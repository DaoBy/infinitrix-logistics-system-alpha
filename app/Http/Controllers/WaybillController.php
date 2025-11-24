<?php

namespace App\Http\Controllers;


use App\Models\Waybill;
use App\Models\DeliveryRequest;
use App\Models\Truck;
use App\Models\Package;
use App\Models\Report;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class WaybillController extends Controller
{
// Add this method to your WaybillController class



  public function index()
{
    // Delivery requests with waybills
   $waybills = Waybill::with([
        'deliveryRequest.dropOffRegion',
        'deliveryRequest.pickUpRegion', // ✅ ADD THIS
        'deliveryRequest.sender',
        'deliveryRequest.receiver',
        'deliveryRequest.packages',
        'deliveryRequest.deliveryOrder.driver',
        'generator'
    ])
    ->latest()
    ->paginate(10);

    // Add is_paid to waybill's deliveryRequest
    foreach ($waybills as $waybill) {
        if ($waybill->deliveryRequest) {
            $waybill->deliveryRequest->is_paid = $waybill->deliveryRequest->isPaid();
        }
    }

    // Pending waybills:
    // - Prepaid: must be paid
    // - Postpaid: always show
    $pendingWaybills = DeliveryRequest::whereDoesntHave('waybill')
        ->whereHas('deliveryOrder')
        ->where(function($q) {
            $q->where(function($q2) {
                // Prepaid - must be paid
                $q2->where('payment_type', 'prepaid')
                   ->where('payment_status', 'paid');
            })
            ->orWhere(function($q2) {
                // Postpaid - always show
                $q2->where('payment_type', 'postpaid');
            });
        })
        ->with([
            'sender',
            'receiver',
            'packages',
            'deliveryOrder.driver',
            'pickUpRegion', // ✅ ADD THIS
            'dropOffRegion'
        ])
        ->latest()
        ->paginate(10, ['*'], 'pending_page');

    // Add is_paid to each pending delivery request
    foreach ($pendingWaybills as $pending) {
        $pending->is_paid = $pending->isPaid();
    }

    return Inertia::render('Admin/Billing/Index', [
        'waybills' => $waybills,
        'pendingWaybills' => $pendingWaybills
    ]);
}


    
    public function generate(DeliveryRequest $deliveryRequest)
    {
        // Check if delivery request exists
        if (!$deliveryRequest) {
            return redirect()->back()->with('error', 'Delivery request not found');
        }

        // Check if waybill already exists
        if ($deliveryRequest->waybill) {
            return redirect()->route('waybills.show', $deliveryRequest->waybill)
                ->with('info', 'Waybill already exists');
        }

        // For prepaid, require payment verification
        if ($deliveryRequest->payment_type === 'prepaid' && !$deliveryRequest->isPaid()) {
            return redirect()->back()
                ->with('error', 'Cannot generate waybill - payment not verified for prepaid delivery');
        }

        $waybillNumber = 'WB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        
        // Add payment type note
        $paymentNote = $deliveryRequest->payment_type === 'postpaid' 
            ? "PAYMENT TYPE: POSTPAID - To be collected after delivery"
            : "PAYMENT TYPE: PREPAID - Paid via " . strtoupper($deliveryRequest->payment_method);

        // Add payment terms and due date to notes if postpaid
        if ($deliveryRequest->payment_type === 'postpaid') {
            $terms = $deliveryRequest->payment_terms ? strtoupper(str_replace('_', ' ', $deliveryRequest->payment_terms)) : 'N/A';
            $dueDate = $deliveryRequest->payment_due_date ? $deliveryRequest->payment_due_date->format('Y-m-d') : 'N/A';
            $paymentNote .= "\nPayment Terms: {$terms}";
            $paymentNote .= "\nDue Date: {$dueDate}";
        }

        $waybill = $deliveryRequest->waybill()->create([
            'generated_by' => auth()->id(),
            'waybill_number' => $waybillNumber,
            'invoice_number' => $invoiceNumber,
            'notes' => $paymentNote . "\nGenerated for delivery request #" . $deliveryRequest->id,
            'file_path' => 'waybills/' . $waybillNumber . '.pdf'
        ]);

        // Generate PDF immediately
        if (method_exists($this, 'generatePdf')) {
            $this->generatePdf($waybill);
        }

        return redirect()->route('waybills.show', $waybill)
            ->with('success', 'Waybill generated successfully!');
    }


public function previewByDelivery(DeliveryRequest $deliveryRequest)
{
    $user = auth()->user();
    
    // Check ownership
    if ($user->hasRole('customer') && $deliveryRequest->sender_id !== $user->customer->id) {
        abort(403, 'Unauthorized');
    }
    
    // Only allow for completed deliveries
    if ($deliveryRequest->status !== 'completed') {
        abort(403, 'Waybill not available for this delivery status');
    }
    
    // Find the waybill for this delivery request
    $waybill = Waybill::where('delivery_request_id', $deliveryRequest->id)->first();
    
    if (!$waybill) {
        abort(404, 'Waybill not found for this delivery');
    }
    
    // Use the same preview logic
    return $this->preview($waybill);
}

  public function preview(Waybill $waybill)
{
    $user = auth()->user();
    
    // Authorization logic
    if ($user->hasRole('admin') || $user->hasRole('staff')) {
        // Admin/staff can preview any waybill
    } else if ($user->hasRole('customer')) {
        if (!$waybill->deliveryRequest || $waybill->deliveryRequest->sender_id !== $user->customer->id) {
            abort(403, 'Unauthorized');
        }
        if ($waybill->deliveryRequest->status !== 'completed') {
            abort(403, 'Waybill not available for this delivery status');
        }
    } else {
        abort(403, 'Unauthorized');
    }

    try {
        // Check if PDF exists in PUBLIC storage, if not generate it
        if (!Storage::disk('public')->exists($waybill->file_path)) {
            \Log::info('PDF not found in public storage, generating: ' . $waybill->file_path);
            $this->generatePdf($waybill);
        }

        $fullPath = Storage::disk('public')->path($waybill->file_path);
        
        if (!file_exists($fullPath)) {
            \Log::error('PDF file not found at: ' . $fullPath);
            abort(404, 'PDF file not found');
        }

        \Log::info('Serving PDF: ' . $fullPath);
        return response()->file($fullPath);
        
    } catch (\Exception $e) {
        \Log::error('PDF Preview Error: ' . $e->getMessage());
        abort(500, 'Failed to load PDF: ' . $e->getMessage());
    }
}

    public function show(Waybill $waybill)
{
    $waybill->load([
        'deliveryRequest' => function ($q) {
            $q->select([
                'id', 'sender_id', 'receiver_id', 'pick_up_region_id', 'drop_off_region_id', 
                'payment_type', 'payment_status', 'payment_method', 'payment_terms', 
                'payment_due_date', 'total_price', 'reference_number', 'created_at',
                'payment_verified', 'payment_verified_at' // Ensure these are loaded
            ])
            ->with([
                'sender:id,user_id,first_name,middle_name,last_name,company_name,email,mobile,phone,building_number,street,barangay,city,province,zip_code,customer_category,frequency_type,payment_terms,credit_limit,notes,created_at,updated_at,archived_at,name,address',
                'receiver:id,user_id,first_name,middle_name,last_name,company_name,email,mobile,phone,building_number,street,barangay,city,province,zip_code,customer_category,frequency_type,payment_terms,credit_limit,notes,created_at,updated_at,archived_at,name,address',
                'packages:id,delivery_request_id,item_code,item_name,weight,length,width,height',
                'pickUpRegion:id,name,address,warehouse_address',
                'dropOffRegion:id,name,address,warehouse_address',
                'deliveryOrder' => function ($q2) {
                    $q2->select(['id', 'delivery_request_id', 'driver_id', 'truck_id', 'driver_truck_assignment_id', 'status'])
                        ->with([
                            'driver:id,name',
                            'truck:id,license_plate,make,model',
                            'driverTruckAssignment' => function ($q3) {
                                $q3->with([
                                    'truck:id,license_plate,make,model',
                                    'driver:id,name'
                                ]);
                            }
                        ]);
                },
            ]);
        },
        'generator:id,name'
    ]);

    $deliveryRequest = $waybill->deliveryRequest;

    // ✅ FIX: Enhanced payment status logic
    if ($deliveryRequest) {
        // For prepaid cash, automatically consider it paid and verified
        if ($deliveryRequest->payment_type === 'prepaid' && $deliveryRequest->payment_method === 'cash') {
            $deliveryRequest->is_paid = true;
            // Also ensure the backend flags are set correctly
            if ($deliveryRequest->payment_status === 'paid' && !$deliveryRequest->payment_verified) {
                // This indicates a data inconsistency - cash prepaid should be auto-verified
                \Log::warning('Cash prepaid payment not verified', [
                    'delivery_request_id' => $deliveryRequest->id,
                    'payment_status' => $deliveryRequest->payment_status,
                    'payment_verified' => $deliveryRequest->payment_verified
                ]);
            }
        } else {
            // For other payment methods, use the standard logic
            $deliveryRequest->is_paid = $deliveryRequest->payment_status === 'paid' && 
                                       $deliveryRequest->payment_verified;
        }
    }

    return Inertia::render('Admin/Billing/Show', [
        'waybill' => $waybill,
        'order' => $waybill->deliveryRequest->deliveryOrder,
        'paymentStatus' => [
            'type' => $deliveryRequest?->payment_type ?? null,
            'method' => $deliveryRequest?->payment_method ?? null,
            'terms' => $deliveryRequest?->payment_terms ?? null,
            'due_date' => $deliveryRequest?->payment_due_date ?? null,
            'isPaid' => $deliveryRequest?->is_paid ?? false,
            'amount' => $deliveryRequest?->total_price ?? 0
        ]
    ]);
}

    public function billing(DeliveryRequest $deliveryRequest)
    {
        // For prepaid, ensure payment is verified
        if ($deliveryRequest->payment_type === 'prepaid' && !$deliveryRequest->isPaid()) {
            return redirect()->back()
                ->with('error', 'Cannot generate billing - payment not verified');
        }

        return Inertia::render('Admin/Billing/Billing', [
            'deliveryRequest' => $deliveryRequest->load([
                'sender:id,name,company_name,address,mobile',
                'receiver:id,name,company_name,address,mobile',
                'pickUpRegion:id,name,address',
                'dropOffRegion:id,name,address',
                'packages:id,delivery_request_id,item_code,item_name,weight,length,width,height',
                'deliveryOrder.driver:id,name,mobile',
                'deliveryOrder.truck:id,license_plate,make,model',
                'payment'
            ]),
            'waybillNumber' => 'WB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'priceMatrix' => null,
            'priceBreakdown' => [
                'base_fee' => $deliveryRequest->base_fee,
                'volume_fee' => $deliveryRequest->volume_fee,
                'weight_fee' => $deliveryRequest->weight_fee,
                'package_fee' => $deliveryRequest->package_fee,
                'total_price' => $deliveryRequest->total_price,
            ],
            'paymentType' => $deliveryRequest->payment_type,
            'paymentTerms' => $deliveryRequest->payment_terms ?? null, // Add this
            'paymentDueDate' => $deliveryRequest->payment_due_date ?? null, // Add this
        ]);
    }

    public function generateFromManifest(Request $request, Truck $truck)
    {
        $request->validate([
            'manifest_number' => 'required|string|max:50'
        ]);

        $reportData = DB::transaction(function () use ($truck, $request) {
            $packages = Package::whereHas('deliveryRequest.deliveryOrder', function($query) use ($truck) {
                $query->where('truck_id', $truck->id)
                    ->whereIn('status', ['dispatched', 'in_transit']);
            })->with([
                'deliveryRequest:id'
            ])->get();

            $generatedCount = 0;

            foreach ($packages as $package) {
                if (!$package->waybill()->exists() && $package->deliveryRequest->deliveryOrder->isAssigned()) {
                    $waybillNumber = 'WB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
                    
                    $waybill = Waybill::create([
                        'delivery_request_id' => $package->delivery_request_id,
                        'generated_by' => auth()->id(),
                        'waybill_number' => $waybillNumber,
                        'notes' => 'Generated from manifest ' . $request->manifest_number
                    ]);
                    
                    $generatedCount++;
                }
            }

            return [
                'truck_id' => $truck->id,
                'manifest_number' => $request->manifest_number,
                'total_packages' => $packages->count(),
                'waybills_generated' => $generatedCount,
                'manifest_path' => null // No PDF generation
            ];
        });

        // Create report after transaction
        Report::create([
            'type' => 'truck_manifest',
            'generated_by' => auth()->id(),
            'parameters' => $reportData,
            'file_path' => $reportData['manifest_path']
        ]);

        return redirect()->back()
            ->with('success', 'Waybills generated successfully!');
    }



    
   protected function generatePdf(Waybill $waybill, $final = false)
{
    \Log::info('Starting PDF generation for waybill: ' . $waybill->id);
    
    try {
        $waybill->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver', 
            'deliveryRequest.packages',
            'deliveryRequest.pickUpRegion',
            'deliveryRequest.dropOffRegion'
        ]);

        if (!$waybill->deliveryRequest) {
            throw new \Exception("Delivery request not found for waybill #{$waybill->id}");
        }

        // Simple HTML content
        $html = view('waybill', [
            'waybill' => $waybill,
            'order' => $waybill->deliveryRequest->deliveryOrder ?? null,
            'paymentType' => $waybill->deliveryRequest->payment_type ?? 'prepaid',
            'isPaid' => true,
            'paymentMethod' => $waybill->deliveryRequest->payment_method ?? 'cash',
            'paymentTerms' => $waybill->deliveryRequest->payment_terms ?? null,
            'paymentDueDate' => $waybill->deliveryRequest->payment_due_date ?? null,
        ])->render();

        // Use mPDF
        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($html);
        
        $filePath = 'waybills/' . $waybill->waybill_number . ($final ? '_final' : '_initial') . '.pdf';
        $fullPath = storage_path('app/public/' . $filePath);
        
        // Ensure directory exists
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        
        $mpdf->Output($fullPath, 'F');
        
        // Update waybill
        $waybill->file_path = $filePath;
        $waybill->save();
        
        \Log::info('mPDF saved successfully: ' . $filePath);
        return true;
        
    } catch (\Exception $e) {
        \Log::error('PDF Generation Error: ' . $e->getMessage());
        throw $e;
    }
}

}
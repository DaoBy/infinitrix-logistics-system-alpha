<?php

namespace App\Http\Controllers;

use TCPDF;
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
protected function generatePdfWithTcpdf(Waybill $waybill, $final = false)
{
    \Log::info('Starting TCPDF generation for waybill: ' . $waybill->id);
    
    try {
        $waybill->load([
            'deliveryRequest' => function ($q) {
                $q->select([
                    'id', 'sender_id', 'receiver_id', 'pick_up_region_id', 'drop_off_region_id', 
                    'payment_type', 'payment_status', 'payment_method', 'payment_terms', 
                    'payment_due_date', 'total_price', 'reference_number', 'created_at',
                    'payment_verified'
                ])
                ->with([
                    'sender:id,user_id,first_name,middle_name,last_name,company_name,email,mobile,phone,building_number,street,barangay,city,province,zip_code,customer_category,frequency_type,payment_terms,credit_limit,notes,created_at,updated_at,archived_at,name,address',
                    'receiver:id,user_id,first_name,middle_name,last_name,company_name,email,mobile,phone,building_number,street,barangay,city,province,zip_code,customer_category,frequency_type,payment_terms,credit_limit,notes,created_at,updated_at,archived_at,name,address',
                    'packages:id,delivery_request_id,item_code,item_name,weight,length,width,height',
                    'deliveryOrder' => function ($q2) {
                        $q2->with([
                            'driver:id,name',
                            'truck:id,license_plate,make,model',
                        ]);
                    },
                    'pickUpRegion:id,name,address,warehouse_address',
                    'dropOffRegion:id,name,address,warehouse_address',
                ]);
            },
            'generator:id,name'
        ]);

        if (!$waybill->deliveryRequest) {
            throw new \Exception("Delivery request not found for waybill #{$waybill->id}");
        }

        // Create PDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Infinitrix Express');
        $pdf->SetAuthor('Infinitrix Express');
        $pdf->SetTitle('Waybill - ' . $waybill->waybill_number);
        $pdf->SetSubject('Waybill Document');
        
        // Add a page
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'INFINITRIX EXPRESS CARGO', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'DELIVERY RECEIPT / WAYBILL', 0, 1, 'C');
        $pdf->Ln(10);
        
        // Waybill Information
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Waybill Information', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(95, 6, 'Waybill No: ' . $waybill->waybill_number, 0, 0);
        $pdf->Cell(95, 6, 'Date: ' . $waybill->created_at->format('M d, Y'), 0, 1);
        $pdf->Cell(95, 6, 'Reference No: ' . ($waybill->deliveryRequest->reference_number ?? 'N/A'), 0, 1);
        $pdf->Cell(95, 6, 'Delivery Type: Branch to Branch', 0, 1);
        $pdf->Ln(5);
        
        // Shipper Information
        if ($waybill->deliveryRequest->sender) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'SHIPPER', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $sender = $waybill->deliveryRequest->sender;
            $pdf->Cell(0, 6, 'Name: ' . ($sender->name ?? $sender->company_name ?? 'N/A'), 0, 1);
            $pdf->Cell(0, 6, 'Address: ' . ($sender->address ?? 'N/A'), 0, 1);
            $pdf->Cell(0, 6, 'Mobile: ' . ($sender->mobile ?? 'N/A'), 0, 1);
            $pdf->Ln(5);
        }
        
        // Consignee Information
        if ($waybill->deliveryRequest->receiver) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'CONSIGNEE', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $receiver = $waybill->deliveryRequest->receiver;
            $pdf->Cell(0, 6, 'Name: ' . ($receiver->name ?? $receiver->company_name ?? 'N/A'), 0, 1);
            $pdf->Cell(0, 6, 'Address: ' . ($receiver->address ?? 'N/A'), 0, 1);
            $pdf->Cell(0, 6, 'Mobile: ' . ($receiver->mobile ?? 'N/A'), 0, 1);
            $pdf->Ln(5);
        }
        
        // Route Information
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Route Information', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        if ($waybill->deliveryRequest->pickUpRegion) {
            $pdf->Cell(0, 6, 'Pickup Branch: ' . $waybill->deliveryRequest->pickUpRegion->name, 0, 1);
            $pdf->Cell(0, 6, 'Pickup Address: ' . ($waybill->deliveryRequest->pickUpRegion->warehouse_address ?? $waybill->deliveryRequest->pickUpRegion->address ?? 'N/A'), 0, 1);
        }
        
        if ($waybill->deliveryRequest->dropOffRegion) {
            $pdf->Cell(0, 6, 'Drop-off Branch: ' . $waybill->deliveryRequest->dropOffRegion->name, 0, 1);
            $pdf->Cell(0, 6, 'Drop-off Address: ' . ($waybill->deliveryRequest->dropOffRegion->warehouse_address ?? $waybill->deliveryRequest->dropOffRegion->address ?? 'N/A'), 0, 1);
        }
        $pdf->Ln(5);
        
        // Package Information
        if ($waybill->deliveryRequest->packages && $waybill->deliveryRequest->packages->count() > 0) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Package Information', 0, 1);
            
            // Table header
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(40, 6, 'Item Code', 1, 0, 'C');
            $pdf->Cell(70, 6, 'Description', 1, 0, 'C');
            $pdf->Cell(40, 6, 'Weight (kg)', 1, 0, 'C');
            $pdf->Cell(40, 6, 'Dimensions (cm)', 1, 1, 'C');
            
            // Table rows
            $pdf->SetFont('helvetica', '', 9);
            foreach ($waybill->deliveryRequest->packages as $package) {
                $pdf->Cell(40, 6, $package->item_code ?? 'N/A', 1, 0);
                $pdf->Cell(70, 6, $package->item_name ?? 'Unspecified Item', 1, 0);
                $pdf->Cell(40, 6, $package->weight ?? 'N/A', 1, 0);
                $pdf->Cell(40, 6, $package->length . 'x' . $package->width . 'x' . $package->height, 1, 1);
            }
            $pdf->Ln(5);
        }
        
        // Payment Information
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Payment Information', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $paymentType = $waybill->deliveryRequest->payment_type ?? 'N/A';
        $paymentMethod = $waybill->deliveryRequest->payment_method ?? 'N/A';
        $totalPrice = $waybill->deliveryRequest->total_price ?? 0;
        
        $pdf->Cell(0, 6, 'Payment Type: ' . strtoupper($paymentType), 0, 1);
        $pdf->Cell(0, 6, 'Payment Method: ' . strtoupper($paymentMethod), 0, 1);
        $pdf->Cell(0, 6, 'Total Amount: P' . number_format($totalPrice, 2), 0, 1);
        
        // Payment status
        $isPaid = false;
        if ($waybill->deliveryRequest) {
            if ($waybill->deliveryRequest->payment_type === 'prepaid' && 
                $waybill->deliveryRequest->payment_method === 'cash') {
                $isPaid = true;
            } else {
                $isPaid = $waybill->deliveryRequest->payment_status === 'paid' && 
                         $waybill->deliveryRequest->payment_verified;
            }
        }
        
        $statusText = $isPaid ? 'PAID' : ($paymentType === 'postpaid' ? 'TO BE COLLECTED' : 'PENDING PAYMENT');
        $pdf->Cell(0, 6, 'Status: ' . $statusText, 0, 1);
        
        // Payment terms for postpaid
        if ($waybill->deliveryRequest->payment_type === 'postpaid') {
            $terms = $waybill->deliveryRequest->payment_terms ?? null;
            $dueDate = $waybill->deliveryRequest->payment_due_date ? \Carbon\Carbon::parse($waybill->deliveryRequest->payment_due_date)->format('M d, Y') : 'N/A';
            
            if ($terms) {
                $termsText = $terms === 'net_7' ? 'Net 7' :
                           ($terms === 'net_15' ? 'Net 15' :
                           ($terms === 'net_30' ? 'Net 30' :
                           ($terms === 'cnd' ? 'CND' : 'N/A')));
                $pdf->Cell(0, 6, 'Payment Terms: ' . $termsText, 0, 1);
            }
            $pdf->Cell(0, 6, 'Due Date: ' . $dueDate, 0, 1);
        }
        
        $pdf->Ln(10);
        
        // Footer
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 6, 'Thank you for choosing Infinitrix Express!', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Generated on: ' . now()->format('Y-m-d H:i:s'), 0, 1, 'C');
        
        $filePath = 'waybills/' . $waybill->waybill_number . ($final ? '_final' : '_initial') . '.pdf';
        $fullPath = storage_path('app/public/' . $filePath);
        
        // Ensure directory exists
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        
        $pdf->Output($fullPath, 'F');
        
        // Update waybill with new file path
        $waybill->file_path = $filePath;
        $waybill->save();
        
        \Log::info('TCPDF PDF saved successfully: ' . $filePath);
        return true;
        
    } catch (\Exception $e) {
        \Log::error('TCPDF Generation Error: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        throw $e;
    }
}


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
    
    // Use your existing preview logic with public disk
    if (!Storage::disk('public')->exists($waybill->file_path)) {
        $this->generatePdf($waybill);
    }

    $fullPath = Storage::disk('public')->path($waybill->file_path);
    return response()->file($fullPath);
}

   public function preview(Waybill $waybill)
{
    $user = auth()->user();
    
    // Allow admin/staff
    if ($user->hasRole('admin') || $user->hasRole('staff')) {
        // Admin/staff can preview any waybill - no additional checks needed
    } 
    // Allow customer if they own the delivery request
    else if ($user->hasRole('customer')) {
        // Check if the customer owns this delivery request
        if (!$waybill->deliveryRequest || $waybill->deliveryRequest->sender_id !== $user->customer->id) {
            abort(403, 'Unauthorized');
        }
        
        // Only allow preview for completed deliveries
        if ($waybill->deliveryRequest->status !== 'completed') {
            abort(403, 'Waybill not available for this delivery status');
        }
    } 
    else {
        abort(403, 'Unauthorized');
    }

    try {
        // FIX: Use public disk
        if (!Storage::disk('public')->exists($waybill->file_path)) {
            \Log::info('PDF not found in public storage, generating: ' . $waybill->file_path);
            $this->generatePdf($waybill);
        }

        $fullPath = Storage::disk('public')->path($waybill->file_path);
        
        if (!file_exists($fullPath)) {
            \Log::error('PDF file not found at: ' . $fullPath);
            abort(404, 'PDF file not found');
        }

        \Log::info('Serving PDF from public storage: ' . $fullPath);
        return response()->file($fullPath);
        
    } catch (\Exception $e) {
        \Log::error('PDF Preview Error: ' . $e->getMessage());
        abort(500, 'Failed to generate PDF: ' . $e->getMessage());
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
        // Ensure TCPDF is loaded
        if (!class_exists('TCPDF')) {
            require_once base_path('vendor/tecnickcom/tcpdf/tcpdf.php');
        }

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

        // Use TCPDF
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Infinitrix Express');
        $pdf->SetAuthor('Infinitrix Express');
        $pdf->SetTitle('Waybill - ' . $waybill->waybill_number);
        
        // Add a page
        $pdf->AddPage();
        
        // Header
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'INFINITRIX EXPRESS CARGO', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'WAYBILL', 0, 1, 'C');
        $pdf->Ln(10);
        
        // Basic info
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Waybill No: ' . $waybill->waybill_number, 0, 1);
        $pdf->Cell(0, 8, 'Date: ' . $waybill->created_at->format('M d, Y'), 0, 1);
        $pdf->Cell(0, 8, 'Reference: ' . ($waybill->deliveryRequest->reference_number ?? 'N/A'), 0, 1);
        $pdf->Ln(5);
        
        // Sender info
        if ($waybill->deliveryRequest->sender) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'SENDER:', 0, 1);
            $pdf->SetFont('helvetica', '', 12);
            $sender = $waybill->deliveryRequest->sender;
            $pdf->Cell(0, 8, 'Name: ' . ($sender->name ?? $sender->company_name ?? 'N/A'), 0, 1);
            $pdf->Cell(0, 8, 'Address: ' . ($sender->address ?? 'N/A'), 0, 1);
            $pdf->Ln(5);
        }
        
        // Footer
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(0, 8, 'Thank you for choosing Infinitrix Express!', 0, 1, 'C');
        $pdf->Cell(0, 8, 'Generated: ' . now()->format('Y-m-d H:i:s'), 0, 1, 'C');
        
        $filePath = 'waybills/' . $waybill->waybill_number . '.pdf';
        $fullPath = storage_path('app/public/' . $filePath);
        
        // Ensure directory exists
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        
        $pdf->Output($fullPath, 'F');
        
        // Update waybill
        $waybill->file_path = $filePath;
        $waybill->save();
        
        \Log::info('PDF saved successfully: ' . $filePath);
        return true;
        
    } catch (\Exception $e) {
        \Log::error('PDF Generation Error: ' . $e->getMessage());
        throw $e;
    }
}
// Add this helper method to generate simple HTML
protected function generateWaybillHtml(Waybill $waybill, $final = false)
{
    $deliveryRequest = $waybill->deliveryRequest;
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Waybill - ' . $waybill->waybill_number . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; line-height: 1.4; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .header h1 { margin: 0; font-size: 20px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            th, td { border: 1px solid #000; padding: 6px; text-align: left; }
            th { background: #f0f0f0; font-weight: bold; }
            .section { margin-bottom: 15px; }
            .label { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>INFINITRIX EXPRESS CARGO</h1>
            <p>DELIVERY RECEIPT / WAYBILL</p>
        </div>
        
        <div class="section">
            <table>
                <tr><th colspan="2">Waybill Information</th></tr>
                <tr>
                    <td><span class="label">Waybill No:</span> ' . $waybill->waybill_number . '</td>
                    <td><span class="label">Date:</span> ' . $waybill->created_at->format('M d, Y') . '</td>
                </tr>
                <tr>
                    <td><span class="label">Reference No:</span> ' . ($deliveryRequest->reference_number ?? 'N/A') . '</td>
                    <td><span class="label">Delivery Type:</span> Branch to Branch</td>
                </tr>
            </table>
        </div>';
        
    if ($deliveryRequest->sender) {
        $html .= '
        <div class="section">
            <table>
                <tr><th colspan="2">Shipper Information</th></tr>
                <tr>
                    <td colspan="2">
                        <span class="label">Name:</span> ' . ($deliveryRequest->sender->name ?? $deliveryRequest->sender->company_name ?? 'N/A') . '<br>
                        <span class="label">Address:</span> ' . ($deliveryRequest->sender->address ?? 'N/A') . '<br>
                        <span class="label">Mobile:</span> ' . ($deliveryRequest->sender->mobile ?? 'N/A') . '
                    </td>
                </tr>
            </table>
        </div>';
    }
    
    if ($deliveryRequest->packages && $deliveryRequest->packages->count() > 0) {
        $html .= '
        <div class="section">
            <table>
                <tr><th colspan="4">Package Information</th></tr>
                <tr>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Weight (kg)</th>
                    <th>Dimensions (cm)</th>
                </tr>';
        
        foreach ($deliveryRequest->packages as $package) {
            $html .= '
                <tr>
                    <td>' . ($package->item_code ?? 'N/A') . '</td>
                    <td>' . ($package->item_name ?? 'Unspecified Item') . '</td>
                    <td>' . ($package->weight ?? 'N/A') . '</td>
                    <td>' . $package->length . 'x' . $package->width . 'x' . $package->height . '</td>
                </tr>';
        }
        
        $html .= '
            </table>
        </div>';
    }
    
    $html .= '
        <div style="margin-top: 30px; text-align: center; font-size: 10px;">
            <p>Thank you for choosing Infinitrix Express!</p>
            <p>Generated on: ' . now()->format('Y-m-d H:i:s') . '</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
}
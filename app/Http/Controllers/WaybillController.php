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
   public function index()
    {
        // Delivery requests with waybills
        $waybills = Waybill::with([
                'deliveryRequest.dropOffRegion',
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
        // - Postpaid: always show (payment collected after delivery)
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

    public function preview(Waybill $waybill)
    {
        if (!Storage::exists($waybill->file_path)) {
            $this->generatePdf($waybill);
        }

        return response()->file(Storage::path($waybill->file_path));
    }

    public function show(Waybill $waybill)
    {
        $waybill->load([
            'deliveryRequest' => function ($q) {
                $q->select([
                    'id', 'sender_id', 'receiver_id', 'pick_up_region_id', 'drop_off_region_id', 'payment_type', 'payment_status', 'payment_method', 'payment_terms', 'payment_due_date', 'total_price', 'reference_number', 'created_at'
                ])
                ->with([
                    // Load all sender/receiver fields for full name/address
                    'sender:id,user_id,first_name,middle_name,last_name,company_name,email,mobile,phone,building_number,street,barangay,city,province,zip_code,customer_category,frequency_type,payment_terms,credit_limit,notes,created_at,updated_at,archived_at,name,address',
                    'receiver:id,user_id,first_name,middle_name,last_name,company_name,email,mobile,phone,building_number,street,barangay,city,province,zip_code,customer_category,frequency_type,payment_terms,credit_limit,notes,created_at,updated_at,archived_at,name,address',
                    'packages:id,delivery_request_id,item_code,item_name,weight,length,width,height',
                    'pickUpRegion:id,name,address,warehouse_address',
                    'dropOffRegion:id,name,address,warehouse_address',
                    'deliveryOrder' => function ($q2) {
                        $q2->select(['id', 'delivery_request_id', 'driver_id', 'truck_id', 'status'])
                            ->with([
                                'driver:id,name',
                                'truck:id,license_plate,make,model'
                            ]);
                    },
                ]);
            },
            'generator:id,name'
        ]);

        $deliveryRequest = $waybill->deliveryRequest;

        // Set is_paid property for frontend consistency (like in index)
        if ($deliveryRequest) {
            $deliveryRequest->is_paid = $deliveryRequest->isPaid();
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
        $waybill->load([
            'deliveryRequest' => function ($q) {
                $q->select([
                    'id', 'sender_id', 'receiver_id', 'pick_up_region_id', 'drop_off_region_id', 'payment_type', 'payment_status', 'payment_method', 'payment_terms', 'payment_due_date', 'total_price', 'reference_number', 'created_at'
                ])
                ->with([
                    // Load all sender/receiver fields for full name/address
                    'sender:id,user_id,first_name,middle_name,last_name,company_name,email,mobile,phone,building_number,street,barangay,city,province,zip_code,customer_category,frequency_type,payment_terms,credit_limit,notes,created_at,updated_at,archived_at,name,address',
                    'receiver:id,user_id,first_name,middle_name,last_name,company_name,email,mobile,phone,building_number,street,barangay,city,province,zip_code,customer_category,frequency_type,payment_terms,credit_limit,notes,created_at,updated_at,archived_at,name,address',
                    'packages:id,delivery_request_id,item_code,item_name,weight,length,width,height',
                    'deliveryOrder.driver:id,name',
                    'deliveryOrder.truck:id,license_plate,make,model',
                    'pickUpRegion:id,name,address,warehouse_address',
                ]);
            },
            'generator:id,name'
        ]);

        // Check if deliveryRequest exists
        if (!$waybill->deliveryRequest) {
            throw new \Exception("Delivery request not found for waybill #{$waybill->id}");
        }

        $view = $final ? 'waybill_complete' : 'waybill';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, [
            'order' => $waybill->deliveryRequest->deliveryOrder ?? null,
            'waybill' => $waybill,
            'paymentType' => $waybill->deliveryRequest->payment_type,
            'isPaid' => $waybill->deliveryRequest->isPaid(),
            'paymentMethod' => $waybill->deliveryRequest->payment_method,
            'paymentTerms' => $waybill->deliveryRequest->payment_terms ?? null,
            'paymentDueDate' => $waybill->deliveryRequest->payment_due_date ?? null,
        ]);

        $filePath = 'waybills/' . $waybill->waybill_number . ($final ? '_final' : '_initial') . '.pdf';

        \Illuminate\Support\Facades\Storage::put($filePath, $pdf->output());

        // Update the waybill file_path if needed
        $waybill->file_path = $filePath;
        $waybill->save();
    }
}
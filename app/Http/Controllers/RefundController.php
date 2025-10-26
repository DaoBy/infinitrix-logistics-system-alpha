<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\DeliveryRequest;
use App\Models\Package;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
  public function index(Request $request)
{
    // Existing refunds and adjustments
     $refunds = Refund::with(['deliveryRequest.sender', 'processor'])
        ->latest()
        ->filter($request->only('search', 'status', 'reason', 'type'))
        ->paginate(10)
        ->withQueryString();
        
    $refunds->getCollection()->transform(function ($refund) {
        $refund->reason_label = $refund->getReasonLabelAttribute();
        $refund->status_label = $refund->getStatusLabelAttribute();
        $refund->type_label = $refund->getTypeLabelAttribute();
        return $refund;
    });
    // Delivery requests that need adjustment (but no refund record exists yet)
    $needsAdjustmentQuery = DeliveryRequest::with(['sender', 'packages'])
        ->where('status', 'completed')
        ->where('payment_type', 'postpaid')
        ->where('payment_status', 'requires_adjustment')
        ->whereDoesntHave('refunds', function($q) {
            $q->where('type', 'adjustment');
        })
        ->latest();

    // Apply search filter to needsAdjustment
    if ($request->search) {
        $search = $request->search;
        $needsAdjustmentQuery->where(function($q) use ($search) {
            $q->where('reference_number', 'like', "%{$search}%")
              ->orWhereHas('sender', function($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }

    $needsAdjustment = $needsAdjustmentQuery->paginate(10, ['*'], 'adjustment_page');

    // Stats with safe defaults
    $stats = [
        'pending_refunds' => Refund::where('type', 'refund')->whereIn('status', ['pending'])->count(),
        'pending_adjustments' => Refund::where('type', 'adjustment')->whereIn('status', ['pending_adjustment'])->count(),
        'needs_adjustment' => DeliveryRequest::where('payment_status', 'requires_adjustment')->count(),
    ];

    return Inertia::render('Admin/Refunds/Index', [
        'refunds' => $refunds,
        'needsAdjustment' => $needsAdjustment,
        'stats' => $stats,
        'filters' => $request->only('search', 'status', 'reason', 'type'),
        'statusOptions' => Refund::STATUSES,
        'reasonOptions' => Refund::REASONS,
        'typeOptions' => Refund::TYPES,
    ]);
}

    public function create(Request $request)
    {
        $deliveryRequest = null;
        
        if ($request->has('delivery_request_id')) {
            $deliveryRequest = DeliveryRequest::with(['packages', 'sender', 'payment'])
                ->where('status', 'completed')
                ->findOrFail($request->delivery_request_id);
        }

        return Inertia::render('Admin/Refunds/Create', [
            'deliveryRequest' => $deliveryRequest,
            'reasonOptions' => Refund::REASONS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_request_id' => 'required|exists:delivery_requests,id',
            'refund_amount' => 'required|numeric|min:0.01',
'reason' => 'required|in:damaged,lost,other',
            'description' => 'required|string|min:10|max:1000',
            'refunded_packages' => 'nullable|array',
            'refunded_packages.*' => 'exists:packages,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if refund/adjustment already exists for this delivery request
        $existingRefund = Refund::where('delivery_request_id', $validated['delivery_request_id'])
            ->whereIn('status', ['pending', 'processed', 'adjusted'])
            ->first();

        if ($existingRefund) {
            return back()->with('error', 'A refund/adjustment request already exists for this delivery order.');
        }

        return DB::transaction(function () use ($validated) {
            $deliveryRequest = DeliveryRequest::findOrFail($validated['delivery_request_id']);
            
            // Determine type based on payment type
            $type = $deliveryRequest->isPrepaid() ? 'refund' : 'adjustment';
            
            if ($type === 'refund') {
                return $this->processRefund($validated, $deliveryRequest);
            } else {
                return $this->processAdjustment($validated, $deliveryRequest);
            }
        });
    }

    /**
     * Process prepaid refund
     */
    private function processRefund($validated, DeliveryRequest $deliveryRequest)
    {
        // Validate delivery request is eligible for refund
        if (!$deliveryRequest->isPrepaid()) {
            throw new \Exception("Refunds can only be created for prepaid delivery requests.");
        }

        if ($deliveryRequest->payment_status === 'refunded') {
            throw new \Exception("This delivery request has already been refunded.");
        }

        // Calculate maximum refundable amount
        $maxRefundable = $this->calculateMaxRefundableAmount($deliveryRequest, $validated['refunded_packages'] ?? []);
        
        // Validate refund amount doesn't exceed maximum
        if ($validated['refund_amount'] > $maxRefundable) {
            throw new \Exception("Refund amount cannot exceed maximum refundable amount: ₱" . number_format($maxRefundable, 2));
        }

        // Create and immediately process the refund
        $refund = Refund::create([
            'delivery_request_id' => $validated['delivery_request_id'],
            'processed_by' => auth()->id(),
            'refund_amount' => $validated['refund_amount'],
            'original_amount' => $deliveryRequest->total_price,
            'reason' => $validated['reason'],
            'description' => $validated['description'],
            'refunded_packages' => $validated['refunded_packages'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'type' => 'refund',
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        // Update delivery request payment status immediately
        $deliveryRequest->update([
            'payment_status' => 'refunded',
        ]);

        // Also complete the delivery order if it exists and isn't already completed
        if ($deliveryRequest->deliveryOrder && !$deliveryRequest->deliveryOrder->isCompleted()) {
            $deliveryRequest->deliveryOrder->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);
        }

        // Release undamaged packages after refund processing
        $this->releaseUndamagedPackages($deliveryRequest, $validated['refunded_packages'] ?? []);

        return redirect()->route('refunds.index')
            ->with('success', 'Refund processed successfully.');
    }

    /**
     * Process postpaid adjustment
     */
    private function processAdjustment($validated, DeliveryRequest $deliveryRequest)
    {
        // Validate delivery request is eligible for adjustment
        if (!$deliveryRequest->isPostpaid()) {
            throw new \Exception("Invoice adjustments can only be created for postpaid delivery requests.");
        }

        if ($deliveryRequest->payment_status !== 'requires_adjustment') {
            throw new \Exception("This delivery request is not flagged for adjustment.");
        }

        // Calculate maximum adjustable amount
        $maxAdjustable = $this->calculateMaxRefundableAmount($deliveryRequest, $validated['refunded_packages'] ?? []);
        
        // Validate adjustment amount doesn't exceed maximum
        if ($validated['refund_amount'] > $maxAdjustable) {
            throw new \Exception("Adjustment amount cannot exceed maximum adjustable amount: ₱" . number_format($maxAdjustable, 2));
        }

        // Calculate new amount due
        $newAmountDue = $deliveryRequest->total_price - $validated['refund_amount'];
        
        if ($newAmountDue < 0) {
            throw new \Exception("Adjustment cannot result in negative amount due.");
        }

        // Create and process the adjustment
        $refund = Refund::create([
            'delivery_request_id' => $validated['delivery_request_id'],
            'processed_by' => auth()->id(),
            'refund_amount' => $validated['refund_amount'],
            'original_amount' => $deliveryRequest->total_price,
            'reason' => $validated['reason'],
            'description' => $validated['description'],
            'refunded_packages' => $validated['refunded_packages'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'type' => 'adjustment',
            'status' => 'adjusted',
            'processed_at' => now(),
            'adjusted_amount' => $newAmountDue,
        ]);

        // Update delivery request with adjusted amount and status
        $paymentStatus = $newAmountDue > 0 ? 'unpaid' : 'paid';
        
        $deliveryRequest->update([
            'total_price' => $newAmountDue, // Update the actual amount due
            'payment_status' => $paymentStatus,
            'status' => 'completed', // Mark as completed after adjustment
        ]);

        // Also complete the delivery order if it exists
        if ($deliveryRequest->deliveryOrder && !$deliveryRequest->deliveryOrder->isCompleted()) {
            $deliveryRequest->deliveryOrder->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);
        }

        // Release undamaged packages after adjustment processing
        $this->releaseUndamagedPackages($deliveryRequest, $validated['refunded_packages'] ?? []);

        $message = $newAmountDue > 0 
            ? "Invoice adjusted successfully. New amount due: ₱" . number_format($newAmountDue, 2)
            : "Invoice fully adjusted. No payment required.";

        return redirect()->route('refunds.index')
            ->with('success', $message);
    }

  public function show(Refund $refund)
{
    $refund->load([
        'deliveryRequest.sender',
        'deliveryRequest.receiver',
        'deliveryRequest.packages' => function ($query) {
            $query->select('id', 'item_name', 'value', 'weight', 'status', 'delivery_request_id', 
                         'photo_path', 'incident_evidence', 'incident_description', 'incident_reported_at');
        },
        'deliveryRequest.payment',
        'processor'
    ]);

    // Transform the refund to include refunded packages with their details
    $refund->refunded_packages_list = [];
    if ($refund->refunded_packages && is_array($refund->refunded_packages)) {
        $refund->refunded_packages_list = Package::whereIn('id', $refund->refunded_packages)
            ->select('id', 'item_name', 'value', 'weight', 'status', 'delivery_request_id', 
                    'photo_path', 'incident_evidence', 'incident_description', 'incident_reported_at')
            ->get()
            ->map(function ($package) {
                // Convert photo_path and incident_evidence to full URLs
                if ($package->photo_path) {
                    $package->photo_url = is_array($package->photo_path) 
                        ? array_map(fn($path) => \Storage::url($path), $package->photo_path)
                        : [\Storage::url($package->photo_path)];
                } else {
                    $package->photo_url = [];
                }

                if ($package->incident_evidence) {
                    $package->incident_evidence_urls = is_array($package->incident_evidence)
                        ? array_map(fn($path) => \Storage::url($path), $package->incident_evidence)
                        : [\Storage::url($package->incident_evidence)];
                } else {
                    $package->incident_evidence_urls = [];
                }

                return $package;
            });
    }

    return Inertia::render('Admin/Refunds/Show', [
        'refund' => $refund,
        'statusOptions' => Refund::STATUSES,
        'reasonOptions' => Refund::REASONS,
    ]);
}

    public function edit(Refund $refund)
{
    if (!in_array($refund->status, ['pending', 'pending_adjustment'])) {
        return redirect()->route('refunds.show', $refund)
            ->with('error', 'Only pending refunds/adjustments can be edited');
    }

    // FIXED: Load packages with their values AND receiver
    $refund->load([
        'deliveryRequest.sender',
        'deliveryRequest.receiver', // Add this line
        'deliveryRequest.packages' => function ($query) {
            $query->select('id', 'item_name', 'value', 'weight', 'status', 'delivery_request_id', 
                         'photo_path', 'incident_evidence', 'incident_description', 'incident_reported_at');
        },
        'deliveryRequest.payment'
    ]);

    // Debug logging
    \Log::info('Edit Refund Data', [
        'refund_id' => $refund->id,
        'original_amount' => $refund->original_amount,
        'delivery_request_packages_count' => $refund->deliveryRequest->packages->count(),
        'package_values_sum' => $refund->deliveryRequest->packages->sum('value'),
        'refunded_packages' => $refund->refunded_packages
    ]);

    $maxRefundable = $this->calculateMaxRefundableAmount($refund->deliveryRequest, $refund->refunded_packages ?? []);

    return Inertia::render('Admin/Refunds/Edit', [
        'refund' => $refund,
        'maxRefundable' => $maxRefundable,
        'reasonOptions' => Refund::REASONS,
    ]);
}

    public function update(Request $request, Refund $refund)
    {
        if (!in_array($refund->status, ['pending', 'pending_adjustment'])) {
            return back()->with('error', 'Only pending refunds/adjustments can be updated');
        }

        $validated = $request->validate([
            'refund_amount' => 'required|numeric|min:0.01',
            'reason' => 'required|in:damaged,lost,delayed,incomplete,customer_request,wrong_delivery,other',
            'description' => 'required|string|min:10|max:1000',
            'notes' => 'nullable|string|max:500',
            'action' => 'required|in:process,update_pending'
        ]);

        $maxRefundable = $refund->calculateMaxRefundableAmount();

        // Enhanced amount validation
        if ($validated['refund_amount'] > $maxRefundable) {
            return back()->with('error', "Amount cannot exceed maximum: ₱" . number_format($maxRefundable, 2));
        }

        if ($validated['refund_amount'] <= 0) {
            return back()->with('error', "Amount must be greater than 0.");
        }

        return DB::transaction(function () use ($refund, $validated, $maxRefundable) {
            // Update refund/adjustment details
            $refund->update([
                'refund_amount' => $validated['refund_amount'],
                'reason' => $validated['reason'],
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($validated['action'] === 'process') {
                // Process immediately based on type
                if ($refund->type === 'refund') {
                    $refund->markAsProcessed();
                    $message = 'Refund processed successfully with negotiated amount: ₱' . number_format($validated['refund_amount'], 2);
                } else {
                    $refund->markAsAdjusted();
                    $newAmountDue = $refund->deliveryRequest->total_price - $validated['refund_amount'];
                    $message = $newAmountDue > 0 
                        ? "Invoice adjusted successfully. New amount due: ₱" . number_format($newAmountDue, 2)
                        : "Invoice fully adjusted. No payment required.";
                }
                
                return redirect()->route('refunds.index')->with('success', $message);
            } else {
                // Keep as pending (updated negotiation details)
                $statusMessage = $refund->type === 'refund' ? 'refund' : 'adjustment';
                return redirect()->route('refunds.show', $refund)
                    ->with('success', "{$statusMessage} negotiation details updated. Request remains pending.");
            }
        });
    }

    public function process(Refund $refund)
    {
        if (!in_array($refund->status, ['pending', 'pending_adjustment'])) {
            return back()->with('error', 'Only pending refunds/adjustments can be processed');
        }

        DB::transaction(function () use ($refund) {
            if ($refund->type === 'refund') {
                $refund->markAsProcessed();
            } else {
                $refund->markAsAdjusted();
            }
        });

        $message = $refund->type === 'refund' ? 'Refund' : 'Invoice adjustment';
        return redirect()->route('refunds.index')
            ->with('success', $message . ' processed successfully.');
    }

   public function searchDeliveryRequests(Request $request)
{
    $query = DeliveryRequest::with(['sender', 'packages', 'payment'])
        ->where('status', 'completed')
        ->where(function($q) {
            // Prepaid: must be paid and not refunded
            $q->where(function($q1) {
                $q1->where('payment_type', 'prepaid')
                   ->where('payment_status', 'paid');
            })->orWhere(function($q1) {
                // Postpaid: must require adjustment
                $q1->where('payment_type', 'postpaid')
                   ->where('payment_status', 'requires_adjustment');
            });
        })
        ->whereDoesntHave('refunds', function($q) {
            $q->whereIn('status', ['processed', 'adjusted']);
        });

    if ($request->has('search') && !empty($request->search)) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('reference_number', 'like', "%{$search}%")
              ->orWhereHas('sender', function($q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }

    $deliveryRequests = $query->limit(10)->get();

    return response()->json($deliveryRequests);
}

    public function calculateMaxRefund(Request $request)
    {
        try {
            // Handle package_ids parameter - it might be a JSON string
            $packageIds = [];
            if ($request->has('package_ids')) {
                if (is_string($request->package_ids)) {
                    // Try to decode JSON string
                    $packageIds = json_decode($request->package_ids, true) ?? [];
                } else {
                    $packageIds = $request->package_ids;
                }
            }

            $request->merge(['package_ids' => $packageIds]);

            $request->validate([
                'delivery_request_id' => 'required|exists:delivery_requests,id',
                'package_ids' => 'nullable|array',
                'package_ids.*' => 'exists:packages,id',
            ]);

            $deliveryRequest = DeliveryRequest::with('packages')->findOrFail($request->delivery_request_id);
            
            $maxRefundable = $this->calculateMaxRefundableAmount($deliveryRequest, $packageIds);

            // Get detailed breakdown for debugging
            $deliveryFee = $deliveryRequest->total_price;
            $packageValues = $deliveryRequest->packages->sum('value');
            $selectedPackageValues = !empty($packageIds) 
                ? Package::whereIn('id', $packageIds)->sum('value')
                : $packageValues;

            return response()->json([
                'max_refundable' => (float) $maxRefundable,
                'original_amount' => (float) $deliveryRequest->total_price,
                'delivery_fee' => (float) $deliveryFee,
                'total_package_values' => (float) $packageValues,
                'selected_package_values' => (float) $selectedPackageValues,
                'package_count' => $deliveryRequest->packages->count(),
                'selected_package_count' => count($packageIds),
                'type' => $deliveryRequest->isPrepaid() ? 'refund' : 'adjustment',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Calculate max refund error: ' . $e->getMessage(), [
                'delivery_request_id' => $request->delivery_request_id,
                'package_ids' => $request->package_ids,
                'exception' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to calculate maximum refund amount: ' . $e->getMessage(),
                'max_refundable' => 0,
                'original_amount' => 0,
                'delivery_fee' => 0,
                'total_package_values' => 0,
                'selected_package_values' => 0,
                'type' => 'refund',
            ], 500);
        }
    }

    /**
     * Calculate maximum refundable/adjustable amount
     */
/**
 * Calculate maximum refundable/adjustable amount
 */
  private function calculateMaxRefundableAmount(DeliveryRequest $deliveryRequest, array $packageIds = []): float
    {
        // Start with delivery fee (base shipping cost)
        $deliveryFee = (float) $deliveryRequest->total_price;
        
        // Calculate package values
        $packageValues = 0;

        // Ensure packages are loaded
        if (!$deliveryRequest->relationLoaded('packages')) {
            $deliveryRequest->load('packages');
        }

        if (!empty($packageIds)) {
            // Add selected package values only
            $selectedPackages = Package::whereIn('id', $packageIds)->get();
            $packageValues = $selectedPackages->sum('value');
        } else {
            // No packages selected = assume complete failure, use all packages
            $packageValues = $deliveryRequest->packages->sum('value');
        }

        // Total maximum = delivery fee + package values
        $maxRefundable = $deliveryFee + (float) $packageValues;

        \Log::info('Max refundable calculation', [
            'delivery_request_id' => $deliveryRequest->id,
            'delivery_fee' => $deliveryFee,
            'package_values' => $packageValues,
            'selected_package_ids' => $packageIds,
            'max_refundable' => $maxRefundable,
            'package_count' => $deliveryRequest->packages->count(),
            'all_package_values' => $deliveryRequest->packages->pluck('value')
        ]);

        return $maxRefundable;
    }

    /**
     * Release undamaged packages after refund/adjustment processing
     */
    private function releaseUndamagedPackages(DeliveryRequest $deliveryRequest, array $refundedPackageIds = []): void
    {
        $undamagedPackages = $deliveryRequest->packages()
            ->whereNotIn('id', $refundedPackageIds)
            ->whereNotIn('status', ['damaged_in_transit', 'lost_in_transit'])
            ->get();

        foreach ($undamagedPackages as $package) {
            $package->updateStatus('completed', auth()->user(), "Released after refund/adjustment processing");
        }
    }
}
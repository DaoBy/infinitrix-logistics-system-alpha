<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryRequest;
use App\Models\Refund;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DeliveryCompletionController extends Controller
{
    public function showCompletionForm(DeliveryOrder $order)
    {
        $order->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver', 
            'deliveryRequest.packages' => function($query) {
                $query->with(['currentRegion', 'incidentReporter']);
            },
            'deliveryRequest.payment',
            'deliveryRequest.dropOffRegion',
            'deliveryRequest.pickUpRegion',
            'deliveryRequest.refunds',
            'driver',
            'truck'
        ]);

        // Calculate delivery outcome statistics
        $outcomeStats = $this->calculateDeliveryOutcome($order);
        
        // Check if refund is needed (prepaid with issues)
        $requiresRefund = $order->needsReview() && $order->deliveryRequest->isPrepaid();

        // Get existing refund details
        $existingRefund = $order->deliveryRequest->refunds->first();
        $hasExistingRefund = $existingRefund ? true : false;
        $existingRefundStatus = $existingRefund ? $existingRefund->status : null;
        $existingRefundId = $existingRefund ? $existingRefund->id : null;

        return Inertia::render('Admin/DeliveryCompletion/CompletionForm', [
            'order' => $order,
            'outcomeStats' => $outcomeStats,
            'requiresRefund' => $requiresRefund,
            'hasExistingRefund' => $hasExistingRefund,
            'existingRefundStatus' => $existingRefundStatus,
            'existingRefundId' => $existingRefundId,
        ]);
    }

    public function processCompletion(Request $request, DeliveryOrder $order)
    {
        $request->validate([
            'receiver_name' => 'required|string|max:255',
            'receiver_contact' => 'nullable|string|max:50',
            'signature' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
            'release_packages' => 'required|array',
            'release_packages.*' => 'exists:packages,id',
        ]);

        Log::info('📦 Starting delivery completion process', [
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'release_packages' => $request->release_packages,
            'order_status' => $order->status,
        ]);

        // PREVENT DUPLICATE PROCESSING - Check if already completed
        if ($order->status === 'completed') {
            Log::warning('❌ Duplicate completion attempt for already completed order', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
            ]);
            return back()->with('error', 'This delivery order has already been completed.');
        }

        // PREVENT DUPLICATE REFUND CREATION - More specific check
        if ($order->needsReview() && $order->deliveryRequest->isPrepaid()) {
            $existingRefund = Refund::where('delivery_request_id', $order->delivery_request_id)
                ->whereIn('status', ['pending', 'processed'])
                ->first();
                
            if ($existingRefund) {
                Log::warning('❌ Duplicate refund creation attempt', [
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'existing_refund_id' => $existingRefund->id,
                    'existing_refund_status' => $existingRefund->status
                ]);
                
                $message = $existingRefund->status === 'processed' 
                    ? "A refund has already been processed for this delivery. No further action needed."
                    : "A refund request (#{$existingRefund->id}) is already pending review.";
                    
                return back()->with('error', $message);
            }
        }

        return DB::transaction(function () use ($order, $request) {
            $deliveryRequest = $order->deliveryRequest;
            $isPrepaid = $deliveryRequest->isPrepaid();
            $isPostpaid = $deliveryRequest->isPostpaid();

            Log::info('🔍 Transaction started for delivery completion', [
                'order_id' => $order->id,
                'delivery_request_id' => $deliveryRequest->id,
                'payment_type' => $isPrepaid ? 'prepaid' : ($isPostpaid ? 'postpaid' : 'unknown'),
                'order_status' => $order->status,
            ]);

            // 1. Handle based on payment type and order status
            if ($order->isDelivered()) {
                Log::info('✅ Completing successful delivery', ['order_id' => $order->id]);
                $this->completeSuccessfulDelivery($order, $request);
            } elseif ($order->needsReview()) {
                Log::info('⚠️ Handling delivery with issues', [
                    'order_id' => $order->id,
                    'payment_type' => $isPrepaid ? 'prepaid' : 'postpaid',
                ]);

                if ($isPrepaid) {
                    $this->handlePrepaidWithIssues($order, $request);
                } else {
                    $this->handlePostpaidWithIssues($order, $request);
                }
            }

            // 2. Update delivery order completion details
            // Note: Prepaid with issues stays in 'needs_review' until refund processed
            $orderUpdateData = [
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'receiver_name' => $request->receiver_name,
                'receiver_contact' => $request->receiver_contact,
                'signature' => $request->signature,
                'notes' => $request->notes,
            ];

            // Only mark as completed if it's not prepaid with issues
            if (!($order->needsReview() && $isPrepaid)) {
                $orderUpdateData['status'] = 'completed';
            }

            $order->update($orderUpdateData);

            // 3. Update delivery request status
            if (!($order->needsReview() && $isPrepaid)) {
                $deliveryRequest->update(['status' => 'completed']);
            }

            Log::info('✅ Delivery completion process finished', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'final_status' => $order->status,
            ]);

            return redirect()->route('delivery-completion.ready-for-completion')
                ->with('success', 'Delivery completion processed successfully');
        });
    }

    private function completeSuccessfulDelivery(DeliveryOrder $order, Request $request)
    {
        $deliveryRequest = $order->deliveryRequest;
        
        // Release all packages for successful delivery
        $this->releasePackages($order, $request->release_packages, $request->receiver_name);
        
        if ($deliveryRequest->isPostpaid()) {
            // Postpaid - mark as awaiting payment
            $deliveryRequest->update([
                'payment_status' => 'awaiting_payment'
            ]);
        }
        // Prepaid - no action needed (already paid)

        Log::info("Successful delivery completed", [
            'order_id' => $order->id,
            'payment_type' => $deliveryRequest->payment_type,
            'payment_status' => $deliveryRequest->payment_status
        ]);
    }

    private function handlePrepaidWithIssues(DeliveryOrder $order, Request $request)
    {
        $deliveryRequest = $order->deliveryRequest;
        
        // CHECK FOR EXISTING REFUND FIRST
        $existingRefund = Refund::where('delivery_request_id', $deliveryRequest->id)
            ->whereIn('status', ['pending', 'processed'])
            ->first();

        if ($existingRefund) {
            Log::warning("Prepaid delivery with issues - refund already exists", [
                'order_id' => $order->id,
                'existing_refund_id' => $existingRefund->id,
                'existing_refund_status' => $existingRefund->status
            ]);
            
            // Return specific message based on refund status
            $message = $existingRefund->status === 'processed' 
                ? "A refund has already been processed for this delivery order. No further action required."
                : "A refund request is already pending review for this delivery order.";
                
            throw new \Exception($message);
        }
        
        // Get incident packages (damaged/lost)
        $incidentPackages = $this->getIncidentPackages($order);
        $incidentPackageIds = $incidentPackages->pluck('id')->toArray();
        
        // Calculate refund amount: DELIVERY COST + INCIDENT PACKAGE VALUES
        $deliveryCost = $deliveryRequest->total_price;
        $incidentPackageValues = $incidentPackages->sum('value');
        $refundAmount = $deliveryCost + $incidentPackageValues;
        
        // Create PENDING refund for negotiation (holds packages)
        $refund = Refund::create([
            'delivery_request_id' => $deliveryRequest->id,
            'processed_by' => auth()->id(),
            'refund_amount' => $refundAmount,
            'original_amount' => $deliveryCost,
            'reason' => 'incomplete',
            'description' => 'Delivery issues require refund negotiation',
            'refunded_packages' => $incidentPackageIds, // Auto-select incident packages
            'notes' => $request->notes ?? "Automatic refund: Delivery cost + " . count($incidentPackageIds) . " incident package(s)",
            'status' => 'pending', // PENDING = holds packages
        ]);

        Log::info("Prepaid delivery with issues - refund created with delivery cost + package values", [
            'order_id' => $order->id,
            'refund_id' => $refund->id,
            'refund_amount' => $refundAmount,
            'delivery_cost' => $deliveryCost,
            'incident_package_values' => $incidentPackageValues,
            'incident_packages' => $incidentPackageIds,
            'incident_count' => count($incidentPackageIds)
        ]);
    }

    private function handlePostpaidWithIssues(DeliveryOrder $order, Request $request)
    {
        $deliveryRequest = $order->deliveryRequest;
        
        // Release ALL packages immediately (including damaged ones for inspection)
        $this->releasePackages($order, $request->release_packages, $request->receiver_name);
        
        // Flag for invoice adjustment - don't charge for damaged/lost
        $incidentPackages = $this->getIncidentPackages($order);
        $adjustmentAmount = $incidentPackages->sum('value');
        
        $deliveryRequest->update([
            'payment_status' => 'requires_adjustment',
            'notes' => $request->notes ?? "Invoice adjustment needed: ₱" . number_format($adjustmentAmount, 2)
        ]);

        Log::info("Postpaid delivery with issues - packages released, invoice adjustment required", [
            'order_id' => $order->id,
            'adjustment_amount' => $adjustmentAmount,
            'incident_packages' => $incidentPackages->pluck('id')
        ]);
    }

    private function releasePackages(DeliveryOrder $order, array $packageIds, string $receiverName)
    {
        $packages = $order->deliveryRequest->packages()
            ->whereIn('id', $packageIds)
            ->get();

        foreach ($packages as $package) {
            // Release ALL selected packages (including damaged ones)
            // Only block lost packages (which are already filtered out in Vue)
            if (!in_array($package->status, ['lost_in_transit'])) {
                $package->updateStatus('completed', auth()->user(), "Released to: $receiverName");
                $package->confirmDelivery(auth()->user(), "Released to: $receiverName");
                
                // If package is damaged, log it for refund tracking
                if ($package->status === 'damaged_in_transit') {
                    Log::info("Released damaged package with refund", [
                        'package_id' => $package->id,
                        'order_id' => $order->id,
                        'value' => $package->value
                    ]);
                }
            }
        }

        Log::info("Packages released for delivery order", [
            'order_id' => $order->id,
            'released_packages' => $packageIds,
            'released_by' => auth()->id(),
            'receiver' => $receiverName
        ]);
    }

    private function calculateDeliveryOutcome(DeliveryOrder $order): array
    {
        $packages = $order->deliveryRequest->packages;
        
        $total = $packages->count();
        $delivered = $packages->where('status', 'delivered')->count();
        $damaged = $packages->where('status', 'damaged_in_transit')->count();
        $lost = $packages->where('status', 'lost_in_transit')->count();
        
        return [
            'total_packages' => $total,
            'delivered_count' => $delivered,
            'damaged_count' => $damaged,
            'lost_count' => $lost,
            'success_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            'incident_count' => $damaged + $lost,
        ];
    }

    private function getIncidentPackages(DeliveryOrder $order)
    {
        return $order->deliveryRequest->packages()
            ->whereIn('status', ['damaged_in_transit', 'lost_in_transit'])
            ->get();
    }

    private function getIncidentPackageIds(DeliveryOrder $order): array
    {
        return $this->getIncidentPackages($order)->pluck('id')->toArray();
    }

    public function readyForCompletion(Request $request)
    {
        $activeTab = $request->input('tab', 'pending');
        
        // TAB 1: Pending Completion Data
        $pendingQuery = DeliveryOrder::with([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'deliveryRequest.packages',
            'deliveryRequest.payment',
            'deliveryRequest.dropOffRegion',
            'deliveryRequest.pickUpRegion',
            'deliveryRequest.refunds',
            'driver'
        ])
        ->whereIn('status', ['delivered', 'needs_review'])
        ->latest();

        // Apply search filter for pending
        if ($request->search && $activeTab === 'pending') {
            $pendingQuery->where(function($q) use ($request) {
                $q->whereHas('deliveryRequest', function($q2) use ($request) {
                    $q2->where('reference_number', 'like', "%{$request->search}%")
                       ->orWhereHas('sender', function($q3) use ($request) {
                           $q3->where('name', 'like', "%{$request->search}%");
                       })
                       ->orWhereHas('receiver', function($q3) use ($request) {
                           $q3->where('name', 'like', "%{$request->search}%");
                       });
                })
                ->orWhereHas('driver', function($q2) use ($request) {
                    $q2->where('name', 'like', "%{$request->search}%");
                });
            });
        }
        
        // Apply status filter for pending
        if ($request->status && $activeTab === 'pending') {
            $pendingQuery->where('status', $request->status);
        }

        $pendingOrders = $pendingQuery->paginate(10, ['*'], 'pending_page');

        // TAB 2: Completed Orders Data
        $completedQuery = DeliveryOrder::with([
            'deliveryRequest.sender', 
            'deliveryRequest.receiver',
            'deliveryRequest.packages',
            'deliveryRequest.payment',
            'deliveryRequest.dropOffRegion',
            'deliveryRequest.pickUpRegion',
            'deliveryRequest.refunds',
            'driver'
        ])
        ->where('status', 'completed')
        ->latest();

        // Apply search filter for completed
        if ($request->search && $activeTab === 'completed') {
            $completedQuery->where(function($q) use ($request) {
                $q->whereHas('deliveryRequest', function($q2) use ($request) {
                    $q2->where('reference_number', 'like', "%{$request->search}%")
                       ->orWhereHas('sender', function($q3) use ($request) {
                           $q3->where('name', 'like', "%{$request->search}%");
                       })
                       ->orWhereHas('receiver', function($q3) use ($request) {
                           $q3->where('name', 'like', "%{$request->search}%");
                       });
                })
                ->orWhereHas('driver', function($q2) use ($request) {
                    $q2->where('name', 'like', "%{$request->search}%");
                });
            });
        }

        // Apply status filter for completed (though they're all completed, but for consistency)
        if ($request->status && $activeTab === 'completed') {
            $completedQuery->where('status', $request->status);
        }

        $completedOrders = $completedQuery->paginate(10, ['*'], 'completed_page');

        // Stats for badges
        $stats = [
            'pending_total' => DeliveryOrder::whereIn('status', ['delivered', 'needs_review'])->count(),
            'completed_total' => DeliveryOrder::where('status', 'completed')->count(),
            'needs_review_count' => DeliveryOrder::where('status', 'needs_review')->count(),
            'delivered_count' => DeliveryOrder::where('status', 'delivered')->count(),
        ];

        // Return with proper pagination structure for Inertia
        return Inertia::render('Admin/DeliveryCompletion/CompletionIndex', [
            'activeTab' => $activeTab,
            'pendingOrders' => [
                'data' => $pendingOrders->items(),
                'meta' => [
                    'current_page' => $pendingOrders->currentPage(),
                    'last_page' => $pendingOrders->lastPage(),
                    'per_page' => $pendingOrders->perPage(),
                    'total' => $pendingOrders->total(),
                    'from' => $pendingOrders->firstItem(),
                    'to' => $pendingOrders->lastItem(),
                ],
                'links' => [
                    'first' => $pendingOrders->url(1),
                    'last' => $pendingOrders->url($pendingOrders->lastPage()),
                    'prev' => $pendingOrders->previousPageUrl(),
                    'next' => $pendingOrders->nextPageUrl(),
                ]
            ],
            'completedOrders' => [
                'data' => $completedOrders->items(),
                'meta' => [
                    'current_page' => $completedOrders->currentPage(),
                    'last_page' => $completedOrders->lastPage(),
                    'per_page' => $completedOrders->perPage(),
                    'total' => $completedOrders->total(),
                    'from' => $completedOrders->firstItem(),
                    'to' => $completedOrders->lastItem(),
                ],
                'links' => [
                    'first' => $completedOrders->url(1),
                    'last' => $completedOrders->url($completedOrders->lastPage()),
                    'prev' => $completedOrders->previousPageUrl(),
                    'next' => $completedOrders->nextPageUrl(),
                ]
            ],
            'stats' => $stats,
            'filters' => $request->all(),
        ]);
    }
}
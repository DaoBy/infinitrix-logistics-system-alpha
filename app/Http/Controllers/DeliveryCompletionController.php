<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryRequest;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryCompletionController extends Controller
{
    public function completeDelivery(DeliveryOrder $order)
    {
        Log::info('[CompleteDelivery] Starting process for Order ID: ' . $order->id);

        try {
            DB::beginTransaction();

            $order->refresh();

            if (!$order->deliveryRequest()->exists()) {
                throw new \Exception("Delivery Request not found for order #{$order->id}");
            }

            $order->load([
                'deliveryRequest.packages',
                'truck',
                'driver'
            ]);

            if ($order->deliveryRequest->packages->isEmpty()) {
                throw new \Exception("No packages found for delivery request #{$order->deliveryRequest->id}");
            }

            // Update Delivery Order
            $order->update([
                'status' => 'completed',
                'actual_arrival' => now(),
                'completed_by' => auth()->id()
            ]);

            // --- Mark DeliveryRequest as completed ---
            if ($order->deliveryRequest) {
                $order->deliveryRequest->update([
                    'status' => 'completed'
                ]);
            }

            // Update Truck status if exists
            if ($order->truck) {
                $hasOtherOrders = DeliveryOrder::where('truck_id', $order->truck_id)
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                    ->exists();
                
                if (!$hasOtherOrders) {
                    $order->truck->update(['status' => 'available']);
                }
            }

            // Update all packages
            foreach ($order->deliveryRequest->packages as $package) {
                if ($package->status !== 'delivered') {
                    $package->updateStatus('delivered', auth()->user(), "Marked delivered via DO #{$order->id}");
                }
                
                $package->confirmDelivery(auth()->user(), "Completed via DO #{$order->id}");
                $package->updateStatus('completed', auth()->user(), "Completed via DO #{$order->id}");
            }

            // --- REMOVE payment creation logic here ---
            // Do NOT create or update payment records here.
            // Payment handling is managed in PaymentController only.

            DB::commit();

            return redirect()->route('cargo-assignments.index')
                ->with('success', "Delivery #{$order->id} completed successfully!");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[CompleteDelivery] Failed to complete order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', "Failed to complete delivery: {$e->getMessage()}");
        }
    }

    public function showReleaseForm(DeliveryOrder $order)
    {
        // Eager load all necessary relationships
        $order->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'deliveryRequest.packages',
            'deliveryRequest.payment',
            'deliveryRequest.pickUpRegion',
            'deliveryRequest.dropOffRegion',
            'driver',
            'truck'
        ]);

        // Log to verify data loading
        \Log::debug('Release Form Data:', [
            'order_id' => $order->id,
            'status' => $order->status,
            'has_delivery_request' => $order->deliveryRequest ? true : false,
            'package_count' => $order->deliveryRequest ? $order->deliveryRequest->packages->count() : 0
        ]);

        // Remap delivery_request to deliveryRequest for Vue
        $orderArr = $order->toArray();
        if (isset($orderArr['delivery_request'])) {
            $orderArr['deliveryRequest'] = $orderArr['delivery_request'];
            unset($orderArr['delivery_request']);
        }

        return inertia('Admin/CargoAssignment/Release', [
            'order' => $orderArr
        ]);
    }

    public function completeOrder(Request $request, DeliveryOrder $order)
    {
        $request->validate([
            'receiver_name' => 'required_if:is_postpaid,true|string|max:255',
            'receiver_contact' => 'nullable|string|max:50',
            'signature' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        // Status validation
        if ($order->status !== 'delivered') {
            return back()->with('error', 'Only delivered orders can be completed');
        }

        DB::transaction(function () use ($order, $request) {
            // Update order status
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'receiver_name' => $request->receiver_name,
                'receiver_contact' => $request->receiver_contact,
                'signature' => $request->signature,
                'notes' => $request->notes
            ]);

            // --- Mark DeliveryRequest as completed ---
            if ($order->deliveryRequest) {
                $order->deliveryRequest->update([
                    'status' => 'completed'
                ]);
            }

            // Update packages
            $order->deliveryRequest->packages()->each(function($package) {
                $package->update(['status' => 'completed']);
                $package->confirmDelivery(auth()->user(), 'Released via delivery completion');
            });

            // --- REMOVE payment creation logic here ---
            // Do NOT create or update payment records here.
            // Payment handling is managed in PaymentController only.
        });

        return redirect()->route('cargo-assignments.delivery-completion.ready-for-release')
            ->with('success', 'Delivery completed successfully');
    }

    public function readyForRelease(Request $request)
    {
        // Pending (delivered, not completed)
        $pendingQuery = \App\Models\DeliveryOrder::with([
                'deliveryRequest.sender',
                'deliveryRequest.receiver',
                'deliveryRequest.dropOffRegion',
                'deliveryRequest.drop_off_region',
                'deliveryRequest.payment'
            ])
            ->where('status', 'delivered')
            ->latest();

        // Completed (status = completed)
        $completedQuery = \App\Models\DeliveryOrder::with([
                'deliveryRequest.sender',
                'deliveryRequest.receiver',
                'deliveryRequest.dropOffRegion',
                'deliveryRequest.drop_off_region',
                'deliveryRequest.payment'
            ])
            ->where('status', 'completed')
            ->latest();

        // Apply filters to both queries
        if ($request->search) {
            $pendingQuery->where(function($query) use ($request) {
                $query
                    ->whereHas('deliveryRequest.sender', function($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%");
                    })
                    ->orWhereHas('deliveryRequest.receiver', function($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%");
                    })
                    ->orWhereHas('deliveryRequest', function($q) use ($request) {
                        $q->where('reference_number', 'like', "%{$request->search}%");
                    })
                    ->orWhere('id', 'like', "%{$request->search}%");
            });
            $completedQuery->where(function($query) use ($request) {
                $query
                    ->whereHas('deliveryRequest.sender', function($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%");
                    })
                    ->orWhereHas('deliveryRequest.receiver', function($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%");
                    })
                    ->orWhereHas('deliveryRequest', function($q) use ($request) {
                        $q->where('reference_number', 'like', "%{$request->search}%");
                    })
                    ->orWhere('id', 'like', "%{$request->search}%");
            });
        }

        if ($request->payment_type) {
            $pendingQuery->whereHas('deliveryRequest', function($q) use ($request) {
                $q->where('payment_type', $request->payment_type);
            });
            $completedQuery->whereHas('deliveryRequest', function($q) use ($request) {
                $q->where('payment_type', $request->payment_type);
            });
        }

        // Use different page parameters for each table
        $pendingPage = $request->input('page', 1);
        $completedPage = $request->input('completed_page', 1);

        return inertia('Admin/CargoAssignment/ReleaseIndex', [
            'orders' => $pendingQuery->paginate(5, ['*'], 'page', $pendingPage),
            'completedOrders' => $completedQuery->paginate(5, ['*'], 'completed_page', $completedPage),
            'filters' => $request->only(['search', 'payment_type'])
        ]);
    }
}
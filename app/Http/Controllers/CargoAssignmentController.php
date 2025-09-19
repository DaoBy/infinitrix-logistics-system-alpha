<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryRequest;
use App\Models\Truck;
use App\Models\User;
use App\Models\Package;
use App\Models\DriverRegionLog;
use App\Models\Region;
use App\Models\Waybill;
use App\Models\Report;
use App\Models\PackageTransfer;
use App\Models\PackageStatusHistory;
use App\Models\Manifest;
use App\Models\DriverTruckAssignment;
use App\Models\RegionTravelDuration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CargoAssignmentController extends Controller
{

    public function index()
    {
        $perPage = request('per_page', 5); // Default to 5 if not specified

        $query = DeliveryOrder::with([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'deliveryRequest.packages',
            'deliveryRequest.pickUpRegion',
            'deliveryRequest.dropOffRegion',
            'driver.employeeProfile',
            'truck',
            'currentRegion'
        ]);

        // Apply status filter if present
        if ($status = request('status')) {
            $query->where('status', $status);
        }

        // Apply search filter if present
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('do_number', 'like', "%{$search}%")
                  ->orWhereHas('deliveryRequest', function ($q2) use ($search) {
                      $q2->where('dr_number', 'like', "%{$search}%");
                  });
            });
        }

        // Apply region filter if present
        if ($regionId = request('region_id')) {
            $query->whereHas('deliveryRequest', function($q) use ($regionId) {
                $q->where('pick_up_region_id', $regionId);
            });
        }

        $deliveries = $query->latest()->paginate($perPage)->withQueryString();

        // Get driver-truck sets with capacity calculations
        $driverTruckSets = $this->getAvailableDriverTruckSets(request('region_id'));

        $regions = Region::where('is_active', true)->get();

        return Inertia::render('Admin/CargoAssignment/Index', [
            'deliveries' => $deliveries,
            'driverTruckSets' => $driverTruckSets,
            'regions' => $regions,
            'filters' => request()->all(['search', 'status', 'region_id'])
        ]);
    }

    /**
     * Get available driver-truck assignments with capacity information
     */
    protected function getAvailableDriverTruckSets(?int $regionId = null)
    {
        $sets = DriverTruckAssignment::with(['driver', 'truck', 'region'])
        ->active()
        ->when($regionId, function($q) use ($regionId) {
            $q->where('region_id', $regionId);
        })
        ->whereHas('truck', function($q) {
            $q->where('is_active', true)
              ->whereIn('status', [Truck::STATUS_AVAILABLE, Truck::STATUS_NEARLY_FULL]);
        })
        ->whereHas('driver', function($q) {
            $q->where('is_active', true);
        })
        ->get()
        ->map(function($assignment) {
            $truck = $assignment->truck;
            $driver = $assignment->driver;

            // Get ALL active orders (assigned, in_transit) for this set
            $activeOrders = DeliveryOrder::where('truck_id', $truck->id)
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['assigned', 'in_transit'])
                ->with(['deliveryRequest.packages'])
                ->get();

            $currentVolume = $activeOrders->sum(fn($order) => 
                $order->deliveryRequest->packages->sum('volume')
            );

            $currentWeight = $activeOrders->sum(fn($order) => 
                $order->deliveryRequest->packages->sum('weight')
            );

            $driver->canAcceptNewAssignment = $driver->canAcceptNewAssignment();
            $driver->available = $driver->isAvailable();
            $driver->current_assignments = $driver->deliveryOrders()
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                ->count();
            $driver->delivery_orders_count = $driver->current_assignments;
            $driver->last_assigned_at = $driver->last_assigned_at;

            // FIX: Do NOT require that there are no active orders, just check capacity and driver/truck availability
            $isAvailable = $driver->isAvailable() && $truck->isAvailable()
                && ($truck->volume_capacity > $currentVolume)
                && ($truck->weight_capacity > $currentWeight);

            return [
                'id' => $assignment->id,
                'driver' => $driver,
                'truck' => $assignment->truck,
                'region' => $assignment->region,
                'current_volume' => $currentVolume,
                'current_weight' => $currentWeight,
                'available_volume' => max(0, $truck->volume_capacity - $currentVolume),
                'available_weight' => max(0, $truck->weight_capacity - $currentWeight),
                'is_available' => $isAvailable,
                'active_orders' => $activeOrders,
                'region_match' => true // always true if filtered by region
            ];
        });

    // Only filter by is_available (not by having zero active orders)
    return $sets
        ->filter(function($set) {
            return $set['is_available'];
        })
        ->values();
    }

    /**
     * Create a new delivery order with common assignment logic
     * (Should only be used when actually creating a new DeliveryOrder, not for assignment)
     */
    private function createDeliveryOrder(DeliveryRequest $deliveryRequest, DriverTruckAssignment $assignment, string $estimatedDeparture): DeliveryOrder
    {
        $travelDuration = RegionTravelDuration::where([
            'from_region_id' => $deliveryRequest->pick_up_region_id,
            'to_region_id' => $deliveryRequest->drop_off_region_id
        ])->first();

        $estimatedArrival = $travelDuration 
            ? Carbon::parse($estimatedDeparture)->addMinutes((int) $travelDuration->estimated_minutes)
            : Carbon::parse($estimatedDeparture)->addHours((int) config('delivery.default_travel_duration_hours'));

        $order = DeliveryOrder::create([
            'delivery_request_id' => $deliveryRequest->id,
            'driver_id' => $assignment->driver_id,
            'truck_id' => $assignment->truck_id,
            'status' => 'assigned',
            'estimated_departure' => $estimatedDeparture,
            'estimated_arrival' => $estimatedArrival,
            'assigned_by' => auth()->id(),
            'current_region_id' => $deliveryRequest->pick_up_region_id
        ]);

        // Update package statuses
        $deliveryRequest->packages()->update([
            'status' => 'loaded',
            'current_region_id' => $deliveryRequest->pick_up_region_id
        ]);

        return $order;
    }

    public function assign(Request $request, DeliveryRequest $deliveryRequest)
    {
        $request->validate([
            'driver_truck_assignment_id' => 'required|exists:driver_truck_assignments,id',
            'estimated_departure' => 'required|date|after:now',
        ]);

        $assignment = \App\Models\DriverTruckAssignment::findOrFail($request->driver_truck_assignment_id);

        // Set driver's current region if not set
        if (!$assignment->driver->current_region_id) {
            $assignment->driver->current_region_id = $assignment->region_id;
            $assignment->driver->save();
        }

        // Allow assignment with pending_payment for postpaid
        $validStatuses = ['ready', 'assigned'];
        if ($deliveryRequest->payment_method === 'postpaid') {
            $validStatuses[] = 'pending_payment';
        }

        $order = DeliveryOrder::where('delivery_request_id', $deliveryRequest->id)
            ->whereIn('status', $validStatuses)
            ->first();

        if (!$order) {
            return back()->withErrors('This delivery order cannot be assigned or reassigned. It may already be dispatched or delivered.');
        }

        $isReassignment = $order->driver_truck_assignment_id && $order->driver_truck_assignment_id != $assignment->id;

        if (!$isReassignment && $order->driver_truck_assignment_id == $assignment->id) {
            return back()->withErrors('This delivery order is already assigned to the selected driver-truck set.');
        }

        // --- NEW VALIDATION: Check if all packages have stickers printed ---
        $unstickerizedPackages = $deliveryRequest->packages()
            ->whereNull('sticker_printed_at')
            ->get();

        if (!$unstickerizedPackages->isEmpty()) {
            $packageCodes = $unstickerizedPackages->pluck('item_code')->implode(', ');
            return back()->withErrors("Cannot assign delivery request. The following packages require stickers: {$packageCodes}");
        }
        // --- END NEW VALIDATION ---

        // --- Calculate estimated_arrival ---
        $deliveryRequest = $order->deliveryRequest;
        $travelDuration = \App\Models\RegionTravelDuration::where([
            'from_region_id' => $deliveryRequest->pick_up_region_id,
            'to_region_id' => $deliveryRequest->drop_off_region_id
        ])->first();

        $estimatedArrival = $travelDuration
            ? \Carbon\Carbon::parse($request->estimated_departure)->addMinutes((int) $travelDuration->estimated_minutes)
            : \Carbon\Carbon::parse($request->estimated_departure)->addHours((int) config('delivery.default_travel_duration_hours', 6));

        // --- Update assignment ---
        $order->update([
            'driver_id' => $assignment->driver_id,
            'truck_id' => $assignment->truck_id,
            'driver_truck_assignment_id' => $assignment->id,
            'estimated_departure' => $request->estimated_departure,
            'estimated_arrival' => $estimatedArrival, // <-- FIXED
            'status' => 'assigned',
        ]);

        if ($isReassignment) {
            // \Log::info("DeliveryOrder {$order->id} reassigned from set {$order->driver_truck_assignment_id} to {$assignment->id} by user " . auth()->id());
            return back()->with('success', 'Delivery order reassigned to a different driver-truck set.');
        }

        return back()->with('success', 'Delivery order assigned successfully.');
    }

    public function batchAssign(Request $request)
    {
        $request->validate([
            'delivery_request_ids' => 'required|array',
            'driver_truck_assignment_id' => 'required|exists:driver_truck_assignments,id',
            'estimated_departure' => 'required|date|after_or_equal:now'
        ]);

        $assignment = DriverTruckAssignment::find($request->driver_truck_assignment_id);

        // Allow batch assignment with pending_payment for postpaid
        $orders = DeliveryOrder::whereIn('delivery_request_id', $request->delivery_request_ids)
            ->whereIn('status', ['ready', 'assigned', 'pending_payment'])
            ->get()
            ->filter(function ($order) {
                $method = $order->deliveryRequest->payment_method;
                return $order->status !== 'pending_payment' || $method === 'postpaid';
            });

        if ($orders->isEmpty()) {
            return redirect()->back()->with('error', 'No ready or assignable delivery orders found for assignment. Please check the delivery order statuses.');
        }

        // --- NEW VALIDATION: Check for unstickerized packages in ALL selected orders ---
        $unstickerizedPackages = [];
        foreach ($orders as $order) {
            $packagesWithoutStickers = $order->deliveryRequest->packages()
                ->whereNull('sticker_printed_at')
                ->get();

            if (!$packagesWithoutStickers->isEmpty()) {
                // Collect info about which order has which unstickerized packages
                $unstickerizedPackages[] = [
                    'order_id' => $order->id,
                    'package_codes' => $packagesWithoutStickers->pluck('item_code')->implode(', ')
                ];
            }
        }

        if (!empty($unstickerizedPackages)) {
            $errorMessage = "Cannot assign batch. The following delivery orders have packages without stickers: ";
            $errorDetails = array_map(function($item) {
                return "DO-{$item['order_id']} (Packages: {$item['package_codes']})";
            }, $unstickerizedPackages);
            
            return redirect()->back()->with('error', $errorMessage . implode('; ', $errorDetails));
        }
        // --- END NEW VALIDATION ---

        DB::transaction(function() use ($assignment, $orders, $request) {
            // Set driver's current region if not set
            if (!$assignment->driver->current_region_id) {
                $assignment->driver->current_region_id = $assignment->region_id;
                $assignment->driver->save();
            }

            foreach ($orders as $order) {
                $deliveryRequest = $order->deliveryRequest;
                $travelDuration = \App\Models\RegionTravelDuration::where([
                    'from_region_id' => $deliveryRequest->pick_up_region_id,
                    'to_region_id' => $deliveryRequest->drop_off_region_id
                ])->first();

                $estimatedArrival = $travelDuration
                    ? \Carbon\Carbon::parse($request->estimated_departure)->addMinutes((int) $travelDuration->estimated_minutes)
                    : \Carbon\Carbon::parse($request->estimated_departure)->addHours((int) config('delivery.default_travel_duration_hours', 6));

                $order->update([
                    'driver_id' => $assignment->driver_id,
                    'truck_id' => $assignment->truck_id,
                    'driver_truck_assignment_id' => $assignment->id,
                    'status' => 'assigned',
                    'estimated_departure' => $request->estimated_departure,
                    'estimated_arrival' => $estimatedArrival, // <-- ADD THIS LINE
                    'assigned_by' => auth()->id(),
                    'current_region_id' => $order->deliveryRequest->pick_up_region_id,
                ]);
                // Update package statuses
                $order->deliveryRequest->packages()->update([
                    'status' => 'loaded',
                    'current_region_id' => $order->deliveryRequest->pick_up_region_id
                ]);
            }
            $assignment->truck->updateStatus();
            $assignment->driver->update(['last_assigned_at' => now()]);
        });

        return redirect()->back()->with('success', 'Batch assignment completed');
    }

    /**
     * Get suggested assignments based on region and capacity
     */
public function getSuggestedAssignments(Request $request)
{
    $request->validate([
        'region_id' => 'required|exists:regions,id'
    ]);

    // Only include delivery orders that are still assignable (status 'ready')
    $deliveries = DeliveryOrder::with([
        'deliveryRequest.packages',
        'deliveryRequest.dropOffRegion'
    ])
        ->whereHas('deliveryRequest', function($q) use ($request) {
            $q->where('pick_up_region_id', $request->input('region_id'));
        })
        ->where('status', 'ready') // Only unassigned/ready orders
        ->get()
        ->groupBy('deliveryRequest.drop_off_region_id');

    $driverTruckSets = $this->getAvailableDriverTruckSets($request->input('region_id'));

    return response()->json([
        'deliveries_by_destination' => $deliveries,
        'driver_truck_sets' => $driverTruckSets
    ]);
}

    /**
     * Validate potential assignment (API endpoint for frontend).
     */
    public function validateAssignment(Request $request)
    {
        $request->validate([
            'delivery_order_ids' => 'required|array',
            'driver_truck_assignment_id' => 'required|exists:driver_truck_assignments,id'
        ]);

        // Optionally accept estimated_departure for time validation
        $departureTime = $request->input('estimated_departure', null);

        $result = $this->validateAssignmentLogic(
            $request->delivery_order_ids,
            $request->driver_truck_assignment_id,
            $departureTime
        );

        return response()->json($result);
    }

    /**
     * Comprehensive assignment validation for Cargo Assignment module (internal logic).
     */
    protected function validateAssignmentLogic(array $deliveryOrderIds, int $assignmentId, ?string $departureTime = null): array
    {
        $assignment = \App\Models\DriverTruckAssignment::with(['driver', 'truck', 'region'])->findOrFail($assignmentId);
        $driver = $assignment->driver;
        $truck = $assignment->truck;
        $region = $assignment->region;

        $errors = [];
        $totalVolume = 0;
        $totalWeight = 0;
        $pickUpRegionId = null;
        $orderIdsChecked = [];
        $now = now();

        // 1. Driver Availability
        if (!$driver) {
            $errors[] = "Driver not found for this assignment.";
        } else {
            // Only consider 'dispatched' and 'in_transit' as "on a trip"
            $driverInProgress = $driver->deliveryOrders()
                ->whereIn('status', ['dispatched', 'in_transit'])
                ->exists();
            if ($driverInProgress) {
                $errors[] = "Driver is currently on a trip and cannot be assigned.";
            }
            if (!$driver->isActive()) {
                $errors[] = "Driver is not active.";
            }
        }

        // 2. Truck Availability
        if (!$truck) {
            $errors[] = "Truck not found for this assignment.";
        } else {
            if (!$truck->is_active || $truck->status === \App\Models\Truck::STATUS_MAINTENANCE) {
                $errors[] = "Truck is unavailable or currently in maintenance.";
            }
            if (in_array($truck->status, [\App\Models\Truck::STATUS_IN_TRANSIT, \App\Models\Truck::STATUS_ASSIGNED])) {
                $errors[] = "Truck is unavailable or currently in use.";
            }
            if ($region && $truck->region_id !== $region->id) {
                $errors[] = "Truck is not assigned to the selected region.";
            }
        }

        // 3. Gather and check all delivery orders
        $deliveryOrders = \App\Models\DeliveryOrder::with(['deliveryRequest.packages'])
            ->whereIn('id', $deliveryOrderIds)
            ->get();

        if ($deliveryOrders->isEmpty()) {
            $errors[] = "No valid delivery orders found for assignment.";
        }

        $seenPickupRegion = null;
        foreach ($deliveryOrders as $order) {
            $orderIdsChecked[] = $order->id;

            // 4. Delivery Order Status Check
            if ($order->status !== 'ready') {
                $errors[] = "Only delivery orders marked as 'Ready' can be assigned. DO-{$order->id} is not eligible.";
            }

            // 5. Region Consistency
            $orderPickupRegion = $order->deliveryRequest->pick_up_region_id ?? null;
            if ($seenPickupRegion === null) {
                $seenPickupRegion = $orderPickupRegion;
            } elseif ($orderPickupRegion !== $seenPickupRegion) {
                $errors[] = "Mismatch in pickup region between selected orders.";
            }
            $pickUpRegionId = $seenPickupRegion;

            // 6. Package Data Completeness
            $packages = $order->deliveryRequest->packages ?? collect();
            if ($packages->isEmpty()) {
                $errors[] = "Incomplete package info found for DO-{$order->id}. Please complete all package details first.";
            }
            foreach ($packages as $package) {
                if (
                    !$package->height || !$package->width || !$package->length ||
                    !$package->weight
                ) {
                    $errors[] = "Incomplete package info found for DO-{$order->id}. Please complete all package details first.";
                    break;
                }
            }

            // 7. Assignment Uniqueness
            if (in_array($order->status, ['assigned', 'dispatched', 'in_transit'])) {
                $errors[] = "Assignment conflict detected for DO-{$order->id}. Please refresh and try again.";
            }

            // --- NEW VALIDATION: Sticker Check ---
            $unstickerizedPackages = $order->deliveryRequest->packages->whereNull('sticker_printed_at');
            if (!$unstickerizedPackages->isEmpty()) {
                $packageCodes = $unstickerizedPackages->pluck('item_code')->implode(', ');
                $errors[] = "Delivery Order DO-{$order->id} has packages without stickers: {$packageCodes}";
            }
            // --- END NEW VALIDATION ---

            // 8. Calculate totals
            $totalVolume += $packages->sum('volume');
            $totalWeight += $packages->sum('weight');
        }

        // 9. Truck Capacity Check
        if ($truck) {
            $availableVolume = $truck->available_volume_capacity ?? ($truck->volume_capacity - ($truck->current_volume ?? 0));
            $availableWeight = $truck->available_weight_capacity ?? ($truck->weight_capacity - ($truck->current_weight ?? 0));
            if ($totalVolume > $availableVolume) {
                $errors[] = "Truck capacity exceeded. Please reduce packages or select a larger truck. (Volume)";
            }
            if ($totalWeight > $availableWeight) {
                $errors[] = "Truck capacity exceeded. Please reduce packages or select a larger truck. (Weight)";
            }
        } else {
            $availableVolume = 0;
            $availableWeight = 0;
        }

        // 10. Region Consistency (Driver-Truck Set vs Pickup)
        if ($region && $pickUpRegionId && $region->id !== $pickUpRegionId) {
            $errors[] = "Mismatch in pickup region between selected orders and driver-truck set.";
        }

        // 11. Departure Time Validation
        if ($departureTime) {
            $departure = \Carbon\Carbon::parse($departureTime);
            if ($departure->lt($now)) {
                $errors[] = "Invalid departure time. Must be a future time.";
            }
        }

        // 12. ETA Calculation Fallback (optional, not blocking)
        $etaWarning = null;
        if ($pickUpRegionId && $deliveryOrders->count() > 0) {
            $dropOffRegionId = $deliveryOrders->first()->deliveryRequest->drop_off_region_id ?? null;
            if ($dropOffRegionId) {
                $duration = \App\Models\RegionTravelDuration::where([
                    'from_region_id' => $pickUpRegionId,
                    'to_region_id' => $dropOffRegionId
                ])->first();
                if (!$duration) {
                    $etaWarning = "ETA cannot be calculated due to missing travel duration between selected regions.";
                }
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'eta_warning' => $etaWarning,
            'total_volume' => $totalVolume,
            'total_weight' => $totalWeight,
            'available_volume' => $availableVolume,
            'available_weight' => $availableWeight,
            'checked_order_ids' => $orderIdsChecked,
        ];
    }

    public function recordArrival(Request $request, DeliveryOrder $order)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
            'notes' => 'nullable|string',
            'all_for_set' => 'sometimes|boolean', // Optional: update all orders for the set
        ]);

        DB::transaction(function () use ($order, $request) {
            if ($request->boolean('all_for_set')) {
                // Update all in-transit/dispatched orders for this driver-truck set
                $orders = DeliveryOrder::where('driver_id', $order->driver_id)
                    ->where('truck_id', $order->truck_id)
                    ->whereIn('status', ['in_transit', 'dispatched'])
                    ->get();
            } else {
                // Only update the single order
                $orders = collect([$order]);
            }

            foreach ($orders as $do) {
                $do->recordRegionArrival($request->region_id);

                // Update actual_arrival if not set or region changed
                if (is_null($do->actual_arrival) || $do->current_region_id != $request->region_id) {
                    $do->update([
                        'actual_arrival' => now(),
                        'current_region_id' => $request->region_id,
                    ]);
                }

                // Update packages
                $do->deliveryRequest->packages()->each(function($package) use ($request) {
                    $package->update([
                        'current_region_id' => $request->region_id
                    ]);
                });
            }
        });

        return back()->with('success', 'Arrival recorded!');
    }

    /**
     * Complete a delivery order (for both prepaid and postpaid)
     */
    public function completeOrder(Request $request, DeliveryOrder $order)
    {
        $request->validate([
            'receiver_name' => 'required_if:is_postpaid,true|string|max:255',
            'receiver_id' => 'nullable|string|max:50',
            'signature' => 'nullable|string',
        ]);

        // Status validation
        if ($order->status !== 'delivered') {
            return back()->with('error', 'Only delivered orders can be completed');
        }

        // Payment validation for prepaid
        $isPostpaid = $order->deliveryRequest->payment_type === 'postpaid';
        
        if (!$isPostpaid) {
            $payment = $order->deliveryRequest->payment;
            if (!$payment || !$payment->verified_by) {
                return back()->with('error', 'Prepaid payment must be verified before completion');
            }
        }

        DB::transaction(function () use ($order, $request, $isPostpaid) {
            // Update order status
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);

            // Handle postpaid payment creation
            if ($isPostpaid) {
                \App\Models\Payment::create([
                    'delivery_order_id' => $order->id,
                    'delivery_request_id' => $order->delivery_request_id,
                    'type' => 'postpaid',
                    'method' => $order->deliveryRequest->payment_method,
                    'amount' => $order->deliveryRequest->total_price,
                    'collected_by' => auth()->id(),
                    'collected_at' => now(),
                ]);

                // Store receiver info
                if ($request->receiver_name) {
                    $order->update([
                        'receiver_name' => $request->receiver_name,
                        'receiver_id' => $request->receiver_id,
                        'signature' => $request->signature,
                    ]);
                }
            }

            // Update packages
            $order->deliveryRequest->packages()->update(['status' => 'completed']);
        });

        return redirect()->route('cargo-assignments.index')
            ->with('success', 'Delivery completed successfully');
    }

    public function cancelAssignment(DeliveryOrder $order)
    {
        DB::transaction(function () use ($order) {
            $previousStatus = $order->status;
            
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id()
            ]);

            // Revert resources
            if ($order->truck) {
                $order->truck->update(['status' => 'available']);
            }
            
            // Revert packages status based on previous state
            if ($previousStatus === 'assigned') {
                $status = 'ready_for_pickup';
            } elseif ($previousStatus === 'dispatched') {
                $status = 'loaded';
            } else {
                $status = 'preparing';
            }
            
            $order->deliveryRequest->packages()->each(function($package) use ($status) {
                $package->updateStatus($status, auth()->user(), 'Delivery cancelled');
            });
        });

        return back()->with('success', 'Assignment cancelled!');
    }

    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'deliveryRequest.packages.transfers',
            'deliveryRequest.packages.transfers.toRegion', 
            'dispatchedBy', 
            'deliveryRequest.pickUpRegion',      
            'deliveryRequest.dropOffRegion',     
            'driver.employeeProfile',
            'truck',
            'assignedBy',
            'currentRegion'
        ]);

        return Inertia::render('Admin/CargoAssignment/Show', [
            'order' => $deliveryOrder,
            'regions' => \App\Models\Region::all(),
        ]);
    }

    /**
     * Robust dispatch for a Driver-Truck Assignment.
     * Dispatches all assigned DeliveryOrders for the set, validates Manifest & Waybills, updates statuses, and logs actions.
     *
     * @param  int  $assignmentId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dispatchDriverTruckSet($assignmentId)
{
    $assignment = DriverTruckAssignment::with(['driver', 'truck'])->findOrFail($assignmentId);

    // Ensure driver has current region set
    if (!$assignment->driver->current_region_id) {
        $assignment->driver->current_region_id = $assignment->region_id;
        $assignment->driver->save();
    }

    // Get all assigned orders for this driver-truck set
    $orders = DeliveryOrder::where('driver_id', $assignment->driver_id)
        ->where('truck_id', $assignment->truck_id)
        ->where('status', 'assigned')
        ->with(['deliveryRequest.waybill', 'deliveryRequest.packages'])
        ->get();

    if ($orders->isEmpty()) {
        return back()->withErrors('No assigned delivery orders found for this driver-truck set.');
    }

    // Gather all package IDs for the current assigned orders
    $currentPackageIds = $orders->flatMap(function($order) {
        return $order->deliveryRequest->packages->pluck('id');
    })->unique()->values()->toArray();

    $manifest = Manifest::where('driver_id', $assignment->driver_id)
        ->where('truck_id', $assignment->truck_id)
        ->where('status', 'finalized')
        ->orderByDesc('id')
        ->first();

    // Improved manifest validation - check if ALL current packages are in manifest
    $manifestValid = false;
    if ($manifest && is_array($manifest->package_ids)) {
        $manifestPackageIds = array_map('intval', $manifest->package_ids);
        $currentIds = array_map('intval', $currentPackageIds);
        $manifestValid = empty(array_diff($currentIds, $manifestPackageIds));
    }

    if (!$manifestValid) {
        return back()->withErrors('Manifest not found or does not contain all assigned packages.');
    }

    // Validate Waybills for each order
    $ordersMissingWaybill = [];
    foreach ($orders as $order) {
        if (!$order->deliveryRequest->waybill) {
            $ordersMissingWaybill[] = $order->id;
        }
    }
        if (!empty($ordersMissingWaybill)) {
            return back()->withErrors('Cannot dispatch: The following orders are missing waybills: ' . implode(', ', $ordersMissingWaybill));
        }

        try {
            DB::transaction(function () use ($orders, $assignment) {
                // Update truck status to 'in_use'
                $assignment->truck->update(['status' => Truck::STATUS_IN_TRANSIT]);
                foreach ($orders as $order) {
                    $order->update([
                        'status' => 'in_transit',
                        'dispatched_at' => now(),
                        'dispatched_by' => auth()->id(),
                        'actual_departure' => now(),
                    ]);

                    // Log driver's departure from the current region
                    DriverRegionLog::create([
                        'driver_id' => $order->driver_id,
                        'region_id' => $order->current_region_id,
                        'delivery_order_id' => $order->id,
                        'type' => 'departure',
                        'logged_at' => now(),
                    ]);

                    // Update package statuses to 'in_transit'
                    if ($order->deliveryRequest && $order->deliveryRequest->packages) {
                        foreach ($order->deliveryRequest->packages as $package) {
                            $package->updateStatus('in_transit', auth()->user(), 'Order dispatched and en route');
                        }
                    }
                }

                // Optionally, update manifest status to 'finalized' (or 'dispatched' if you want to track this)
                // $manifest->update(['status' => 'dispatched']);
            });
        } catch (\Throwable $e) {
            // \Log::error('Dispatch DriverTruckSet failed', [
            //     'assignment_id' => $assignmentId,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString(),
            //     'user_id' => auth()->id(),
            // ]);

            // Return a JSON response if it's an AJAX/API call
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to dispatch Driver+Truck set: ' . $e->getMessage()
                ], 500);
            }

            // Otherwise, redirect back with error
            return back()->withErrors('Failed to dispatch Driver+Truck set: ' . $e->getMessage());
        }

        return back()->with('success', 'All assigned delivery orders for this driver-truck set have been dispatched!');
    }

    // Add this validation endpoint if not present:
    public function validateDispatchSet($assignmentId)
{
    $assignment = DriverTruckAssignment::with(['driver', 'truck'])->findOrFail($assignmentId);

    // Find all assigned orders for this set
    $orders = DeliveryOrder::where('driver_id', $assignment->driver_id)
        ->where('truck_id', $assignment->truck_id)
        ->where('status', 'assigned')
        ->with('deliveryRequest.waybill', 'deliveryRequest.packages')
        ->get();

    // If there are no assigned orders, cannot dispatch
    if ($orders->isEmpty()) {
        return response()->json([
            'has_manifest' => false,
            'missing_waybills' => [],
            'can_dispatch' => false,
            'message' => 'No assigned delivery orders for this driver-truck set.'
        ]);
    }

    // Gather all package IDs for the current assigned orders
    $currentPackageIds = $orders->flatMap(function($order) {
        return $order->deliveryRequest->packages->pluck('id');
    })->unique()->values()->toArray();

    // Find a finalized manifest for this truck/driver
    $manifest = \App\Models\Manifest::where('driver_id', $assignment->driver_id)
        ->where('truck_id', $assignment->truck_id)
        ->where('status', 'finalized')
        ->orderByDesc('id')
        ->first();

    // Check for waybills
    $ordersMissingWaybill = [];
    foreach ($orders as $order) {
        if (!$order->deliveryRequest->waybill) {
            $ordersMissingWaybill[] = $order->id;
        }
    }

    // Improved manifest validation
    $manifestValid = false;
    if ($manifest && is_array($manifest->package_ids)) {
        $manifestPackageIds = array_map('intval', $manifest->package_ids);
        $currentIds = array_map('intval', $currentPackageIds);
        
        // Check if ALL current packages are in the manifest (manifest can have more packages)
        $manifestValid = empty(array_diff($currentIds, $manifestPackageIds));
    }

    $canDispatch = $manifestValid && empty($ordersMissingWaybill);

    $message = '';
    if (!$manifest) {
        $message = 'No finalized manifest found for this driver-truck set.';
    } elseif (!$manifestValid) {
        $missingPackages = array_diff($currentPackageIds, $manifest->package_ids ?? []);
        $message = 'Manifest missing ' . count($missingPackages) . ' packages from current assignments.';
    } elseif (count($ordersMissingWaybill)) {
        $message = 'Missing waybills for orders: ' . implode(', ', $ordersMissingWaybill);
    } elseif ($canDispatch) {
        $message = 'All checks passed. Ready to dispatch this set.';
    }

    return response()->json([
        'has_manifest' => (bool)$manifest,
        'manifest_valid' => $manifestValid,
        'missing_waybills' => $ordersMissingWaybill,
        'can_dispatch' => $canDispatch,
        'message' => $message,
        'debug' => [ // Add debug info for troubleshooting
            'current_package_ids' => $currentPackageIds,
            'manifest_package_ids' => $manifest ? $manifest->package_ids : [],
            'manifest_id' => $manifest ? $manifest->id : null,
            'assignment_id' => $assignmentId
        ]
    ]);
}

    /**
     * Debug endpoint: Show assigned package IDs and manifest package IDs for a driver+truck set.
     * GET /cargo-assignments/debug-manifest/{assignmentId}
     */
    public function debugManifest($assignmentId)
    {
        $assignment = \App\Models\DriverTruckAssignment::with(['driver', 'truck'])->findOrFail($assignmentId);

        // Get all assigned orders for this driver-truck set
        $orders = \App\Models\DeliveryOrder::where('driver_id', $assignment->driver_id)
            ->where('truck_id', $assignment->truck_id)
            ->where('status', 'assigned')
            ->with(['deliveryRequest.packages'])
            ->get();

        $currentPackageIds = $orders->flatMap(function($order) {
            return $order->deliveryRequest->packages->pluck('id');
        })->unique()->values()->toArray();

        $manifest = \App\Models\Manifest::where('driver_id', $assignment->driver_id)
            ->where('truck_id', $assignment->truck_id)
            ->where('status', 'finalized')
            ->orderByDesc('id')
            ->first();

        $manifestPackageIds = $manifest && is_array($manifest->package_ids)
            ? array_map('intval', $manifest->package_ids)
            : [];

        return response()->json([
            'assignment_id' => $assignment->id,
            'driver_id' => $assignment->driver_id,
            'truck_id' => $assignment->truck_id,
            'current_assigned_package_ids' => $currentPackageIds,
            'manifest_id' => $manifest ? $manifest->id : null,
            'manifest_package_ids' => $manifestPackageIds,
            'manifest_status' => $manifest ? $manifest->status : null,
            'manifest_valid' => empty(array_diff($currentPackageIds, $manifestPackageIds)),
            'missing_packages' => array_diff($currentPackageIds, $manifestPackageIds),
            'orders' => $orders->pluck('id'),
        ]);
    }
    
}


<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Package;
use App\Models\Region;
use App\Models\User;
use App\Models\DriverRegionLog;
use App\Models\PackageTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Helpers\RouteHelper;

class DriverController extends Controller
{
    /**
     * Display the driver dashboard
     */
    public function dashboard()
    {
        $driver = auth()->user();
        
        return Inertia::render('Driver/Dashboard', [
            'stats' => $this->getDriverStats($driver),
            'activeDeliveries' => $this->getActiveDeliveries($driver),
            'recentDeliveries' => $this->getRecentDeliveries($driver),
            'currentTruck' => $this->getCurrentTruck($driver),
            'user' => $driver->only(['name', 'email']),
        ]);
    }

    /**
     * View for updating package statuses
     */
    public function statusUpdateView()
    {
        $driver = auth()->user();
        $activeAssignmentId = $driver->truckAssignments()
            ->where('is_active', true)
            ->latest()
            ->value('id');
        $activeAssignment = $activeAssignmentId
            ? \App\Models\DriverTruckAssignment::find($activeAssignmentId)
            : null;

        // Scope to current active delivery order for this assignment
        $hasPendingReturn = false;
        $canReturnToBase = false;
        if ($activeAssignment) {
            // Get the current active delivery order for this assignment
            $activeOrder = $driver->deliveryOrders()
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                ->latest()
                ->first();

            $deliveryOrderId = $activeOrder?->id;

            // Only consider logs for the current delivery order
            $recentReturnLog = null;
            if ($deliveryOrderId) {
                $recentReturnLog = $driver->regionLogs()
                    ->where('region_id', $activeAssignment->region_id)
                    ->where('delivery_order_id', $deliveryOrderId)
                    ->where('type', 'driver_returned')
                    ->latest()
                    ->first();
            }
            $hasPendingReturn = $recentReturnLog !== null;
            $canReturnToBase = !$hasPendingReturn;
        }

        // Get all packages assigned to the driver (across all active orders)
        $allDriverPackages = Package::whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
            $query->where('driver_id', $driver->id);
        })->get();

        $finalStatuses = ['delivered', 'completed', 'returned'];
        $allPackagesFinal = $allDriverPackages->count() > 0
            && $allDriverPackages->whereNotIn('status', $finalStatuses)->count() === 0;

        return Inertia::render('Driver/UpdateStatus', [
            'packages' => $this->getDriverPackages($driver),
            'regions' => $this->getAvailableRegions($driver),
            'statusOptions' => $this->getStatusOptions(),
            'canReturnToBase' => $canReturnToBase,
            'hasPendingReturn' => $hasPendingReturn,
            'activeAssignmentId' => $activeAssignmentId,
            'allPackagesFinal' => $allPackagesFinal,
        ]);
    }

    /**
     * View for assigned deliveries
     */
    public function assignedDeliveries()
    {
        $driver = auth()->user();
        
        return Inertia::render('Driver/AssignedDeliveries', [
            'deliveries' => $this->getActiveDeliveries($driver),
        ]);
    }

    /**
     * Track a specific package
     */
    public function trackPackage(Package $package)
    {
        $package->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'deliveryRequest.dropOffRegion',
            'currentRegion',
            'statusHistory' => function($query) {
                $query->with('updatedBy')->latest();
            },
            'transfers' => function($query) {
                $query->with(['fromRegion', 'toRegion', 'processor'])->latest();
            }
        ]);

        // DEBUG: Log the raw backend data for inspection
        \Log::debug('TRACK PACKAGE RAW DATA', [
            'package' => $package->toArray(),
            'status_history' => $package->statusHistory->toArray(),
            'transfers' => $package->transfers->toArray(),
        ]);

        return Inertia::render('Driver/PackageTracking', [
            'package' => [
                'id' => $package->id,
                'item_code' => $package->item_code,
                'item_name' => $package->item_name,
                'description' => $package->description,
                'photo_url' => $package->photo_url,
                'status' => $package->status,
                'sender' => $package->deliveryRequest->sender->name,
                'receiver' => $package->deliveryRequest->receiver->name,
                'destination' => $package->deliveryRequest->dropOffRegion->name,
                'current_region' => $package->currentRegion->name,
                // --- Add these fields for specifications ---
                'weight' => $package->weight,
                'value' => $package->value,
                'height' => $package->height,
                'width' => $package->width,
                'length' => $package->length,
                'volume' => $package->volume,
                'category' => $package->category,
                // ------------------------------------------------
            ],
            'statusHistory' => $package->statusHistory->map(function($history) {
                return [
                    'status' => $this->getStatusOptions()[$history->status] ?? $history->status,
                    'remarks' => $history->remarks,
                    'updated_at' => $history->updated_at->format('M d, Y H:i'),
                    'updated_by' => $history->updatedBy->name,
                ];
            }),
            'transfers' => $package->transfers->map(function($transfer) {
                return [
                    'from' => $transfer->fromRegion->name,
                    'to' => $transfer->toRegion->name,
                    'processed_at' => $transfer->transferred_at->format('M d, Y H:i'),
                    'processor' => $transfer->processor->name,
                    'remarks' => $transfer->remarks,
                ];
            }),
        ]);
    }

    /**
     * Bulk update status for multiple packages
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'package_ids' => 'required|array',
            'package_ids.*' => 'exists:packages,id',
            'status' => 'required|in:loaded,in_transit,delivered,returned',
            'remarks' => 'nullable|string|max:255',
            'region_id' => 'nullable|exists:regions,id'
        ]);

        $driver = auth()->user();
        $packages = $this->getValidPackages($validated['package_ids'], $driver);

        if ($packages->isEmpty()) {
            return back()->with('error', 'No valid packages selected');
        }

        $result = \DB::transaction(function () use ($packages, $validated, $driver) {
            $updatedDeliveryOrderIds = [];
            $autoDeliveredCount = 0;
            $autoReturnedCount = 0;
            
            foreach ($packages as $package) {
                $originalRegionId = $package->current_region_id;
                $finalStatus = $validated['status'];
                
                // Auto-set status if region is provided
                if (isset($validated['region_id'])) {
                    if (RouteHelper::isDestinationRegion($package, $validated['region_id'])) {
                        $finalStatus = 'delivered';
                        $autoDeliveredCount++;
                    } elseif ($package->shouldMarkAsReturn($validated['region_id'])) {
                        $finalStatus = 'returned';
                        $autoReturnedCount++;
                    }
                }
                
                // Update package status
                $this->updatePackageStatus($package, array_merge($validated, ['status' => $finalStatus]));
                
                // Handle region change if provided
                if (isset($validated['region_id']) && $validated['region_id'] != $originalRegionId) {
                    $this->handleRegionChange($package, $originalRegionId, $validated['region_id'], $driver, $validated['remarks'] ?? null);
                }
                
                // Handle delivery confirmation
                if ($finalStatus === 'delivered') {
                    $this->confirmDelivery($package, $validated['remarks']);
                    $updatedDeliveryOrderIds[] = $package->deliveryRequest->deliveryOrder->id;
                }
            }
            
            $this->checkAndUpdateDeliveryOrders($updatedDeliveryOrderIds);
            
            return [
                'total' => $packages->count(),
                'auto_delivered' => $autoDeliveredCount,
                'auto_returned' => $autoReturnedCount,
                'manual_updates' => $packages->count() - $autoDeliveredCount - $autoReturnedCount
            ];
        });

        $message = "Updated {$result['total']} packages";
        if ($result['auto_delivered'] > 0) {
            $message .= " ({$result['auto_delivered']} auto-delivered at destination)";
        }
        if ($result['auto_returned'] > 0) {
            $message .= " ({$result['auto_returned']} auto-returned to sender)";
        }

        return back()->with('success', $message);
    }

    /**
     * Update driver's current region with optional package updates
     */
    public function updateDriverRegion(Request $request)
    {
        $validated = $request->validate([
            'region_id' => 'required|exists:regions,id',
            'update_packages' => 'boolean',
            'only_in_transit' => 'boolean'
        ]);

        $driver = auth()->user();
        $newRegionId = $validated['region_id'];
        
        return DB::transaction(function () use ($driver, $newRegionId, $validated, $request) {
            // Log the region change
            $this->logDriverRegion($driver, $newRegionId);

            $response = ['message' => 'Location updated successfully'];

            if ($request->update_packages) {
                $query = $driver->deliveryOrders()
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                    ->with(['packages']);
                    
                if ($validated['only_in_transit']) {
                    $query->where('status', 'in_transit');
                }
                
                $orders = $query->get();
                $updatedPackages = 0;
                
                foreach ($orders as $order) {
                    $order->current_region_id = $newRegionId;
                    $order->save();
                    
                    foreach ($order->packages as $package) {
                        if (!$validated['only_in_transit'] || $package->status === 'in_transit') {
                            // Save previous region before updating
                            $fromRegionId = $package->current_region_id;
                            $package->current_region_id = $newRegionId;
                            $package->save();
                            $updatedPackages++;
                            
                            // Create transfer record
                            $package->transfers()->create([
                                'from_region_id' => $fromRegionId,
                                'to_region_id' => $newRegionId,
                                'processed_by' => $driver->id,
                                'remarks' => 'Updated with driver location change',
                                'is_return' => $package->shouldMarkAsReturn($newRegionId)
                            ]);

                            // --- AUTO-DELIVER LOGIC ---
                            // If the new region is the package's destination, mark as delivered
                            if (
                                $package->deliveryRequest &&
                                $package->deliveryRequest->drop_off_region_id == $newRegionId &&
                                $package->status !== 'delivered'
                            ) {
                                $package->updateStatus('delivered', $driver, 'Auto-delivered at destination via location update');
                                $package->confirmDelivery($driver, 'Auto-delivered at destination via location update');
                            }
                        }
                    }
                }
                
                $response['updated_packages'] = $updatedPackages;
            }
            
            return back()->with($response);
        });
    }

    /**
     * Find all region IDs along the route from start to destination using RegionTravelDuration as a graph.
     */
    private function getRouteRegions(int $fromRegionId, int $toRegionId): array
    {
        if ($fromRegionId === $toRegionId) {
            return [$fromRegionId];
        }

        $queue = [[$fromRegionId]];
        $visited = [$fromRegionId];

        while (!empty($queue)) {
            $path = array_shift($queue);
            $last = end($path);

            if ($last == $toRegionId) {
                return $path;
            }

            $nextRegions = \App\Models\RegionTravelDuration::where('from_region_id', $last)
                ->pluck('to_region_id')
                ->toArray();

            foreach ($nextRegions as $next) {
                if (!in_array($next, $visited)) {
                    $visited[] = $next;
                    $queue[] = array_merge($path, [$next]);
                }
            }
        }

        return []; // No path found
    }

    /**
     * Only regions relevant to the driver's current route
     */
    private function getAvailableRegions(User $driver = null)
    {
        if ($driver) {
            $activeOrder = $driver->deliveryOrders()
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                ->latest()
                ->first();

            if ($activeOrder) {
                $fromRegionId = $activeOrder->current_region_id ?? $activeOrder->deliveryRequest->pick_up_region_id;
                $toRegionId = $activeOrder->deliveryRequest->drop_off_region_id;
                $routeRegionIds = $this->getRouteRegions($fromRegionId, $toRegionId);

                return Region::active()
                    ->whereIn('id', $routeRegionIds)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        }

        return Region::active()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Packages with all necessary route information
     */
    private function getDriverPackages(User $driver)
    {
        return Package::with([
                'currentRegion',
                'deliveryRequest.dropOffRegion',
                'deliveryRequest.pickUpRegion',
                'deliveryRequest.deliveryOrder'
            ])
            ->whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id)
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
            })
            ->get()
            ->map(function ($package) {
                $currentStatus = $package->status;
                $isFinalStatus = in_array($currentStatus, ['delivered', 'returned', 'completed']);
                
                return [
                    'id' => $package->id,
                    'item_code' => $package->item_code,
                    'item_name' => $package->item_name,
                    'status' => $currentStatus,
                    'is_final_status' => $isFinalStatus,
                    'current_region' => [
                        'id' => $package->current_region_id,
                        'name' => $package->currentRegion->name,
                    ],
                    'deliveryRequest' => $package->deliveryRequest ? [
                        'drop_off_region_id' => $package->deliveryRequest->drop_off_region_id,
                        'pick_up_region_id' => $package->deliveryRequest->pick_up_region_id,
                        'dropOffRegion' => $package->deliveryRequest->dropOffRegion->name,
                        'pickUpRegion' => $package->deliveryRequest->pickUpRegion->name
                    ] : null
                ];
            });
    }

    /**
     * Handle all region change logic
     */
    private function handleRegionChange(Package $package, int $fromRegionId, int $toRegionId, User $driver, ?string $remarks = null): void
    {
        // Create package transfer record
        $transfer = $package->transfers()->create([
            'from_region_id' => $fromRegionId,
            'to_region_id' => $toRegionId,
            'processed_by' => $driver->id,
            'transferred_at' => now(),
            'is_return' => $package->shouldMarkAsReturn($toRegionId),
            'remarks' => $remarks
        ]);

        // Update package's current region
        $package->current_region_id = $toRegionId;
        $package->save();

        // Update delivery order's current region if this is the first package
        $deliveryOrder = $package->deliveryRequest->deliveryOrder;
        if ($deliveryOrder && !$deliveryOrder->current_region_id) {
            $deliveryOrder->current_region_id = $toRegionId;
            $deliveryOrder->save();
        }

        // Log driver's region change
        $this->logDriverRegion($driver, $toRegionId, $deliveryOrder);
    }

    /**
     * Log driver's region change
     */
  private function logDriverRegion(User $driver, int $regionId, ?DeliveryOrder $deliveryOrder = null): void
{
    $logData = [
        'driver_id' => $driver->id,
        'region_id' => $regionId,
        'type' => 'arrival',
        'logged_at' => now()
    ];

    if ($deliveryOrder) {
        $logData['delivery_order_id'] = $deliveryOrder->id;
    }

    DriverRegionLog::create($logData);

    $driver->current_region_id = $regionId;
    $driver->last_region_update = now();
    $driver->save();
}


    /**
     * Get packages that the driver is authorized to update
     */
    private function getValidPackages(array $packageIds, User $driver)
    {
        return Package::whereIn('id', $packageIds)
            ->whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id)
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
            })
            ->get();
    }

    /**
     * Update package status and create history record
     */
    private function updatePackageStatus(Package $package, array $data): void
    {
        $package->updateStatus(
            $data['status'],
            auth()->user(),
            $data['remarks'] ?? null
        );
    }

    /**
     * Confirm delivery for a package
     */
    private function confirmDelivery(Package $package, ?string $remarks = null): void
    {
        $package->confirmDelivery(auth()->user(), $remarks);
    }

    /**
     * Check and update delivery orders where all packages are delivered
     */
    private function checkAndUpdateDeliveryOrders(array $deliveryOrderIds): void
    {
        $uniqueOrderIds = array_unique($deliveryOrderIds);

        foreach ($uniqueOrderIds as $orderId) {
            $deliveryOrder = DeliveryOrder::with(['packages', 'deliveryRequest'])->find($orderId);

            \Log::debug('DELIVERYORDER DEBUG 1 - Checking for actual_arrival update', [
                'order_id' => $orderId,
                'found' => $deliveryOrder !== null,
                'package_count' => $deliveryOrder?->packages->count(),
                'package_ids' => $deliveryOrder?->packages->pluck('id')->toArray(),
                'package_statuses' => $deliveryOrder?->packages->pluck('status', 'id')->toArray(),
                'delivery_request_id' => $deliveryOrder?->delivery_request_id,
                'delivery_request_package_ids' => $deliveryOrder?->deliveryRequest?->packages->pluck('id')->toArray(),
            ]);

            if ($deliveryOrder && $deliveryOrder->packages->every(fn($pkg) => $pkg->status === 'delivered')) {
                $deliveryOrder->update([
                    'status' => 'delivered',
                    'actual_arrival' => now(),
                ]);
                \Log::debug('DELIVERYORDER DEBUG 2 - Updated actual_arrival for DeliveryOrder', [
                    'order_id' => $orderId,
                    'timestamp' => now()->toDateTimeString(),
                ]);
            } else {
                \Log::debug('DELIVERYORDER DEBUG 3 - Did NOT update actual_arrival for DeliveryOrder', [
                    'order_id' => $orderId,
                    'all_delivered' => $deliveryOrder ? $deliveryOrder->packages->every(fn($pkg) => $pkg->status === 'delivered') : null,
                ]);
            }
        }
    }

    // ====================
    // DASHBOARD HELPER METHODS
    // ====================

    private function getDriverStats(User $driver)
    {
        return [
            'active_deliveries' => DeliveryOrder::where('driver_id', $driver->id)
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                ->count(),
            // 'completed_today' => DeliveryOrder::where('driver_id', $driver->id)
            //     ->where('status', 'completed')
            //     ->whereDate('updated_at', today())
            //     ->count(),
            'packages_in_transit' => Package::whereHas('deliveryRequest.deliveryOrder', function ($q) use ($driver) {
                    $q->where('driver_id', $driver->id)
                        ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
                })
                ->count(),
        ];
    }

    private function getActiveDeliveries(User $driver)
    {
        return DeliveryOrder::with([
                'deliveryRequest.packages' => function($query) {
                    $query->select(['id', 'item_code', 'delivery_request_id']);
                },
                'deliveryRequest.receiver',
                'truck'
            ])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->get()
            ->map(function ($order) {
                return $this->formatDeliveryOrder($order);
            });
    }

    private function getRecentDeliveries(User $driver)
    {
        // Use paginate(5) for pagination support
        $paginator = DeliveryOrder::with(['deliveryRequest.packages', 'deliveryRequest.receiver'])
            ->where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->latest()
            ->paginate(5);

        // Map the data for each delivery order
        $paginator->getCollection()->transform(function ($order) {
            return $this->formatDeliveryOrder($order);
        });

        return $paginator;
    }

    private function getCurrentTruck(User $driver)
    {
        $order = DeliveryOrder::with('truck')
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->first();

        return $order?->truck ? [
            'make' => $order->truck->make,
            'model' => $order->truck->model,
            'license_plate' => $order->truck->license_plate,
            'status' => $order->truck->status,
        ] : null;
    }

    private function formatDeliveryOrder(DeliveryOrder $order)
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'estimated_arrival' => $order->estimated_arrival?->format('M d, Y H:i'),
            'package_count' => $order->deliveryRequest->packages->count(),
            'receiver' => $order->deliveryRequest->receiver->name,
            'reference_number' => $order->deliveryRequest->reference_number, // <-- Add this line
            'delivered_at' => $order->actual_arrival ? $order->actual_arrival->format('M d, Y H:i') : null, // Optional: for delivered_at column
            'packages' => $order->deliveryRequest->packages->take(2)->map(function ($package) {
                return [
                    'id' => $package->id,
                    'item_code' => $package->item_code,
                ];
            }),
            'truck' => $order->truck ? [
                'make' => $order->truck->make,
                'model' => $order->truck->model,
                'license_plate' => $order->truck->license_plate,
            ] : null,
        ];
    }

    /**
     * Show tracking for a delivery order (DeliveryTracking)
     */
    public function deliveryTracking(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load([
            'deliveryRequest.sender',
            'deliveryRequest.receiver',
            'deliveryRequest.dropOffRegion',
            'currentRegion',
            'deliveryRequest.packages.currentRegion',
            'regionLogs.fromRegion',
            'regionLogs.driver',
        ]);

        // Use status history from all packages in the delivery request
        $statusHistory = $deliveryOrder->deliveryRequest->statusHistory()
            ->with('updatedBy')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Driver/DeliveryTracking', [
            'delivery' => [
                'id' => $deliveryOrder->id,
                'reference_number' => $deliveryOrder->deliveryRequest->reference_number,
                'status' => $deliveryOrder->status,
                'sender' => $deliveryOrder->deliveryRequest->sender->name,
                'receiver' => $deliveryOrder->deliveryRequest->receiver->name,
                'destination' => $deliveryOrder->deliveryRequest->dropOffRegion->name,
                'current_region' => $deliveryOrder->currentRegion->name,
                'estimated_arrival' => $deliveryOrder->estimated_arrival,
                'actual_arrival' => $deliveryOrder->actual_arrival,
                'package_count' => $deliveryOrder->deliveryRequest->packages->count(),
            ],
            'statusHistory' => $statusHistory->map(function($history) {
                return [
                    'status' => $history->status,
                    'remarks' => $history->remarks,
                    'updated_at' => $history->updated_at,
                    'updated_by' => $history->updatedBy->name ?? 'System',
                ];
            }),
            'transfers' => $deliveryOrder->regionLogs->map(function($log) {
                return [
                    'from' => $log->fromRegion->name,
                    'to' => $log->fromRegion->name, // Use fromRegion for both
                    'processed_at' => $log->created_at,
                    'processor' => $log->driver->name,
                    'remarks' => $log->remarks,
                ];
            }),
            'packages' => $deliveryOrder->deliveryRequest->packages->map(function($package) {
                return [
                    'id' => $package->id,
                    'item_code' => $package->item_code,
                    'item_name' => $package->item_name,
                    'description' => $package->description,
                    'status' => $package->status,
                    'weight' => $package->weight,
                    'value' => $package->value,
                    'dimensions' => $package->height ? "{$package->height} × {$package->width} × {$package->length} cm" : null,
                    'current_region' => $package->currentRegion->name,
                ];
            }),
        ]);
    }

    /**
     * Returns available status options for package updates.
     */
    private function getStatusOptions()
    {
        return [
            'loaded' => 'Loaded',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'returned' => 'Returned to Sender Branch',
            // Add or adjust statuses as needed for your workflow
        ];
    }
}
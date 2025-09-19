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
use App\Services\RouteOptimizerService;

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

        // Get route data for the map
        $routeData = $this->getRouteData($driver);

        return Inertia::render('Driver/UpdateStatus', [
            'packages' => $this->getDriverPackages($driver),
            'regions' => $this->getAvailableRegions($driver),
            'statusOptions' => $this->getStatusOptions(),
            'canReturnToBase' => $canReturnToBase,
            'hasPendingReturn' => $hasPendingReturn,
            'activeAssignmentId' => $activeAssignmentId,
            'allPackagesFinal' => $allPackagesFinal,
            'routeData' => $routeData,
        ]);
    }

    /**
     * Get route data for the driver's current assignments
     */
    private function getRouteData(User $driver): array
    {
        $currentRegionId = $driver->current_region_id;
        $destinationRegionIds = $this->getDriverDestinationRegions($driver);

        if (!$currentRegionId || empty($destinationRegionIds)) {
            return [
                'current_region' => null,
                'route' => []
            ];
        }

        $optimizer = new RouteOptimizerService();
        $route = $optimizer->findOptimalRoute($currentRegionId, $destinationRegionIds);

        // Ensure current region is always first in the route
        $orderedRoute = array_unique(array_merge([$currentRegionId], $route));

        // Get full region details
        $regions = Region::whereIn('id', $orderedRoute)
            ->get(['id', 'name', 'latitude', 'longitude'])
            ->keyBy('id');

        $routeDetails = [];
        $previousRegionId = $currentRegionId;

        foreach ($orderedRoute as $index => $regionId) {
            if ($regionId === $previousRegionId && $index !== 0) continue;
            
            $region = $regions[$regionId] ?? null;
            $routeDetails[] = [
                'region' => $region ? [
                    'id' => $region->id,
                    'name' => $region->name,
                    'latitude' => $region->latitude,
                    'longitude' => $region->longitude
                ] : null,
                'estimated_minutes' => $index === 0 ? 0 : $optimizer->getTravelTime($previousRegionId, $regionId)
            ];
            $previousRegionId = $regionId;
        }

        return [
            'current_region' => $regions[$currentRegionId] ? [
                'id' => $regions[$currentRegionId]->id,
                'name' => $regions[$currentRegionId]->name,
                'latitude' => $regions[$currentRegionId]->latitude,
                'longitude' => $regions[$currentRegionId]->longitude
            ] : null,
            'route' => $routeDetails
        ];
    }

    /**
     * Get optimized route for driver
     */
    public function getOptimizedRoute(Request $request)
    {
        $driver = auth()->user();
        $currentRegionId = $driver->current_region_id;
        $destinationRegionIds = $this->getDriverDestinationRegions($driver);

        if (!$currentRegionId) {
            return response()->json([
                'success' => false,
                'message' => 'Driver has no current region assigned'
            ], 400);
        }

        try {
            $optimizer = new RouteOptimizerService();
            $routeData = $optimizer->getRouteDetails($currentRegionId, $destinationRegionIds);

            return response()->json([
                'success' => true,
                'data' => $routeData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Route calculation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get route with status for tracking
     */
    public function getRouteWithStatus()
    {
        $driver = auth()->user();
        $currentRegionId = $driver->current_region_id;
        
        if (!$currentRegionId) {
            return response()->json(['message' => 'Driver has no current region'], 400);
        }

        try {
            $optimizer = new RouteOptimizerService();
            $destinationRegionIds = $this->getDriverDestinationRegions($driver);
            
            if (empty($destinationRegionIds)) {
                return response()->json(['message' => 'No active deliveries with destinations'], 400);
            }

            $route = $optimizer->findOptimalRoute($currentRegionId, $destinationRegionIds);
            
            $visitedRegions = $driver->regionLogs()
                ->whereIn('region_id', $route)
                ->where('type', 'arrival')
                ->pluck('region_id')
                ->toArray();

            $regions = Region::whereIn('id', $route)
                ->get(['id', 'name', 'latitude', 'longitude'])
                ->map(function ($region) use ($currentRegionId, $visitedRegions, $route) {
                    $status = 'upcoming';
                    
                    if ($region->id == $currentRegionId) {
                        $status = 'current';
                    } elseif (in_array($region->id, $visitedRegions)) {
                        $status = 'visited';
                    }
                    
                    $position = array_search($region->id, $route);
                    $isNext = false;
                    
                    if ($status === 'current' && isset($route[$position + 1])) {
                        $isNext = true;
                    }
                    
                    return [
                        'id' => $region->id,
                        'name' => $region->name,
                        'latitude' => $region->latitude,
                        'longitude' => $region->longitude,
                        'status' => $status,
                        'isNext' => $isNext,
                        'position' => $position,
                    ];
                })
                ->sortBy('position')
                ->values();

            $routeWithTimes = [];
            $previousRegion = null;
            $totalTime = 0;
            
            foreach ($regions as $index => $region) {
                if ($previousRegion) {
                    $segmentTime = $optimizer->getTravelTime($previousRegion['id'], $region['id']);
                    $totalTime += $segmentTime;
                    
                    $region['eta_from_previous'] = $segmentTime;
                    $region['eta_from_start'] = $totalTime;
                } else {
                    $region['eta_from_previous'] = 0;
                    $region['eta_from_start'] = 0;
                }
                
                $routeWithTimes[] = $region;
                $previousRegion = $region;
            }

            return response()->json([
                'route' => $routeWithTimes,
                'current_region_id' => $currentRegionId,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get route status: ' . $e->getMessage()
            ], 500);
        }
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
                'weight' => $package->weight,
                'value' => $package->value,
                'height' => $package->height,
                'width' => $package->width,
                'length' => $package->length,
                'volume' => $package->volume,
                'category' => $package->category,
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
                
                if (isset($validated['region_id'])) {
                    if (RouteHelper::isDestinationRegion($package, $validated['region_id'])) {
                        $finalStatus = 'delivered';
                        $autoDeliveredCount++;
                    } elseif ($package->shouldMarkAsReturn($validated['region_id'])) {
                        $finalStatus = 'returned';
                        $autoReturnedCount++;
                    }
                }
                
                $this->updatePackageStatus($package, array_merge($validated, ['status' => $finalStatus]));
                
                if (isset($validated['region_id']) && $validated['region_id'] != $originalRegionId) {
                    $this->handleRegionChange($package, $originalRegionId, $validated['region_id'], $driver, $validated['remarks'] ?? null);
                }
                
                if ($finalStatus === 'delivered') {
                    $this->confirmDelivery($package, $validated['remarks']);
                    $updatedDeliveryOrderIds[] = $package->deliveryRequest->deliveryOrder->id;
                }
            }
            
            if ($this->allPackagesDelivered($driver)) {
                $driver->update(['all_packages_delivered' => true]);
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
            $this->logDriverRegion($driver, $newRegionId);

            $response = ['message' => 'Location updated successfully'];
            $shouldSetTruckReturning = false;

            if ($request->update_packages) {
                $query = $driver->deliveryOrders()
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                    ->with(['packages' => function($query) {
                        $query->with('deliveryRequest');
                    }]);
                    
                if ($validated['only_in_transit']) {
                    $query->where('status', 'in_transit');
                }
                
                $orders = $query->get();
                $updatedPackages = 0;
                $deliveredPackages = 0;
                
                foreach ($orders as $order) {
                    $order->current_region_id = $newRegionId;
                    $order->save();
                    
                    foreach ($order->packages as $package) {
                        if (!$validated['only_in_transit'] || $package->status === 'in_transit') {
                            if ($package->deliveryRequest->drop_off_region_id == $newRegionId) {
                                $package->current_region_id = $newRegionId;
                                $package->save();
                                
                                $package->updateStatus('delivered', $driver, 'Auto-delivered at destination via location update');
                                $package->confirmDelivery($driver, 'Auto-delivered at destination via location update');
                                $deliveredPackages++;
                            } else {
                                $fromRegionId = $package->current_region_id;
                                $package->current_region_id = $newRegionId;
                                $package->save();
                                $updatedPackages++;
                                
                                $package->transfers()->create([
                                    'from_region_id' => $fromRegionId,
                                    'to_region_id' => $newRegionId,
                                    'processed_by' => $driver->id,
                                    'remarks' => 'Updated with driver location change',
                                    'is_return' => $package->shouldMarkAsReturn($newRegionId)
                                ]);
                            }
                        }
                    }
                }
                
                $undeliveredPackages = Package::whereHas('deliveryRequest.deliveryOrder', function($q) use ($driver) {
                        $q->where('driver_id', $driver->id)
                          ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
                    })
                    ->whereNotIn('status', ['delivered', 'completed', 'returned'])
                    ->count();
                
                $shouldSetTruckReturning = ($undeliveredPackages === 0);
                
                $response['updated_packages'] = $updatedPackages;
                $response['delivered_packages'] = $deliveredPackages;
            }
            
            if ($shouldSetTruckReturning) {
                $assignment = $driver->currentTruckAssignment;
                if ($assignment && $assignment->truck) {
                    $assignment->truck->update(['status' => 'returning']);
                }
            }
            
            return back()->with($response);
        });
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
                    'to' => $log->fromRegion->name,
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
     * Mark arrival at a region
     */
    public function markArrival(Request $request)
    {
        $validated = $request->validate([
            'region_id' => 'required|exists:regions,id',
        ]);

        $driver = auth()->user();
        $regionId = $validated['region_id'];

        $this->logDriverRegion($driver, $regionId);

        return response()->json(['message' => 'Arrival recorded successfully']);
    }

    // ====================
    // HELPER METHODS
    // ====================

    /**
     * Get driver's destination regions from active packages
     */
    private function getDriverDestinationRegions(User $driver): array
    {
        $packages = Package::whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id)
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
            })
            ->whereNotIn('status', ['delivered', 'completed', 'returned'])
            ->with('deliveryRequest.dropOffRegion')
            ->get();

        $destinations = $packages->pluck('deliveryRequest.drop_off_region_id')
            ->unique()
            ->values()
            ->toArray();

        $pickups = $packages->pluck('deliveryRequest.pick_up_region_id')
            ->unique()
            ->values()
            ->toArray();

        return array_unique(array_merge($destinations, $pickups));
    }

    /**
     * Only regions relevant to the driver's current route
     */
    private function getAvailableRegions(User $driver = null)
    {
        if ($driver) {
            $currentRegionId = $driver->current_region_id;
            $destinationRegionIds = $this->getDriverDestinationRegions($driver);
            
            if ($currentRegionId && !empty($destinationRegionIds)) {
                $optimizer = new RouteOptimizerService();
                $route = $optimizer->findOptimalRoute($currentRegionId, $destinationRegionIds);

                $orderedRegions = [];
                foreach ($route as $regionId) {
                    if (!in_array($regionId, $orderedRegions)) {
                        $orderedRegions[] = $regionId;
                    }
                }

                return Region::whereIn('id', $orderedRegions)
                    ->active()
                    ->get(['id', 'name'])
                    ->sortBy(function ($region) use ($orderedRegions) {
                        return array_search($region->id, $orderedRegions);
                    })
                    ->values()
                    ->toArray();
            }
        }

        return Region::active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
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
        $transfer = $package->transfers()->create([
            'from_region_id' => $fromRegionId,
            'to_region_id' => $toRegionId,
            'processed_by' => $driver->id,
            'transferred_at' => now(),
            'is_return' => $package->shouldMarkAsReturn($toRegionId),
            'remarks' => $remarks
        ]);

        $package->current_region_id = $toRegionId;
        $package->save();

        $deliveryOrder = $package->deliveryRequest->deliveryOrder;
        if ($deliveryOrder && !$deliveryOrder->current_region_id) {
            $deliveryOrder->current_region_id = $toRegionId;
            $deliveryOrder->save();
        }

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
     * Check if all packages are delivered
     */
    private function allPackagesDelivered(User $driver): bool
    {
        return Package::whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id)
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
            })
            ->whereNotIn('status', ['delivered', 'completed'])
            ->doesntExist();
    }

    /**
     * Check and update delivery orders where all packages are delivered
     */
    private function checkAndUpdateDeliveryOrders(array $deliveryOrderIds): void
    {
        $uniqueOrderIds = array_unique($deliveryOrderIds);

        foreach ($uniqueOrderIds as $orderId) {
            $deliveryOrder = DeliveryOrder::with(['packages'])->find($orderId);

            if ($deliveryOrder && $deliveryOrder->packages->every(fn($pkg) => $pkg->status === 'delivered')) {
                $deliveryOrder->update([
                    'status' => 'delivered',
                    'actual_arrival' => now(),
                ]);
            }
        }
    }

    /**
     * Get driver statistics
     */
    private function getDriverStats(User $driver)
    {
        return [
            'active_deliveries' => DeliveryOrder::where('driver_id', $driver->id)
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                ->count(),
            'packages_in_transit' => Package::whereHas('deliveryRequest.deliveryOrder', function ($q) use ($driver) {
                    $q->where('driver_id', $driver->id)
                        ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
                })
                ->count(),
        ];
    }

    /**
     * Get active deliveries for driver
     */
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

    /**
     * Get recent deliveries for driver
     */
    private function getRecentDeliveries(User $driver)
    {
        $paginator = DeliveryOrder::with(['deliveryRequest.packages', 'deliveryRequest.receiver'])
            ->where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->latest()
            ->paginate(5);

        $paginator->getCollection()->transform(function ($order) {
            return $this->formatDeliveryOrder($order);
        });

        return $paginator;
    }

    /**
     * Get current truck assignment
     */
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

    /**
     * Format delivery order for response
     */
    private function formatDeliveryOrder(DeliveryOrder $order)
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'estimated_arrival' => $order->estimated_arrival?->format('M d, Y H:i'),
            'package_count' => $order->deliveryRequest->packages->count(),
            'receiver' => $order->deliveryRequest->receiver->name,
            'reference_number' => $order->deliveryRequest->reference_number,
            'delivered_at' => $order->actual_arrival ? $order->actual_arrival->format('M d, Y H:i') : null,
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
     * Returns available status options for package updates
     */
    private function getStatusOptions()
    {
        return [
            'loaded' => 'Loaded',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'returned' => 'Returned to Sender Branch',
        ];
    }
}
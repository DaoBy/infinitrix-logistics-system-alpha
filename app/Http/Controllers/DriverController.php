<?php

namespace App\Http\Controllers;

use App\Models\DriverStatusLog; 
use App\Models\DeliveryOrder;
use App\Models\Package;
use App\Models\Region;
use App\Models\User;
use App\Models\DriverRegionLog;
use App\Models\PackageTransfer;
use App\Models\DriverTruckAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Helpers\RouteHelper;
use App\Services\RouteOptimizerService;

class DriverController extends Controller
{
    public function dashboard()
    {
        $driver = auth()->user();
        
        $assignment = $driver->currentTruckAssignment;
        if ($assignment) {
            $assignment->processAutomaticTransitions();
        }
        
        return Inertia::render('Driver/Dashboard', [
            'stats' => array_merge($this->getDriverStats($driver), [
                'backhaul_eligible' => $driver->isEligibleForBackhaul() ?? false
            ]),
            'activeDeliveries' => $this->getActiveDeliveries($driver),
            'recentDeliveries' => $this->getRecentDeliveries($driver),
            'currentTruck' => $this->getCurrentTruck($driver),
            'user' => $driver->only(['name', 'email']),
            'backhaul_available' => $this->isBackhaulAvailable($driver),
            'cooldown_info' => $assignment ? [
                'in_cooldown' => $assignment->current_status === DriverTruckAssignment::STATUS_COOLDOWN,
                'cooldown_ends_at' => $assignment->cooldown_ends_at,
                'cooldown_finished' => $assignment->isCooldownFinished(),
                'is_final_cooldown' => $assignment->is_final_cooldown,
            ] : null,
        ]);
    }

    public function statusUpdateView()
    {
        $driver = auth()->user();
        
        $assignment = $driver->currentTruckAssignment;
        if ($assignment) {
            $assignment->processAutomaticTransitions();
        }
        
        $activeAssignmentId = $driver->truckAssignments()
            ->where('is_active', true)
            ->latest()
            ->value('id');
        $activeAssignment = $activeAssignmentId
            ? DriverTruckAssignment::with(['region', 'currentRegion'])->find($activeAssignmentId)
            : null;

        return Inertia::render('Driver/UpdateStatus', [
            'packages' => $this->getDriverPackages($driver),
            'regions' => $this->getAvailableRegions($driver),
            'statusOptions' => $this->getStatusOptions(),
            'activeAssignmentId' => $activeAssignmentId,
            'allPackagesFinal' => $this->allPackagesFinal($driver),
            'routeData' => $this->getRouteData($driver),
            'backhaulAvailable' => $activeAssignment ? $activeAssignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE : false,
            'currentAssignment' => $activeAssignment ? [
                'available_for_backhaul' => $activeAssignment->available_for_backhaul,
                'current_region_name' => $activeAssignment->currentRegion?->name,
                'home_region_name' => $activeAssignment->region?->name,
                'current_status' => $activeAssignment->current_status,
                'in_cooldown' => $assignment->current_status === DriverTruckAssignment::STATUS_COOLDOWN,
                'is_returning' => $assignment->current_status === DriverTruckAssignment::STATUS_RETURNING,
                'cooldown_ends_at' => $assignment->cooldown_ends_at,
                'backhaul_eligible_at' => $assignment->backhaul_eligible_at,
                'cooldown_finished' => $assignment->isCooldownFinished(),
                'is_final_cooldown' => $assignment->is_final_cooldown,
                'can_skip_cooldown' => $assignment->current_status === DriverTruckAssignment::STATUS_COOLDOWN && !$assignment->is_final_cooldown,
                'can_return_without_backhaul' => in_array($assignment->current_status, [
                    DriverTruckAssignment::STATUS_ACTIVE,
                    DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE,
                    DriverTruckAssignment::STATUS_COOLDOWN
                ]),
            ] : null,
            'currentLocation' => $driver->currentRegion?->name ?? 'Unknown',
            'currentRegionId' => $driver->current_region_id,
        ]);
    }

   public function skipCooldown()
{
    $driver = auth()->user();
    $assignment = $driver->currentTruckAssignment;

    \Log::info("🎯 CONTROLLER: Skip cooldown requested", [
        'driver_id' => $driver->id,
        'assignment_id' => $assignment?->id,
        'assignment_status' => $assignment?->current_status
    ]);

    if (!$assignment) {
        return back()->with('error', 'No active assignment found');
    }

    if ($assignment->current_status !== DriverTruckAssignment::STATUS_COOLDOWN) {
        return back()->with('error', 'Cannot skip cooldown from current status');
    }

    if ($assignment->is_final_cooldown) {
        return back()->with('error', 'Cannot skip final cooldown. Please complete the assignment.');
    }

    try {
        $previousStatus = $assignment->current_status;
        
        \Log::info("🔄 CONTROLLER: Calling skipCooldown method", [
            'assignment_id' => $assignment->id,
            'previous_status' => $previousStatus
        ]);
        
        $success = $assignment->skipCooldown();
        
        // Refresh the assignment to get the latest state
        $assignment->refresh();
        
        \Log::info("📋 CONTROLLER: After skipCooldown method", [
            'assignment_id' => $assignment->id,
            'method_return' => $success,
            'actual_status' => $assignment->current_status,
            'available_for_backhaul' => $assignment->available_for_backhaul
        ]);
        
        // Check if the status actually changed, even if method returned false
        $actuallySucceeded = ($assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE);
        
        if ($success || $actuallySucceeded) {
            // Only create log if not already created in the model
            if ($success) {
                DriverStatusLog::create([
                    'driver_truck_assignment_id' => $assignment->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $assignment->current_status,
                    'remarks' => "COOLDOWN SKIPPED: Driver opted to skip regular cooldown period. Status changed from {$previousStatus} to {$assignment->current_status}. Now eligible for backhaul assignments.",
                    'changed_at' => now()
                ]);
            }

            \Log::info('✅ CONTROLLER: Driver skipped cooldown successfully', [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment->id,
                'method_return' => $success,
                'actual_result' => $actuallySucceeded
            ]);
            
            return back()->with('success', 'Cooldown skipped! You are now eligible for backhaul assignments.');
        } else {
            \Log::warning('❌ CONTROLLER: skipCooldown failed', [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment->id,
                'method_return' => $success,
                'actual_status' => $assignment->current_status
            ]);
            return back()->with('error', 'Cannot skip cooldown at this time.');
        }
    } catch (\Exception $e) {
        \Log::error('💥 CONTROLLER: Exception in skipCooldown: ' . $e->getMessage());
        return back()->with('error', 'Failed to skip cooldown: ' . $e->getMessage());
    }
}

    public function returnWithoutBackhaul(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255'
        ]);
        
        $reason = $validated['reason'];
        $driver = auth()->user();
        $assignment = $driver->currentTruckAssignment;

        if (!$assignment) {
            \Log::error('Driver attempted return without backhaul but has no active assignment', [
                'driver_id' => $driver->id
            ]);
            throw new \Exception('No active assignment found');
        }

        $previousStatus = $assignment->current_status;
        
        if (!in_array($assignment->current_status, [
            DriverTruckAssignment::STATUS_ACTIVE, 
            DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE,
            DriverTruckAssignment::STATUS_COOLDOWN
        ])) {
            \Log::warning('Driver attempted return without backhaul from invalid status', [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment->id,
                'current_status' => $assignment->current_status
            ]);
            throw new \Exception('Cannot return without backhaul from current status: ' . $assignment->current_status);
        }
        
        if ($driver->current_region_id == $assignment->region_id) {
            $assignment->updateStatus(DriverTruckAssignment::STATUS_COOLDOWN, 'Driver already in home region - starting final cooldown (Option B)');
            $assignment->cooldown_ends_at = now()->addHour();
            $assignment->is_final_cooldown = true;
            $assignment->save();
            
            DriverStatusLog::create([
                'driver_truck_assignment_id' => $assignment->id,
                'previous_status' => $previousStatus,
                'new_status' => DriverTruckAssignment::STATUS_COOLDOWN,
                'remarks' => "RETURN WITHOUT BACKHAUL - FINAL COOLDOWN: Driver already in home region. Starting FINAL cooldown period ending at " . $assignment->cooldown_ends_at->format('Y-m-d H:i:s') . ". Reason: {$reason}",
                'changed_at' => now()
            ]);

            \Log::info('Driver initiated return without backhaul but already in home region - started final cooldown', [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment->id,
                'reason' => $reason
            ]);
            
            return;
        }

        $assignment->updateStatus(DriverTruckAssignment::STATUS_RETURNING, $reason . ' (Option B - No Backhaul)');
        
        DriverStatusLog::create([
            'driver_truck_assignment_id' => $assignment->id,
            'previous_status' => $previousStatus,
            'new_status' => DriverTruckAssignment::STATUS_RETURNING,
            'remarks' => "RETURN WITHOUT BACKHAUL INITIATED: Status changed from {$previousStatus} to RETURNING. Reason: {$reason}. Driver will return to home region without taking backhaul assignments.",
            'changed_at' => now()
        ]);

        DriverRegionLog::create([
            'driver_id' => $driver->id,
            'region_id' => $assignment->current_region_id,
            'type' => 'driver_returned',
            'remarks' => $reason . ' - Option B Return Initiated',
            'logged_at' => now()
        ]);

        if ($assignment->truck) {
            $assignment->truck->update(['status' => \App\Models\Truck::STATUS_RETURNING]);
            $assignment->truck->updateStatus();
        }

        \Log::info('Driver initiated return without backhaul', [
            'driver_id' => $driver->id,
            'assignment_id' => $assignment->id,
            'reason' => $reason,
            'previous_status' => $previousStatus
        ]);
    }

    public function confirmArrivalAtHome()
    {
        $driver = auth()->user();
        $assignment = $driver->currentTruckAssignment;

        if (!$assignment || $assignment->current_status !== DriverTruckAssignment::STATUS_RETURNING) {
            \Log::warning('Driver attempted to confirm arrival from invalid status', [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment?->id,
                'current_status' => $assignment?->current_status
            ]);
            return back()->with('error', 'Cannot confirm arrival from current status');
        }

        try {
            $driver->current_region_id = $assignment->region_id;
            $driver->save();

            $assignment->current_region_id = $assignment->region_id;
            $assignment->save();

            DriverRegionLog::create([
                'driver_id' => $driver->id,
                'region_id' => $assignment->region_id,
                'type' => 'arrival',
                'remarks' => 'Driver confirmed arrival at home region (Option B)',
                'logged_at' => now()
            ]);

            $assignment->completeAssignment();

            \Log::info('Driver confirmed arrival at home region', [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment->id,
                'home_region_id' => $assignment->region_id
            ]);

            return back()->with('success', 'Arrival confirmed! Final cooldown period started. Complete cooldown when ready.');
        } catch (\Exception $e) {
            \Log::error('Failed to confirm arrival: ' . $e->getMessage(), [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment->id
            ]);
            return back()->with('error', 'Failed to confirm arrival: ' . $e->getMessage());
        }
    }

    public function completeCooldown()
    {
        $driver = auth()->user();
        $assignment = $driver->currentTruckAssignment;

        if (!$assignment) {
            \Log::warning('Driver attempted to complete cooldown but has no active assignment', [
                'driver_id' => $driver->id
            ]);
            return back()->with('error', 'No active assignment found');
        }

        if ($assignment->current_status !== DriverTruckAssignment::STATUS_COOLDOWN) {
            \Log::warning('Driver attempted to complete cooldown from invalid status', [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment->id,
                'current_status' => $assignment->current_status
            ]);
            return back()->with('error', 'Assignment is not in cooldown period');
        }

        if (!$assignment->is_final_cooldown) {
            \Log::warning('Driver attempted to complete regular cooldown', [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment->id
            ]);
            return back()->with('error', 'Cannot complete regular cooldown. Please skip cooldown for backhaul or wait for automatic transition.');
        }

        try {
            $previousStatus = $assignment->current_status;
            $assignment->completeCooldown();
            
            DriverStatusLog::create([
                'driver_truck_assignment_id' => $assignment->id,
                'previous_status' => $previousStatus,
                'new_status' => DriverTruckAssignment::STATUS_COMPLETED,
                'remarks' => "ASSIGNMENT COMPLETED: Final cooldown period finished. Driver {$driver->name} and Truck {$assignment->truck->license_plate} assignment has been completed successfully. Both are now available for new assignments.",
                'changed_at' => now()
            ]);

            \Log::info('Driver completed final cooldown and assignment', [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment->id,
                'previous_status' => $previousStatus
            ]);
            
            return back()->with('success', 'Cooldown completed! Assignment finished. You are now available for new assignments.');
        } catch (\Exception $e) {
            \Log::error('Failed to complete cooldown: ' . $e->getMessage(), [
                'driver_id' => $driver->id,
                'assignment_id' => $assignment->id
            ]);
            return back()->with('error', 'Failed to complete cooldown: ' . $e->getMessage());
        }
    }

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

        $orderedRoute = array_unique(array_merge([$currentRegionId], $route));

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

    public function assignedDeliveries()
    {
        $driver = auth()->user();
        
        $readyForStatusUpdate = $this->getPackagesReadyForStatusUpdate($driver);
        
        return Inertia::render('Driver/AssignedDeliveries', [
            'deliveries' => $this->getActiveDeliveries($driver),
            'readyForStatusUpdate' => $readyForStatusUpdate,
        ]);
    }

    private function getPackagesReadyForStatusUpdate(User $driver)
{
    return Package::with([
            'currentRegion',
            'deliveryRequest.dropOffRegion',
            'deliveryRequest.deliveryOrder'
        ])
        ->whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
            $query->where('driver_id', $driver->id)
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
        })
        ->where('status', 'in_transit')
        ->whereHas('deliveryRequest', function($query) use ($driver) {
            $query->where('drop_off_region_id', $driver->current_region_id);
        })
        ->get()
        ->map(function ($package) {
            return [
                'id' => $package->id,
                'item_code' => $package->item_code,
                'item_name' => $package->item_name,
                'category' => $package->category, // Add this
                'weight' => $package->weight ? (float) $package->weight : null, // Add this - cast to float
                'volume' => $package->volume ? (float) $package->volume : null, // Add this - cast to float
                'status' => $package->status,
                'current_region' => [
                    'id' => $package->current_region_id,
                    'name' => $package->currentRegion->name,
                ],
                'deliveryRequest' => $package->deliveryRequest ? [
                    'drop_off_region_id' => $package->deliveryRequest->drop_off_region_id,
                    'dropOffRegion' => $package->deliveryRequest->dropOffRegion->name,
                    'reference_number' => $package->deliveryRequest->reference_number,
                ] : null,
                'deliveryOrder' => $package->deliveryRequest->deliveryOrder ? [
                    'id' => $package->deliveryRequest->deliveryOrder->id,
                ] : null
            ];
        });
}

   public function updateDestinationPackagesStatus(Request $request)
{
    $validated = $request->validate([
        'package_updates' => 'required|array',
        'package_updates.*.package_id' => 'required|exists:packages,id',
        'package_updates.*.status' => 'required|in:delivered,damaged_in_transit,lost_in_transit',
        'package_updates.*.remarks' => 'nullable|string|max:500',
        'package_updates.*.evidence' => 'nullable|array',
        'package_updates.*.evidence.*' => 'nullable|file|image|max:10240',
    ]);

    $driver = auth()->user();
    
    \Log::info("🎯 Updating destination packages status", [
        'driver_id' => $driver->id,
        'updates_count' => count($validated['package_updates'])
    ]);
    
    try {
        DB::transaction(function () use ($validated, $driver, $request) {
            foreach ($validated['package_updates'] as $index => $update) {
                // Load package with relationships
                $package = Package::with([
                    'deliveryRequest.deliveryOrder.driver',
                    'deliveryRequest.packages'
                ])->find($update['package_id']);
                
                if (!$package) {
                    \Log::warning('Package not found for update', [
                        'package_id' => $update['package_id']
                    ]);
                    continue;
                }
                
                \Log::info("📦 Processing package update", [
                    'package_id' => $package->id,
                    'new_status' => $update['status'],
                    'current_region_id' => $package->current_region_id,
                    'driver_region_id' => $driver->current_region_id
                ]);
                
                // Validate this package belongs to the driver and is at destination
                if (!$this->isValidDriverPackage($package, $driver)) {
                    \Log::warning('Invalid package for driver', [
                        'package_id' => $package->id,
                        'driver_id' => $driver->id
                    ]);
                    continue;
                }
                
                $evidencePaths = [];
                if ($request->hasFile("package_updates.{$index}.evidence")) {
                    foreach ($request->file("package_updates.{$index}.evidence") as $evidenceFile) {
                        $path = $evidenceFile->store(
                            "drivers/{$driver->id}/evidence", 
                            'public'
                        );
                        $evidencePaths[] = $path;
                    }
                }
                
                if (in_array($update['status'], ['damaged_in_transit', 'lost_in_transit'])) {
                    $package->reportIncident(
                        $update['status'],
                        $driver,
                        $update['remarks'] ?? 'Incident reported at destination',
                        $evidencePaths
                    );
                } else {
                    $package->updateStatus(
                        $update['status'], 
                        $driver, 
                        $update['remarks'] ?? 'Status updated at destination'
                    );
                }
                
                \Log::info("✅ Package status updated", [
                    'package_id' => $package->id,
                    'new_status' => $update['status']
                ]);
            }
        });

        \Log::info("🎉 All package updates completed successfully");
        return back()->with('success', 'Package statuses updated successfully!');

    } catch (\Exception $e) {
        \Log::error('💥 Failed to update package statuses: ' . $e->getMessage(), [
            'driver_id' => $driver->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()->with('error', 'Failed to update package statuses: ' . $e->getMessage());
    }
}

    private function isValidDriverPackage(?Package $package, User $driver): bool
    {
        if (!$package) return false;
        
        $isDriverPackage = Package::where('id', $package->id)
            ->whereHas('deliveryRequest.deliveryOrder', function($query) use ($driver) {
                $query->where('driver_id', $driver->id)
                      ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
            })
            ->exists();
            
        if (!$isDriverPackage) return false;
        
        $isAtDestination = $package->deliveryRequest && 
                          $package->deliveryRequest->drop_off_region_id == $driver->current_region_id;
                          
        if (!$isAtDestination) return false;
        
        return $package->status === 'in_transit';
    }

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

        $result = DB::transaction(function () use ($packages, $validated, $driver) {
            $updatedDeliveryOrderIds = [];
            $autoDeliveredCount = 0;
            $autoReturnedCount = 0;
            $cooldownStarted = false;
            
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
            
            $assignment = $driver->currentTruckAssignment;
            if ($assignment) {
                if ($this->allPackagesFinal($driver)) {
                    $assignment->completeDeliveries();
                    $cooldownStarted = ($assignment->current_status === DriverTruckAssignment::STATUS_COOLDOWN);
                }
            }
            
            $this->checkAndUpdateAllDeliveryOrders($driver);
            
            return [
                'total' => $packages->count(),
                'auto_delivered' => $autoDeliveredCount,
                'auto_returned' => $autoReturnedCount,
                'manual_updates' => $packages->count() - $autoDeliveredCount - $autoReturnedCount,
                'cooldown_started' => $cooldownStarted
            ];
        });

        $message = "Updated {$result['total']} packages";
        if ($result['auto_delivered'] > 0) {
            $message .= " ({$result['auto_delivered']} auto-delivered at destination)";
        }
        if ($result['auto_returned'] > 0) {
            $message .= " ({$result['auto_returned']} auto-returned to sender)";
        }
        if ($result['cooldown_started']) {
            $assignment = $driver->currentTruckAssignment;
            $isFinal = $assignment ? $assignment->is_final_cooldown : false;
            
            if ($isFinal) {
                $message .= ". All packages delivered! Final cooldown period started. Complete cooldown to finish assignment.";
            } else {
                $message .= ". All packages delivered! Cooldown period started. Choose your next action during cooldown.";
            }
        }

        return back()->with('success', $message);
    }

    public function updateDriverRegion(Request $request)
    {
        $validated = $request->validate([
            'region_id' => 'required|exists:regions,id',
            'update_packages' => 'boolean',
            'only_in_transit' => 'boolean'
        ]);

        $driver = auth()->user();
        $newRegionId = $validated['region_id'];
        $assignment = $driver->currentTruckAssignment;

        try {
            $oldRegionId = $driver->current_region_id;
            $oldRegionName = $driver->currentRegion->name ?? 'Unknown';
            $newRegionName = Region::find($newRegionId)->name ?? 'Unknown';
            
            $this->logDriverRegion($driver, $newRegionId);

            if ($assignment && $oldRegionId != $newRegionId) {
                DriverStatusLog::create([
                    'driver_truck_assignment_id' => $assignment->id,
                    'previous_status' => $assignment->current_status,
                    'new_status' => $assignment->current_status,
                    'remarks' => "LOCATION CHANGE: Driver moved from {$oldRegionName} (Region ID: {$oldRegionId}) to {$newRegionName} (Region ID: {$newRegionId}). All associated packages and delivery orders updated to new region.",
                    'changed_at' => now()
                ]);
            }

            if ($assignment && 
                $assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE && 
                $newRegionId == $assignment->region_id && 
                $assignment->allPackagesDelivered()) {

                $assignment->updateStatus(
                    DriverTruckAssignment::STATUS_COOLDOWN,
                    'Backhaul driver arrived at home region - FINAL cooldown'
                );
                $assignment->cooldown_ends_at = now()->addMinutes(30);
                $assignment->is_final_cooldown = true;
                $assignment->save();
                
                DriverStatusLog::create([
                    'driver_truck_assignment_id' => $assignment->id,
                    'previous_status' => DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE,
                    'new_status' => DriverTruckAssignment::STATUS_COOLDOWN,
                    'remarks' => "FINAL COOLDOWN TRIGGERED: Backhaul driver arrived at home region {$newRegionName}. All packages delivered. Starting FINAL cooldown period ending at " . $assignment->cooldown_ends_at->format('Y-m-d H:i:s'),
                    'changed_at' => now()
                ]);
            }

            $response = ['message' => 'Location updated successfully'];
            if ($validated['update_packages'] ?? true) {
                $this->updatePackagesWithDriver($driver, $newRegionId, $validated);
            }

            if ($assignment) {
                $assignment->processAutomaticTransitions();
            }

            return back()->with($response);

        } catch (\Exception $e) {
            return back()->with('error', 'Location update failed: ' . $e->getMessage());
        }
    }

    private function updatePackagesWithDriver($driver, $newRegionId, $validated)
    {
        $orders = $driver->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'needs_review'])
            ->with(['packages' => function($query) use ($validated) {
                $query->with(['deliveryRequest', 'currentRegion']);
                if ($validated['only_in_transit'] ?? false) {
                    $query->where('status', 'in_transit');
                }
            }])
            ->get();

        foreach ($orders as $order) {
            $oldRegionId = $order->current_region_id;
            $order->current_region_id = $newRegionId;
            $order->save();
            
            foreach ($order->packages as $package) {
                $oldPackageRegionId = $package->current_region_id;
                $package->current_region_id = $newRegionId;
                $package->save();
                
                if ($oldPackageRegionId != $newRegionId && 
                    !in_array($package->status, ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit'])) {
                    
                    $package->updateStatus('in_transit', $driver, "Moving from " . 
                        ($package->currentRegion->name ?? 'Unknown') . " to " . 
                        (Region::find($newRegionId)->name ?? 'Unknown'));
                }
            }
        }
    }

   

    public function markArrival(Request $request)
    {
        $validated = $request->validate([
            'region_id' => 'required|exists:regions,id',
        ]);

        $driver = auth()->user();
        $regionId = $validated['region_id'];

        $this->logDriverRegion($driver, $regionId);

        $assignment = $driver->currentTruckAssignment;
        if ($assignment) {
            $assignment->processAutomaticTransitions();
        }

        return response()->json(['message' => 'Arrival recorded successfully']);
    }

    private function allPackagesFinal(User $driver): bool
    {
        $undeliveredPackages = Package::whereHas('deliveryRequest.deliveryOrder', function($q) use ($driver) {
                $q->where('driver_id', $driver->id)
                  ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'needs_review']);
            })
            ->whereNotIn('status', ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit'])
            ->count();

        return $undeliveredPackages === 0;
    }

    private function getDriverDestinationRegions(User $driver): array
    {
        $packages = Package::whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id)
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'needs_review']);
            })
            ->whereNotIn('status', ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit'])
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
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'needs_review']);
        })
        ->whereNotIn('status', ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit'])
        ->get()
        ->map(function ($package) {
                $currentStatus = $package->status;
                $isFinalStatus = in_array($currentStatus, ['delivered', 'returned', 'completed', 'damaged_in_transit', 'lost_in_transit']);
                
                return [
                    'id' => $package->id,
                    'item_code' => $package->item_code,
                    'item_name' => $package->item_name,
                    'category' => $package->category, // Add this
                    'weight' => $package->weight ? (float) $package->weight : null, // Add this
                    'volume' => $package->volume ? (float) $package->volume : null, // Add this
                    'status' => $currentStatus,
                    'is_final_status' => $isFinalStatus,
                    'verified_at' => $package->verified_at,
                    'verification_status' => $package->verification_status,
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

    private function getDriverStats(User $driver)
    {
        $activeDeliveries = $driver->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'needs_review'])
            ->whereHas('deliveryRequest.packages', function($query) {
                $query->whereNotIn('status', ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit']);
            })
            ->count();

        $totalPackages = Package::whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id)
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'needs_review']);
            })
            ->whereNotIn('status', ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit'])
            ->count();

            $deliveredPackages = Package::whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
                    $query->where('driver_id', $driver->id);
                })
                ->where('status', 'delivered')
                ->count();

            $pendingVerification = Package::whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
                    $query->where('driver_id', $driver->id);
                })
                ->where('verification_status', 'pending')
                ->count();

            return [
                'active_deliveries' => $activeDeliveries,
                'total_packages' => $totalPackages,
                'delivered_packages' => $deliveredPackages,
                'pending_verification' => $pendingVerification,
            ];
        }

    private function getActiveDeliveries(User $driver)
{
    return $driver->deliveryOrders()
        ->with([
            'deliveryRequest.receiver',
            'deliveryRequest.packages' => function($query) {
                $query->whereNotIn('status', ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit']);
            },
            'deliveryRequest.packages.currentRegion', // ✅ ADD THIS
            'deliveryRequest.packages.deliveryRequest.dropOffRegion', // ✅ ADD THIS
            'deliveryRequest.dropOffRegion', // ✅ ADD THIS
            'deliveryRequest.pickUpRegion', // ✅ ADD THIS
            'truck',
            'currentRegion'
        ])
        ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
        ->whereHas('deliveryRequest.packages', function($query) {
            $query->whereNotIn('status', ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit']);
        })
        ->get()
        ->map(function ($deliveryOrder) {
            $packages = $deliveryOrder->deliveryRequest->packages ?? collect([]);
            
            return [
                'id' => $deliveryOrder->id,
                'reference_number' => $deliveryOrder->deliveryRequest->reference_number,
                'status' => $deliveryOrder->status,
                'receiver' => $deliveryOrder->deliveryRequest->receiver->name,
                'destination' => $deliveryOrder->deliveryRequest->dropOffRegion->name ?? 'Unknown',
                'pickUpRegion' => $deliveryOrder->deliveryRequest->pickUpRegion->name ?? 'Unknown', // ✅ ADD THIS
                'dropOffRegion' => $deliveryOrder->deliveryRequest->dropOffRegion->name ?? 'Unknown', // ✅ ADD THIS
                'truck' => $deliveryOrder->truck ? [
                    'make' => $deliveryOrder->truck->make,
                    'model' => $deliveryOrder->truck->model,
                    'license_plate' => $deliveryOrder->truck->license_plate,
                ] : null,
                'packages' => $packages->take(2)->map(function ($package) {
                    return [
                        'id' => $package->id,
                        'item_code' => $package->item_code,
                        'item_name' => $package->item_name, // ✅ ADD THIS
                        'category' => $package->category, // ✅ ADD THIS
                        'weight' => $package->weight ? (float) $package->weight : null, // ✅ ADD THIS
                        'volume' => $package->volume ? (float) $package->volume : null, // ✅ ADD THIS
                        'current_region' => [ // ✅ ADD THIS
                            'id' => $package->current_region_id,
                            'name' => $package->currentRegion->name ?? 'Unknown',
                        ],
                        'deliveryRequest' => $package->deliveryRequest ? [ // ✅ ADD THIS
                            'drop_off_region_id' => $package->deliveryRequest->drop_off_region_id,
                            'dropOffRegion' => $package->deliveryRequest->dropOffRegion->name ?? 'Unknown',
                        ] : null
                    ];
                }),
                'package_count' => $packages->count(),
                'verified_packages' => $packages->whereNotNull('verified_at')->count(),
                'current_region' => $deliveryOrder->currentRegion->name ?? 'Unknown',
                'is_backhaul' => $deliveryOrder->isBackhaulAssignment(),
                'estimated_arrival' => $deliveryOrder->estimated_arrival?->format('M d, Y H:i'),
                'actual_arrival' => $deliveryOrder->actual_arrival?->format('M d, Y H:i'),
                'dispatched_at' => $deliveryOrder->dispatched_at?->format('M d, Y H:i'),
            ];
        });
}
    private function getRecentDeliveries(User $driver)
    {
        return $driver->deliveryOrders()
            ->with([
                'deliveryRequest.receiver',
                'deliveryRequest.dropOffRegion',
                'deliveryRequest.packages'
            ])
            ->whereIn('status', ['completed', 'delivered', 'needs_review'])
            ->whereHas('deliveryRequest.packages', function($query) {
                $query->whereIn('status', ['delivered', 'completed', 'damaged_in_transit', 'lost_in_transit']);
            })
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'reference_number' => $order->deliveryRequest->reference_number,
                        'sender' => $order->deliveryRequest->sender->name ?? 'Unknown',
                        'receiver' => $order->deliveryRequest->receiver->name,
                        'destination' => $order->deliveryRequest->dropOffRegion->name,
                        'status' => $order->status,
                        'estimated_arrival' => $order->estimated_arrival?->format('M d, Y H:i'),
                        'actual_arrival' => $order->actual_arrival?->format('M d, Y H:i'),
                        'package_count' => $order->deliveryRequest->packages->count(),
                    ];
                });
    }

    private function getCurrentTruck(User $driver)
    {
        $assignment = $driver->currentTruckAssignment;
        
        if (!$assignment || !$assignment->truck) {
            return null;
        }

        return [
            'make' => $assignment->truck->make ?? 'Unknown',
            'model' => $assignment->truck->model ?? 'Unknown', 
            'license_plate' => $assignment->truck->license_plate ?? 'Unknown',
            'status' => $assignment->truck->status ?? 'unknown',
        ];
    }

    private function isBackhaulAvailable(User $driver)
    {
        $assignment = $driver->currentTruckAssignment;
        return $assignment && $assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE;
    }

    private function getStatusOptions()
    {
        return [
            'loaded' => 'Loaded',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'returned' => 'Returned',
        ];
    }

    private function getValidPackages(array $packageIds, User $driver)
    {
        return Package::whereIn('id', $packageIds)
            ->whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
                $query->where('driver_id', $driver->id)
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'needs_review']);
            })
            ->get();
    }

    private function updatePackageStatus(Package $package, array $data)
    {
        $package->updateStatus($data['status'], auth()->user(), $data['remarks'] ?? null);
        
        if (isset($data['region_id'])) {
            $package->current_region_id = $data['region_id'];
            $package->save();
        }
    }

    private function handleRegionChange(Package $package, $fromRegionId, $toRegionId, User $driver, $remarks = null)
    {
        PackageTransfer::create([
            'package_id' => $package->id,
            'from_region_id' => $fromRegionId,
            'to_region_id' => $toRegionId,
            'processed_by' => $driver->id,
            'remarks' => $remarks ?? 'Region transfer via status update',
            'is_return' => $package->shouldMarkAsReturn($toRegionId)
        ]);
    }

    private function confirmDelivery(Package $package, $remarks = null)
    {
        $package->confirmDelivery(auth()->user(), $remarks);
    }

    private function checkAndUpdateAllDeliveryOrders(User $driver): void
    {
        $orders = $driver->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'needs_review'])
            ->with(['deliveryRequest.packages'])
            ->get();

        foreach ($orders as $order) {
            $this->checkAndUpdateDeliveryOrder($order);
        }

        $assignment = $driver->currentTruckAssignment;
        if ($assignment) {
            $assignment->processAutomaticTransitions();
        }
    }

    private function checkAndUpdateDeliveryOrder(DeliveryOrder $order): void
    {
        $undeliveredPackages = $order->deliveryRequest->packages()
            ->whereNotIn('status', ['delivered', 'completed', 'returned', 'damaged_in_transit', 'lost_in_transit'])
            ->count();

        if ($undeliveredPackages === 0 && in_array($order->status, ['assigned', 'dispatched', 'in_transit'])) {
            $packages = $order->deliveryRequest->packages;
            $deliveredCount = $packages->where('status', 'delivered')->count();
            $totalPackages = $packages->count();
            
            $newOrderStatus = ($deliveredCount === $totalPackages) ? 'delivered' : 'needs_review';
            
            $order->update(['status' => $newOrderStatus]);
        }
    }

    private function checkAndEnableBackhaul(User $driver): bool
    {
        $assignment = $driver->currentTruckAssignment;
        
        if (!$assignment) {
            return false;
        }

        try {
            $assignment->processAutomaticTransitions();
            
            $eligible = $assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE;
            
            return $eligible;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function logDriverRegion(User $driver, int $regionId): void
    {
        $oldRegionId = $driver->current_region_id;
        
        $driver->current_region_id = $regionId;
        $driver->save();

        DriverRegionLog::create([
            'driver_id' => $driver->id,
            'region_id' => $regionId,
            'type' => 'arrival',
            'remarks' => 'Driver arrived at region via location update',
            'logged_at' => now()
        ]);

        $assignment = $driver->currentTruckAssignment;
        if ($assignment) {
            $assignment->current_region_id = $regionId;
            $assignment->save();

            $assignment->processAutomaticTransitions();
        }
    }
}
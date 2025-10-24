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
use App\Models\DriverStatusLog;
use App\Models\RegionTravelDuration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CargoAssignmentController extends Controller
{
    // Workflow modes
    const WORKFLOW_QUICK_ASSIGN = 'quick_assign';
    const WORKFLOW_BATCH_PLANNING = 'batch_planning';
    const WORKFLOW_BACKHAUL_OPTIMIZER = 'backhaul_optimizer';

    public function index()
{
    $perPage = request('per_page', 5);
    $workflowMode = request('workflow_mode', self::WORKFLOW_QUICK_ASSIGN);

    $query = DeliveryOrder::with([
        'deliveryRequest.sender',
        'deliveryRequest.receiver', 
        'deliveryRequest.packages',
        'deliveryRequest.pickUpRegion',
        'deliveryRequest.dropOffRegion',
        'driver.employeeProfile',
        'truck',
        'currentRegion',
        'driverTruckAssignment' => function($query) {
            $query->with([
                'driver' => function($q) {
                    $q->with('employeeProfile');
                },
                'truck', 
                'region', 
                'currentRegion'
            ]);
        }
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

    // Apply backhaul filter if present
    if ($backhaulFilter = request('backhaul_filter')) {
        if ($backhaulFilter === 'backhaul') {
            $query->whereHas('driverTruckAssignment', function($q) {
                $q->where('available_for_backhaul', true);
            });
        } elseif ($backhaulFilter === 'regular') {
            $query->whereHas('driverTruckAssignment', function($q) {
                $q->where('available_for_backhaul', false);
            });
        }
    }

    $deliveries = $query->latest()->paginate($perPage)->withQueryString();

    // Get driver-truck sets with capacity calculations
    $driverTruckSets = $this->getAvailableDriverTruckSets(request('region_id')) ?? [];

    $regions = Region::where('is_active', true)->get();

    // Get batch suggestions for batch planning mode
    $batchSuggestions = $this->getBatchSuggestions($workflowMode);

    // ✅ ADDED: Process deliveries to include manifest status
    $processedDeliveries = $deliveries->getCollection()->map(function($delivery) {
        // Check if this delivery's driver-truck assignment has a finalized manifest
        if ($delivery->driver_truck_assignment) {
            $hasFinalizedManifest = Manifest::where('driver_id', $delivery->driver_truck_assignment->driver_id)
                ->where('truck_id', $delivery->driver_truck_assignment->truck_id)
                ->where('status', 'finalized')
                ->exists();
            
            // Add the manifest status to the delivery object
            $delivery->driver_truck_assignment->has_finalized_manifest = $hasFinalizedManifest;
            $delivery->driver_truck_assignment->manifest_status = $hasFinalizedManifest ? 'finalized' : 'none';
        }
        
        return $delivery;
    });

    $deliveries->setCollection($processedDeliveries);

    return Inertia::render('Admin/CargoAssignment/Index', [
        'deliveries' => $deliveries,
        'driverTruckSets' => $driverTruckSets,
        'regions' => $regions,
        'filters' => request()->all(['search', 'status', 'region_id', 'backhaul_filter']),
        'workflow_mode' => $workflowMode,
        'batch_suggestions' => $batchSuggestions,
    ]);
}


    protected function hasFinalizedManifest(DriverTruckAssignment $assignment): bool
{
    // Check for finalized manifest using driver_truck_assignment_id
    $hasFinalizedManifest = Manifest::where('driver_truck_assignment_id', $assignment->id)
        ->where('status', 'finalized')
        ->exists();

    // If no manifest found by assignment ID, check by driver_id and truck_id for backward compatibility
    if (!$hasFinalizedManifest) {
        $hasFinalizedManifest = Manifest::where('driver_id', $assignment->driver_id)
            ->where('truck_id', $assignment->truck_id)
            ->where('status', 'finalized')
            ->exists();
    }

    return $hasFinalizedManifest;
}

    /**
     * Get batch suggestions based on workflow mode
     */
    protected function getBatchSuggestions(string $workflowMode): array
    {
        $readyDeliveries = DeliveryOrder::with(['deliveryRequest.packages', 'deliveryRequest.pickUpRegion', 'deliveryRequest.dropOffRegion'])
            ->where('status', 'ready')
            ->get();

        if ($workflowMode === self::WORKFLOW_BACKHAUL_OPTIMIZER) {
            return $this->getBackhaulSuggestions($readyDeliveries);
        } elseif ($workflowMode === self::WORKFLOW_BATCH_PLANNING) {
            return $this->getBatchPlanningSuggestions($readyDeliveries);
        }

        return $this->getQuickAssignSuggestions($readyDeliveries);
    }

    /**
     * Quick Assign suggestions - simple grouping by destination
     */
    protected function getQuickAssignSuggestions($deliveries): array
    {
        $suggestions = [];
        
        foreach ($deliveries as $delivery) {
            $destinationId = $delivery->deliveryRequest->drop_off_region_id;
            $pickupId = $delivery->deliveryRequest->pick_up_region_id;
            
            if (!isset($suggestions[$destinationId])) {
                $suggestions[$destinationId] = [
                    'destination_region' => $delivery->deliveryRequest->dropOffRegion,
                    'pickup_region' => $delivery->deliveryRequest->pickUpRegion,
                    'delivery_requests' => [],
                    'total_volume' => 0,
                    'total_weight' => 0,
                    'strategy' => 'destination_grouping',
                    'workflow_mode' => self::WORKFLOW_QUICK_ASSIGN
                ];
            }
            
            $packages = $delivery->deliveryRequest->packages;
            $volume = $packages->sum('volume');
            $weight = $packages->sum('weight');
            
            $suggestions[$destinationId]['delivery_requests'][] = $delivery;
            $suggestions[$destinationId]['total_volume'] += $volume;
            $suggestions[$destinationId]['total_weight'] += $weight;
        }
        
        return array_values($suggestions);
    }


// Add this method to your CargoAssignmentController
public function checkManifestStatus(DeliveryOrder $deliveryOrder)
{
    if (!$deliveryOrder->driver_truck_assignment) {
        return response()->json(['has_finalized_manifest' => false]);
    }

    $hasFinalizedManifest = Manifest::where('driver_id', $deliveryOrder->driver_truck_assignment->driver_id)
        ->where('truck_id', $deliveryOrder->driver_truck_assignment->truck_id)
        ->where('status', 'finalized')
        ->exists();

    return response()->json([
        'has_finalized_manifest' => $hasFinalizedManifest,
        'delivery_order_id' => $deliveryOrder->id
    ]);
}

    /**
     * Batch Planning suggestions - multiple grouping strategies
     */
    protected function getBatchPlanningSuggestions($deliveries): array
    {
        $suggestions = [];
        
        // Strategy 1: Destination-based grouping
        $destinationGroups = [];
        foreach ($deliveries as $delivery) {
            $key = $delivery->deliveryRequest->drop_off_region_id;
            if (!isset($destinationGroups[$key])) {
                $destinationGroups[$key] = [
                    'destination_region' => $delivery->deliveryRequest->dropOffRegion,
                    'pickup_region' => $delivery->deliveryRequest->pickUpRegion,
                    'delivery_requests' => [],
                    'total_volume' => 0,
                    'total_weight' => 0,
                    'strategy' => 'destination_grouping',
                    'workflow_mode' => self::WORKFLOW_BATCH_PLANNING
                ];
            }
            
            $packages = $delivery->deliveryRequest->packages;
            $volume = $packages->sum('volume');
            $weight = $packages->sum('weight');
            
            $destinationGroups[$key]['delivery_requests'][] = $delivery;
            $destinationGroups[$key]['total_volume'] += $volume;
            $destinationGroups[$key]['total_weight'] += $weight;
        }
        
        // Strategy 2: Time-based grouping (within 2-hour windows)
        $timeGroups = [];
        foreach ($deliveries as $delivery) {
            $timeWindow = Carbon::parse($delivery->estimated_departure)->format('Y-m-d H:00:00');
            if (!isset($timeGroups[$timeWindow])) {
                $timeGroups[$timeWindow] = [
                    'time_window' => $timeWindow,
                    'delivery_requests' => [],
                    'total_volume' => 0,
                    'total_weight' => 0,
                    'strategy' => 'time_grouping',
                    'workflow_mode' => self::WORKFLOW_BATCH_PLANNING
                ];
            }
            
            $packages = $delivery->deliveryRequest->packages;
            $volume = $packages->sum('volume');
            $weight = $packages->sum('weight');
            
            $timeGroups[$timeWindow]['delivery_requests'][] = $delivery;
            $timeGroups[$timeWindow]['total_volume'] += $volume;
            $timeGroups[$timeWindow]['total_weight'] += $weight;
        }
        
        // Strategy 3: Capacity optimization (group by similar capacity requirements)
        $capacityGroups = [];
        foreach ($deliveries as $delivery) {
            $packages = $delivery->deliveryRequest->packages;
            $volume = $packages->sum('volume');
            $weight = $packages->sum('weight');
            
            // Group by capacity tiers
            $volumeTier = floor($volume / 10) * 10; // 10m³ tiers
            $weightTier = floor($weight / 100) * 100; // 100kg tiers
            $key = "v{$volumeTier}_w{$weightTier}";
            
            if (!isset($capacityGroups[$key])) {
                $capacityGroups[$key] = [
                    'capacity_tier' => "Volume: {$volumeTier}-" . ($volumeTier + 10) . "m³, Weight: {$weightTier}-" . ($weightTier + 100) . "kg",
                    'delivery_requests' => [],
                    'total_volume' => 0,
                    'total_weight' => 0,
                    'strategy' => 'capacity_optimization',
                    'workflow_mode' => self::WORKFLOW_BATCH_PLANNING
                ];
            }
            
            $capacityGroups[$key]['delivery_requests'][] = $delivery;
            $capacityGroups[$key]['total_volume'] += $volume;
            $capacityGroups[$key]['total_weight'] += $weight;
        }
        
        // Combine all strategies
        $suggestions = array_merge(
            array_values($destinationGroups),
            array_values($timeGroups),
            array_values($capacityGroups)
        );
        
        return $suggestions;
    }

    /**
     * Backhaul Optimizer suggestions
     */
    protected function getBackhaulSuggestions($deliveries): array
    {
        $suggestions = [];
        
        // Find backhaul-eligible driver-truck sets
        $backhaulSets = DriverTruckAssignment::with(['driver', 'truck', 'region', 'currentRegion'])
            ->where('current_status', DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE)
            ->where('is_active', true)
            ->get();
        
        foreach ($backhaulSets as $set) {
            $homeRegionId = $set->region_id;
            $currentRegionId = $set->current_region_id;
            
            // Find deliveries that match backhaul criteria
            $matchingDeliveries = [];
            $totalVolume = 0;
            $totalWeight = 0;
            
            foreach ($deliveries as $delivery) {
                // Backhaul: pickup from current region, dropoff to home region
                if ($delivery->deliveryRequest->pick_up_region_id == $currentRegionId &&
                    $delivery->deliveryRequest->drop_off_region_id == $homeRegionId) {
                    
                    $packages = $delivery->deliveryRequest->packages;
                    $volume = $packages->sum('volume');
                    $weight = $packages->sum('weight');
                    
                    // Check capacity
                    if (($totalVolume + $volume) <= $set->truck->available_volume_capacity &&
                        ($totalWeight + $weight) <= $set->truck->available_weight_capacity) {
                        
                        $matchingDeliveries[] = $delivery;
                        $totalVolume += $volume;
                        $totalWeight += $weight;
                    }
                }
            }
            
            if (!empty($matchingDeliveries)) {
                $suggestions[] = [
                    'driver_truck_set' => $set,
                    'delivery_requests' => $matchingDeliveries,
                    'total_volume' => $totalVolume,
                    'total_weight' => $totalWeight,
                    'strategy' => 'backhaul_optimization',
                    'workflow_mode' => self::WORKFLOW_BACKHAUL_OPTIMIZER,
                    'backhaul_metrics' => [
                        'driver_name' => $set->driver->name,
                        'truck_plate' => $set->truck->license_plate,
                        'current_region' => $set->currentRegion->name,
                        'home_region' => $set->region->name,
                        'available_volume' => $set->truck->available_volume_capacity,
                        'available_weight' => $set->truck->available_weight_capacity
                    ]
                ];
            }
        }
        
        return $suggestions;
    }

   /**
     * Get available driver-truck assignments with capacity information
     */
   protected function getAvailableDriverTruckSets(?int $regionId = null)
{
    $sets = DriverTruckAssignment::with([
        'driver' => function($query) {
            $query->with('employeeProfile');
        },
        'truck',
        'region',
        'currentRegion'
    ])
    ->where('is_active', true)
    ->where(function($query) use ($regionId) {
        $query->whereIn('current_status', [
            DriverTruckAssignment::STATUS_ACTIVE,
            DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE
        ]);
    })
    ->whereHas('truck', function($q) {
        $q->where('is_active', true)
          ->whereIn('status', [
              Truck::STATUS_AVAILABLE, 
              Truck::STATUS_AVAILABLE_FOR_BACKHAUL, 
              Truck::STATUS_NEARLY_FULL,
              Truck::STATUS_ASSIGNED
          ]);
    })
    ->whereHas('driver', function($q) {
        $q->where('is_active', true)
          ->where('role', 'driver');
    })
    ->when($regionId, function($query) use ($regionId) {
        $query->where(function($q) use ($regionId) {
            $q->where(function($q2) use ($regionId) {
                $q2->where('current_status', DriverTruckAssignment::STATUS_ACTIVE)
                   ->where('region_id', $regionId);
            })->orWhere(function($q2) use ($regionId) {
                $q2->where('current_status', DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE)
                   ->where('current_region_id', $regionId);
            });
        });
    })
    ->get();

    $result = $sets->map(function($assignment) {
        $truck = $assignment->truck;
        $driver = $assignment->driver;

        if (!$truck || !$driver) {
            return null;
        }

        // ✅ FIXED: Check for finalized manifest using driver_id and truck_id
        $hasFinalizedManifest = Manifest::where('driver_id', $assignment->driver_id)
            ->where('truck_id', $assignment->truck_id)
            ->where('status', 'finalized')
            ->exists();

        // ✅ DEBUG: Log the manifest check
        \Log::info("Manifest check for DriverTruckAssignment", [
            'assignment_id' => $assignment->id,
            'driver_id' => $assignment->driver_id,
            'truck_id' => $assignment->truck_id,
            'has_finalized_manifest' => $hasFinalizedManifest
        ]);

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

        // Check if driver can accept new assignments
        $driver->canAcceptNewAssignment = $this->canDriverAcceptAssignment($driver);
        $driver->available = $driver->isActive();
        $driver->current_assignments = $driver->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->count();
        $driver->delivery_orders_count = $driver->current_assignments;
        $driver->last_assigned_at = $driver->last_assigned_at;

        // Calculate capacity percentages for warnings
        $volumePercentage = $truck->volume_capacity > 0 ? ($currentVolume / $truck->volume_capacity) * 100 : 0;
        $weightPercentage = $truck->weight_capacity > 0 ? ($currentWeight / $truck->weight_capacity) * 100 : 0;
        $maxPercentage = max($volumePercentage, $weightPercentage);

        // Determine capacity status
        $capacityStatus = 'normal';
        if ($maxPercentage >= 90) {
            $capacityStatus = 'critical';
        } elseif ($maxPercentage >= 75) {
            $capacityStatus = 'warning';
        }

        // Availability check
        $isAvailable = $driver->isAvailable() && 
                      $truck->is_active &&
                      ($truck->volume_capacity > $currentVolume || $truck->volume_capacity == 0) &&
                      ($truck->weight_capacity > $currentWeight || $truck->weight_capacity == 0);

        return [
            'id' => $assignment->id,
            'driver' => $driver,
            'truck' => $assignment->truck,
            'region' => $assignment->region,
            'current_region' => $assignment->currentRegion,
            'current_volume' => $currentVolume,
            'current_weight' => $currentWeight,
            'available_volume' => max(0, $truck->volume_capacity - $currentVolume),
            'available_weight' => max(0, $truck->weight_capacity - $currentWeight),
            'is_available' => $isAvailable,
            'active_orders' => $activeOrders,
            'available_for_backhaul' => $assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE,
            'assignment_type' => $assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE ? 'backhaul' : 'regular',
            'current_status' => $assignment->current_status,
            'region_match' => true,
            'capacity_status' => $capacityStatus,
            'capacity_percentage' => $maxPercentage,
            // Manifest status
            'has_finalized_manifest' => $hasFinalizedManifest,
            'manifest_status' => $hasFinalizedManifest ? 'finalized' : 'none',
            'can_accept_new_assignments' => !$hasFinalizedManifest
        ];
    })
    ->filter(function($set) {
        return $set !== null && $set['is_available'];
    })
    ->values()
    ->toArray();

    return $result;
}

    /**
     * Create a new delivery order with common assignment logic
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

  /**
 * Validate dispatch readiness for a driver-truck assignment
 */
public function validateDispatch(DriverTruckAssignment $assignment)
{
    try {
        // 1. Basic operational checks
        if (!$assignment->is_active) {
            return response()->json([
                'is_valid' => false,
                'errors' => ['This driver-truck assignment is not active.'],
                'assigned_orders_count' => 0,
                'message' => 'Assignment is not active'
            ]);
        }

        // 2. Check for assigned deliveries
        $assignedOrders = DeliveryOrder::where('driver_truck_assignment_id', $assignment->id)
            ->where('status', 'assigned')
            ->with(['deliveryRequest.packages', 'deliveryRequest.waybill'])
            ->get();

        if ($assignedOrders->isEmpty()) {
            return response()->json([
                'is_valid' => false,
                'errors' => ['No assigned deliveries found for this driver-truck set.'],
                'assigned_orders_count' => 0,
                'message' => 'No assigned deliveries'
            ]);
        }

        $errors = [];
        $warnings = [];

        // 3. Truck validation
        $truck = $assignment->truck;
        if (!$truck || !$truck->is_active) {
            $errors[] = 'Truck is not available for dispatch.';
        }

        // 4. Driver validation
        $driver = $assignment->driver;
        if (!$driver || !$driver->is_active) {
            $errors[] = 'Driver is not available for dispatch.';
        }

        // 5. Document validation - CRITICAL for dispatch
        $missingWaybills = [];
        $unstickerizedPackages = [];

        foreach ($assignedOrders as $order) {
            // Check waybill
            if (!$order->deliveryRequest->waybill) {
                $missingWaybills[] = $order->id;
            }

            // Check package stickers
            $unstickerized = $order->deliveryRequest->packages()
                ->whereNull('sticker_printed_at')
                ->get();

            foreach ($unstickerized as $package) {
                $unstickerizedPackages[] = [
                    'package_id' => $package->id,
                    'item_code' => $package->item_code,
                    'delivery_order_id' => $order->id
                ];
            }
        }

        if (!empty($missingWaybills)) {
            $errors[] = 'Missing waybills for some delivery orders.';
        }

        if (!empty($unstickerizedPackages)) {
            $errors[] = 'Some packages are missing stickers.';
        }

        // 6. MANIFEST VALIDATION (Integrated from provided function)
        $manifestCheck = $this->validateManifestForDispatch($assignment, $assignedOrders);
        if (!$manifestCheck['has_manifest']) {
            $errors[] = 'No finalized manifest found for this driver-truck set.';
        } elseif (!$manifestCheck['manifest_valid']) {
            $errors[] = 'Manifest is missing packages from current assignments.';
        }

        // 7. Capacity warnings (not blocking)
        $currentVolume = $assignedOrders->sum(fn($order) => 
            $order->deliveryRequest->packages->sum('volume')
        );
        $currentWeight = $assignedOrders->sum(fn($order) => 
            $order->deliveryRequest->packages->sum('weight')
        );

        if ($truck) {
            $volumePercentage = $truck->volume_capacity > 0 ? ($currentVolume / $truck->volume_capacity) * 100 : 0;
            $weightPercentage = $truck->weight_capacity > 0 ? ($currentWeight / $truck->weight_capacity) * 100 : 0;
            
            if ($volumePercentage >= 90 || $weightPercentage >= 90) {
                $warnings[] = 'Truck capacity nearly exceeded. Please verify load distribution.';
            }
        }

        // 8. Final determination
        $canDispatch = empty($errors);

        return response()->json([
            'is_valid' => $canDispatch,
            'errors' => $errors,
            'warnings' => $warnings,
            'assigned_orders_count' => $assignedOrders->count(),
            'missing_waybills' => $missingWaybills,
            'unstickerized_packages' => $unstickerizedPackages,
            'total_volume' => $currentVolume,
            'total_weight' => $currentWeight,
            'manifest_check' => $manifestCheck, // Include manifest validation details
            'message' => $canDispatch ? 'Ready for dispatch' : 'Cannot dispatch due to validation errors'
        ]);

    } catch (\Exception $e) {
        \Log::error('Dispatch validation error: ' . $e->getMessage());
        
        return response()->json([
            'is_valid' => false,
            'errors' => ['An error occurred while validating dispatch: ' . $e->getMessage()],
            'assigned_orders_count' => 0,
            'missing_waybills' => [],
            'unstickerized_packages' => [],
            'warnings' => [],
            'manifest_check' => ['has_manifest' => false, 'manifest_valid' => false],
            'message' => 'Validation error occurred'
        ], 500);
    }
}

/**
 * Validate manifest for dispatch (extracted from provided function)
 */
protected function validateManifestForDispatch(DriverTruckAssignment $assignment, $assignedOrders): array
{
    // Gather all package IDs for the current assigned orders
    $currentPackageIds = $assignedOrders->flatMap(function($order) {
        return $order->deliveryRequest->packages->pluck('id');
    })->unique()->values()->toArray();

    // Find a finalized manifest for this driver-truck assignment
    $manifest = Manifest::where('driver_truck_assignment_id', $assignment->id)
        ->where('status', 'finalized')
        ->orderByDesc('id')
        ->first();

    // If no manifest found, also check by driver_id and truck_id for backward compatibility
    if (!$manifest) {
        $manifest = Manifest::where('driver_id', $assignment->driver_id)
            ->where('truck_id', $assignment->truck_id)
            ->where('status', 'finalized')
            ->orderByDesc('id')
            ->first();
    }

    // Improved manifest validation
    $manifestValid = false;
    if ($manifest && is_array($manifest->package_ids)) {
        $manifestPackageIds = array_map('intval', $manifest->package_ids);
        $currentIds = array_map('intval', $currentPackageIds);
        
        // Check if ALL current packages are in the manifest (manifest can have more packages)
        $manifestValid = empty(array_diff($currentIds, $manifestPackageIds));
    }

    $missingPackages = [];
    if ($manifest && is_array($manifest->package_ids)) {
        $missingPackages = array_diff(
            array_map('intval', $currentPackageIds),
            array_map('intval', $manifest->package_ids)
        );
    }

    return [
        'has_manifest' => (bool)$manifest,
        'manifest_valid' => $manifestValid,
        'manifest_id' => $manifest ? $manifest->id : null,
        'current_package_ids' => $currentPackageIds,
        'manifest_package_ids' => $manifest ? $manifest->package_ids : [],
        'missing_package_ids' => $missingPackages,
        'missing_package_count' => count($missingPackages)
    ];
}

public function assign(Request $request, DeliveryRequest $deliveryRequest)
{
    $request->validate([
        'driver_truck_assignment_id' => 'required|exists:driver_truck_assignments,id',
        'estimated_departure' => 'required|date|after:now',
        'workflow_mode' => 'sometimes|in:' . implode(',', [self::WORKFLOW_QUICK_ASSIGN, self::WORKFLOW_BATCH_PLANNING, self::WORKFLOW_BACKHAUL_OPTIMIZER])
    ]);

    $assignment = \App\Models\DriverTruckAssignment::with(['region', 'currentRegion'])->findOrFail($request->driver_truck_assignment_id);

    // Check for cooldown status
    if ($this->isInCooldown($assignment)) {
        return back()->withErrors('Driver is in cooldown period and cannot be assigned new deliveries.');
    }

    // Backhaul validation
    if ($assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE) {
        // For backhaul assignments, pickup must match current region and dropoff must match home region
        if ($deliveryRequest->pick_up_region_id != $assignment->current_region_id) {
            return back()->withErrors('For backhaul assignments, pickup must be from driver\'s current region.');
        }
        if ($deliveryRequest->drop_off_region_id != $assignment->region_id) {
            return back()->withErrors('For backhaul assignments, delivery must be to driver\'s home region.');
        }
    } else {
        // Regular assignment validation
        if ($deliveryRequest->pick_up_region_id != $assignment->region_id) {
            return back()->withErrors('Pickup region must match driver-truck set\'s home region for regular assignments.');
        }
    }

    // Set driver's current region if not set
    if (!$assignment->driver->current_region_id) {
        $assignment->driver->current_region_id = $assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE 
            ? $assignment->current_region_id 
            : $assignment->region_id;
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

    // Check if all packages have stickers printed
    $unstickerizedPackages = $deliveryRequest->packages()
        ->whereNull('sticker_printed_at')
        ->get();

    if (!$unstickerizedPackages->isEmpty()) {
        $packageCodes = $unstickerizedPackages->pluck('item_code')->implode(', ');
        return back()->withErrors("Cannot assign delivery request. The following packages require stickers: {$packageCodes}");
    }

    // Calculate estimated_arrival
    $deliveryRequest = $order->deliveryRequest;
    $travelDuration = \App\Models\RegionTravelDuration::where([
        'from_region_id' => $deliveryRequest->pick_up_region_id,
        'to_region_id' => $deliveryRequest->drop_off_region_id
    ])->first();

    $estimatedArrival = $travelDuration
        ? \Carbon\Carbon::parse($request->estimated_departure)->addMinutes((int) $travelDuration->estimated_minutes)
        : \Carbon\Carbon::parse($request->estimated_departure)->addHours((int) config('delivery.default_travel_duration_hours', 6));

    // Update assignment - ONLY update the delivery order, NOT the assignment status
    $order->update([
        'driver_id' => $assignment->driver_id,
        'truck_id' => $assignment->truck_id,
        'driver_truck_assignment_id' => $assignment->id,
        'estimated_departure' => $request->estimated_departure,
        'estimated_arrival' => $estimatedArrival,
        'status' => 'assigned',
    ]);

    // ✅ ENHANCED: Update package statuses with status history logging
    $deliveryRequest->packages()->each(function($package) use ($assignment) {
        $package->update([
            'status' => 'loaded',
            'current_region_id' => $package->deliveryRequest->pick_up_region_id
        ]);
        
        // Log the status change in package_status_history
        $package->statusHistory()->create([
            'status' => 'loaded',
            'remarks' => $assignment->current_status === \App\Models\DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE 
                ? 'Package loaded for backhaul delivery' 
                : 'Package loaded for regular delivery',
            'updated_by' => auth()->id() // Staff who assigned
        ]);
    });

    // NO assignment status update - preserve existing status (regular or backhaul)

    if ($isReassignment) {
        return back()->with('success', 'Delivery order reassigned to a different driver-truck set.');
    }

    return back()->with('success', 'Delivery order assigned successfully.');
}


  public function batchAssign(Request $request)
{
    $request->validate([
        'delivery_request_ids' => 'required|array',
        'driver_truck_assignment_id' => 'required|exists:driver_truck_assignments,id',
        'estimated_departure' => 'required|date',
        'workflow_mode' => 'sometimes|in:' . implode(',', [self::WORKFLOW_QUICK_ASSIGN, self::WORKFLOW_BATCH_PLANNING, self::WORKFLOW_BACKHAUL_OPTIMIZER])
    ]);

    $assignment = DriverTruckAssignment::with(['region', 'currentRegion'])->find($request->driver_truck_assignment_id);

    // Use consistent cooldown check
    if ($this->isInCooldown($assignment)) {
        return redirect()->back()->with('error', 'Driver is in cooldown period and cannot be assigned new deliveries.');
    }

    // Backhaul validation for batch assignments
    if ($assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE) {
        $invalidOrders = [];
        foreach ($request->delivery_request_ids as $drId) {
            $deliveryRequest = DeliveryRequest::find($drId);
            if ($deliveryRequest->pick_up_region_id != $assignment->current_region_id || 
                $deliveryRequest->drop_off_region_id != $assignment->region_id) {
                $invalidOrders[] = $drId;
            }
        }
        if (!empty($invalidOrders)) {
            return redirect()->back()->with('error', 'For backhaul assignments, all orders must pickup from current region and deliver to home region.');
        }
    }

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

    // Check for unstickerized packages in ALL selected orders
    $unstickerizedPackages = [];
    foreach ($orders as $order) {
        $packagesWithoutStickers = $order->deliveryRequest->packages()
            ->whereNull('sticker_printed_at')
            ->get();

        if (!$packagesWithoutStickers->isEmpty()) {
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

    DB::transaction(function() use ($assignment, $orders, $request) {
        // Set driver's current region if not set
        if (!$assignment->driver->current_region_id) {
            $assignment->driver->current_region_id = $assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE 
                ? $assignment->current_region_id 
                : $assignment->region_id;
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
                'estimated_arrival' => $estimatedArrival,
                'assigned_by' => auth()->id(),
                'current_region_id' => $order->deliveryRequest->pick_up_region_id,
            ]);
            
            // ✅ ENHANCED: Update package statuses with status history logging
            $order->deliveryRequest->packages()->each(function($package) use ($assignment) {
                $package->update([
                    'status' => 'loaded',
                    'current_region_id' => $package->deliveryRequest->pick_up_region_id
                ]);
                
                // Log the status change in package_status_history
                $package->statusHistory()->create([
                    'status' => 'loaded',
                    'remarks' => $assignment->current_status === \App\Models\DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE 
                        ? 'Package loaded for backhaul delivery (batch)' 
                        : 'Package loaded for regular delivery (batch)',
                    'updated_by' => auth()->id() // Staff who assigned
                ]);
            });
        }

        // NO assignment status update - preserve existing status (regular or backhaul)
        // Just update truck status and driver assignment time
        $assignment->truck->updateStatus();
        $assignment->driver->update(['last_assigned_at' => now()]);
    });

    return redirect()->back()->with('success', 'Batch assignment completed');
}

/**
 * Check if assignment is in cooldown period - FIXED VERSION
 */
protected function isInCooldown(DriverTruckAssignment $assignment): bool
{
    // Check if assignment is explicitly in cooldown status
    if ($assignment->current_status === DriverTruckAssignment::STATUS_COOLDOWN) {
        // If cooldown_ends_at is set and in the future, still in cooldown
        if ($assignment->cooldown_ends_at && $assignment->cooldown_ends_at->isFuture()) {
            \Log::info("Assignment {$assignment->id} is in cooldown until {$assignment->cooldown_ends_at}");
            return true;
        }
        
        // If cooldown period has ended, but status hasn't been updated yet
        if ($assignment->cooldown_ends_at && $assignment->cooldown_ends_at->isPast()) {
            \Log::info("Assignment {$assignment->id} cooldown period ended but status not updated");
            // Auto-complete the cooldown
            $assignment->completeCooldown();
            return false;
        }
        
        // If no cooldown end time is set, assume cooldown is active
        if (!$assignment->cooldown_ends_at) {
            \Log::info("Assignment {$assignment->id} is in cooldown but no end time set");
            return true;
        }
    }
    
    // If status is backhaul eligible, definitely not in cooldown
    if ($assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE) {
        return false;
    }
    
    \Log::info("Assignment {$assignment->id} current status: {$assignment->current_status}, cooldown check: false");
    return false;
}

  /**
 * Dispatch a driver-truck set with manifest validation
 */
public function dispatch(Request $request, DriverTruckAssignment $assignment)
{
    // Validate that all assigned packages are included in a finalized manifest
    $assignedOrders = DeliveryOrder::where('driver_truck_assignment_id', $assignment->id)
        ->where('status', 'assigned')
        ->with('deliveryRequest.packages')
        ->get();

    if ($assignedOrders->isEmpty()) {
        return back()->withErrors('No assigned delivery orders found for this driver-truck set.');
    }

    // Check waybill requirements
    foreach ($assignedOrders as $order) {
        if (!$order->deliveryRequest->waybill) {
            return back()->withErrors("Delivery Order {$order->do_number} requires a waybill before dispatch.");
        }
    }

    // Check for finalized manifest that covers all packages
    $allAssignedPackages = $assignedOrders->flatMap(function($order) {
        return $order->deliveryRequest->packages->pluck('id');
    })->unique()->sort()->values();

    // Find a finalized manifest for this driver-truck assignment
    $manifest = Manifest::where('driver_truck_assignment_id', $assignment->id)
        ->where('status', 'finalized')
        ->orderByDesc('id')
        ->first();

    // If no manifest found by assignment ID, check by driver_id and truck_id
    if (!$manifest) {
        $manifest = Manifest::where('driver_id', $assignment->driver_id)
            ->where('truck_id', $assignment->truck_id)
            ->where('status', 'finalized')
            ->orderByDesc('id')
            ->first();
    }

    // Validate manifest coverage
    if (!$manifest) {
        return back()->withErrors('No finalized manifest found for this driver-truck set. Please create and finalize a manifest first.');
    }

    if (!is_array($manifest->package_ids)) {
        return back()->withErrors('Manifest package data is invalid. Please regenerate the manifest.');
    }

    $manifestPackageIds = collect($manifest->package_ids)->map('intval')->sort()->values();
    $currentPackageIds = $allAssignedPackages->map('intval')->sort()->values();

    // Check if ALL current packages are in the manifest
    $missingPackages = $currentPackageIds->diff($manifestPackageIds);
    if ($missingPackages->isNotEmpty()) {
        return back()->withErrors('Manifest is missing ' . $missingPackages->count() . ' packages from current assignments. Please update the manifest.');
    }

    DB::transaction(function() use ($assignment, $assignedOrders, $manifest) {
        $previousStatus = $assignment->current_status;
        
        // Update delivery orders status
        DeliveryOrder::where('driver_truck_assignment_id', $assignment->id)
            ->where('status', 'assigned')
            ->update([
                'status' => 'dispatched',
                'actual_departure' => now()
            ]);

        // ✅ ENHANCED: Update packages status to 'in_transit' with status history logging
        $packageIds = $assignedOrders->flatMap(function($order) {
            return $order->deliveryRequest->packages->pluck('id');
        });
        
        // Get all packages to update with logging
        $packages = Package::whereIn('id', $packageIds)->get();
        
        $packages->each(function($package) use ($assignment) {
            $package->update([
                'status' => 'in_transit'
            ]);
            
            // Log the status change in package_status_history
            $package->statusHistory()->create([
                'status' => 'in_transit',
                'remarks' => $assignment->current_status === \App\Models\DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE 
                    ? 'Package dispatched for backhaul delivery' 
                    : 'Package dispatched for regular delivery',
                'updated_by' => auth()->id() // Staff who dispatched
            ]);
        });

        // DON'T change assignment status for backhaul - keep it as BACKHAUL_ELIGIBLE
        // Only update regular assignments to IN_TRANSIT
        $newStatus = $assignment->current_status;
        $isBackhaulDispatch = false;
        
        if ($assignment->current_status !== DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE) {
            $newStatus = DriverTruckAssignment::STATUS_IN_TRANSIT;
            $assignment->update([
                'current_status' => $newStatus
            ]);
        } else {
            $isBackhaulDispatch = true;
        }

        // Update truck status (but preserve backhaul eligibility)
        $assignment->truck->update([
            'status' => Truck::STATUS_IN_TRANSIT
            // available_for_backhaul remains unchanged!
        ]);

    // Get the count of packages in the manifest
$manifestPackageCount = count($manifest->package_ids ?? []);

// Update manifest
$manifest->update([
    'dispatched_at' => now(),
    'dispatched_by' => auth()->id()
]);

// ✅ ADDED: Log the dispatch event in DriverStatusLog
DriverStatusLog::create([
    'driver_truck_assignment_id' => $assignment->id,
    'previous_status' => $previousStatus,
    'new_status' => $newStatus,
    'remarks' => $isBackhaulDispatch 
        ? "Driver-truck set DISPATCHED for BACKHAUL assignment. Manifest #{$manifest->id} finalized with {$manifestPackageCount} packages. Truck {$assignment->truck->license_plate} is now in transit for backhaul delivery."
        : "Driver-truck set DISPATCHED for REGULAR assignment. Manifest #{$manifest->id} finalized with {$manifestPackageCount} packages. Status changed from {$previousStatus} to IN_TRANSIT. Truck {$assignment->truck->license_plate} is now in transit.",
    'changed_at' => now()
]);
    });

    return back()->with('success', 'Driver-truck set dispatched successfully.');
}


private function getInitials(?string $name): string
{
    if (!$name) return '??';
    
    $names = explode(' ', $name);
    $initials = '';
    
    foreach ($names as $name) {
        if (!empty(trim($name))) {
            $initials .= strtoupper(substr(trim($name), 0, 1));
        }
    }
    
    return substr($initials, 0, 2);
}

public function cancelDeliveryOrderAssignment(DeliveryOrder $deliveryOrder, Request $request)
{
    $request->validate([
        'reason' => 'required|string|max:500'
    ]);

    if (!in_array($deliveryOrder->status, ['assigned', 'dispatched', 'in_transit'])) {
        return back()->withErrors('Cannot cancel assignment. Order is not in an assignable state.');
    }

    try {
        DB::transaction(function() use ($deliveryOrder, $request) {
            $previousStatus = $deliveryOrder->status;
            $assignment = $deliveryOrder->driverTruckAssignment;
            
            // Determine revert status based on payment - DELIVERY ORDER GOES TO READY
            $revertStatus = $deliveryOrder->deliveryRequest->payment_method === 'postpaid' 
                && $deliveryOrder->deliveryRequest->payment_status === 'pending'
                ? 'pending_payment' 
                : 'ready'; // ← DELIVERY ORDER GOES TO READY

            // Revert delivery order to 'ready'
            $deliveryOrder->update([
                'driver_id' => null,
                'truck_id' => null,
                'driver_truck_assignment_id' => null,
                'status' => $revertStatus, // Should be 'ready'
                'estimated_departure' => null,
                'estimated_arrival' => null,
                'actual_departure' => null,
                'assigned_at' => null,
                'assigned_by' => null,
                'cancellation_reason' => $request->reason,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now()
            ]);

            // Revert packages to 'preparing' status
            $deliveryOrder->deliveryRequest->packages()->each(function($package) use ($request) {
                $package->update([
                    'status' => 'preparing', // ← PACKAGES GO TO PREPARING
                    'current_driver_id' => null,
                    'current_truck_id' => null,
                    'loaded_at' => null,
                    'current_region_id' => $package->deliveryRequest->pick_up_region_id
                ]);
                
                // Log package status change
                $package->statusHistory()->create([
                    'status' => 'preparing',
                    'remarks' => "Assignment cancelled: " . $request->reason,
                    'updated_by' => auth()->id()
                ]);
            });

            // Check if assignment should be updated
            if ($assignment) {
                $remainingOrders = DeliveryOrder::where('driver_truck_assignment_id', $assignment->id)
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                    ->count();

                // If no more active orders, consider updating assignment status
                if ($remainingOrders === 0 && $assignment->current_status === DriverTruckAssignment::STATUS_IN_TRANSIT) {
                    $assignment->update([
                        'current_status' => DriverTruckAssignment::STATUS_ACTIVE
                    ]);
                }
            }

            \Log::info("Delivery order assignment cancelled", [
                'delivery_order_id' => $deliveryOrder->id,
                'previous_status' => $previousStatus,
                'reverted_to' => $revertStatus, // Should be 'ready'
                'package_status' => 'preparing', // Packages went to preparing
                'reason' => $request->reason
            ]);
        });

        return back()->with('success', 'Delivery order assignment cancelled successfully.');

    } catch (\Exception $e) {
        \Log::error('Failed to cancel delivery order assignment: ' . $e->getMessage(), [
            'delivery_order_id' => $deliveryOrder->id
        ]);
        return back()->with('error', 'Failed to cancel assignment: ' . $e->getMessage());
    }
}

    /**
     * Helper method to check if driver can accept assignments
     */
    protected function canDriverAcceptAssignment($driver): bool
    {
        // Basic availability check
        if (!$driver->is_active) {
            return false;
        }

        // Check if driver has too many active assignments
        $maxAssignments = config('delivery.max_assignments_per_driver', 5);
        $currentAssignments = $driver->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->count();

        return $currentAssignments < $maxAssignments;
    }
}
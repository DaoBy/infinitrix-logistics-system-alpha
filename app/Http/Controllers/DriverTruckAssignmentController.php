<?php

namespace App\Http\Controllers;

use App\Models\DriverTruckAssignment;
use App\Models\User;
use App\Models\Truck;
use App\Models\Region;
use App\Models\DriverStatusLog;
use App\Models\DriverRegionLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DriverTruckAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = DriverTruckAssignment::with([
            'driver' => function($query) {
                $query->with([
                    'employeeProfile',
                    'regionLogs'
                ]);
            },
            'truck' => function($query) {
                $query->withCount(['deliveryOrders as active_deliveries_count' => function($q) {
                    $q->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
                }]);
            },
            'region',
            'currentRegion'
        ])->notDeleted()->latest();

        $assignments = $query->get();
        foreach ($assignments as $assignment) {
            try {
                $assignment->processAutomaticTransitions();
            } catch (\Exception $e) {
                \Log::error('Failed to process automatic transitions for assignment ' . $assignment->id . ': ' . $e->getMessage());
            }
        }

        $query = DriverTruckAssignment::with([
            'driver' => function($query) {
                $query->with([
                    'employeeProfile',
                    'regionLogs'
                ]);
            },
            'truck' => function($query) {
                $query->withCount(['deliveryOrders as active_deliveries_count' => function($q) {
                    $q->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
                }]);
            },
            'region',
            'currentRegion'
        ])->notDeleted()->latest();

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        if ($request->filled('driver_status') && $request->driver_status !== 'all') {
            $query->where('current_status', $request->driver_status);
        }

        if ($request->filled('cooldown_completed')) {
            if ($request->cooldown_completed === 'completed') {
                $query->where(function($q) {
                    $q->where('current_status', '!=', DriverTruckAssignment::STATUS_COOLDOWN)
                      ->orWhere(function($q2) {
                          $q2->where('current_status', DriverTruckAssignment::STATUS_COOLDOWN)
                             ->where('cooldown_ends_at', '<=', now());
                      });
                });
            } elseif ($request->cooldown_completed === 'active') {
                $query->where('current_status', DriverTruckAssignment::STATUS_COOLDOWN)
                      ->where('cooldown_ends_at', '>', now());
            }
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('driver', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('truck', function($q) use ($search) {
                    $q->where('license_plate', 'like', "%{$search}%");
                });
            });
        }

        $assignments = $query->paginate(10);

        return Inertia::render('Admin/CargoAssignment/DriverTruckAssignments/Index', [
            'assignments' => $assignments,
            'regions' => Region::active()->get(),
            'metrics' => $this->getAssignmentMetrics(),
            'deleteReasons' => DriverTruckAssignment::getDeleteReasons(),
            'statusOptions' => DriverTruckAssignment::getStatuses(),
            'filters' => $request->only([
                'region_id', 
                'status', 
                'search', 
                'driver_status', 
                'cooldown_completed'
            ])
        ]);
    }

    public function getAvailableResources(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id'
        ]);

        $regionId = $request->region_id;

        $driverQuery = User::with(['employeeProfile', 'truckAssignments' => function($q) {
            $q->where('is_active', true)->whereNull('deleted_at');
        }])
        ->where('role', 'driver')
        ->where('is_active', true)
        ->whereHas('employeeProfile', function($q) use ($regionId) {
            $q->where('region_id', $regionId)
              ->whereNull('archived_at');
        });

        $allDriversInRegion = $driverQuery->get();
        $availableDrivers = $allDriversInRegion->filter(function ($driver) {
            return $driver->truckAssignments->isEmpty();
        });

        $drivers = $availableDrivers->map(function ($driver) {
            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'email' => $driver->email,
                'employee_profile' => $driver->employeeProfile ? [
                    'employee_id' => $driver->employeeProfile->employee_id,
                    'position' => $driver->employeeProfile->position,
                ] : null,
                'current_region' => $driver->currentRegion ? [
                    'id' => $driver->currentRegion->id,
                    'name' => $driver->currentRegion->name,
                ] : null,
            ];
        });

        $truckQuery = Truck::with(['driverAssignments' => function($q) {
            $q->where('is_active', true)->whereNull('deleted_at');
        }])
        ->where('region_id', $regionId)
        ->where('is_active', true)
        ->whereIn('status', [
            Truck::STATUS_AVAILABLE, 
            Truck::STATUS_AVAILABLE_FOR_BACKHAUL,
            Truck::STATUS_MAINTENANCE
        ]);

        $allTrucksInRegion = $truckQuery->get();
        $availableTrucks = $allTrucksInRegion->filter(function ($truck) {
            return $truck->driverAssignments->isEmpty();
        });

        $trucks = $availableTrucks->map(function ($truck) {
            return [
                'id' => $truck->id,
                'make' => $truck->make,
                'model' => $truck->model,
                'license_plate' => $truck->license_plate,
                'status' => $truck->status,
                'capacity_kg' => $truck->capacity_kg,
                'capacity_volume' => $truck->capacity_volume,
            ];
        });

        return response()->json([
            'drivers' => $drivers,
            'trucks' => $trucks
        ]);
    }

    public function getAllResourcesForRegion(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id'
        ]);

        $regionId = $request->region_id;

        $allDrivers = User::with(['employeeProfile', 'truckAssignments' => function($q) {
                $q->where('is_active', true)->whereNull('deleted_at');
            }])
            ->where('role', 'driver')
            ->where('is_active', true)
            ->whereHas('employeeProfile', function($q) use ($regionId) {
                $q->where('region_id', $regionId)
                  ->whereNull('archived_at');
            })
            ->get()
            ->map(function ($driver) {
                $activeAssignment = $driver->truckAssignments->first();
                
                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'email' => $driver->email,
                    'is_active' => $driver->is_active,
                    'has_active_assignment' => $activeAssignment ? true : false,
                    'active_assignment' => $activeAssignment ? [
                        'id' => $activeAssignment->id,
                        'truck_id' => $activeAssignment->truck_id,
                        'current_status' => $activeAssignment->current_status,
                        'is_active' => $activeAssignment->is_active,
                        'is_final_cooldown' => $activeAssignment->is_final_cooldown,
                    ] : null,
                    'employee_profile' => $driver->employeeProfile ? [
                        'employee_id' => $driver->employeeProfile->employee_id,
                        'position' => $driver->employeeProfile->position,
                        'region_id' => $driver->employeeProfile->region_id,
                        'archived_at' => $driver->employeeProfile->archived_at,
                    ] : null,
                    'current_region' => $driver->currentRegion ? [
                        'id' => $driver->currentRegion->id,
                        'name' => $driver->currentRegion->name,
                    ] : null,
                ];
            });

        $allTrucks = Truck::with(['driverAssignments' => function($q) {
                $q->where('is_active', true)->whereNull('deleted_at');
            }])
            ->where('region_id', $regionId)
            ->where('is_active', true)
            ->get()
            ->map(function ($truck) {
                $activeAssignment = $truck->driverAssignments->first();
                
                return [
                    'id' => $truck->id,
                    'make' => $truck->make,
                    'model' => $truck->model,
                    'license_plate' => $truck->license_plate,
                    'status' => $truck->status,
                    'is_active' => $truck->is_active,
                    'has_active_assignment' => $activeAssignment ? true : false,
                    'active_assignment' => $activeAssignment ? [
                        'id' => $activeAssignment->id,
                        'driver_id' => $activeAssignment->driver_id,
                        'current_status' => $activeAssignment->current_status,
                        'is_active' => $activeAssignment->is_active,
                        'is_final_cooldown' => $activeAssignment->is_final_cooldown,
                    ] : null,
                ];
            });

        return response()->json([
            'all_drivers' => $allDrivers,
            'all_trucks' => $allTrucks,
        ]);
    }

    public function checkDatabaseState(Request $request)
    {
        $regionId = $request->region_id;

        $driversInDb = \DB::table('users as u')
            ->leftJoin('employee_profiles as ep', 'u.id', '=', 'ep.user_id')
            ->where('u.role', 'driver')
            ->where('u.is_active', true)
            ->where('ep.region_id', $regionId)
            ->select('u.id', 'u.name', 'u.email', 'u.is_active as user_active', 
                    'ep.region_id', 'ep.archived_at', 'ep.employee_id')
            ->get();

        $trucksInDb = \DB::table('trucks')
            ->where('region_id', $regionId)
            ->select('id', 'license_plate', 'status', 'is_active', 'region_id')
            ->get();

        $activeAssignments = \DB::table('driver_truck_assignments')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'driver_id', 'truck_id', 'current_status', 'is_active', 'region_id')
            ->get();

        return response()->json([
            'database_state' => [
                'drivers' => $driversInDb,
                'trucks' => $trucksInDb,
                'active_assignments' => $activeAssignments,
                'summary' => [
                    'drivers_in_region' => $driversInDb->count(),
                    'trucks_in_region' => $trucksInDb->count(),
                    'active_assignments' => $activeAssignments->count(),
                    'available_drivers' => $driversInDb->count() - $activeAssignments->where('region_id', $regionId)->count(),
                    'available_trucks' => $trucksInDb->where('is_active', true)->count() - $activeAssignments->where('region_id', $regionId)->count(),
                ]
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
            'truck_id' => 'required|exists:trucks,id',
            'region_id' => 'required|exists:regions,id'
        ]);

        $validator = validator($request->all(), []);
        
        $driverConflict = DriverTruckAssignment::where('driver_id', $validated['driver_id'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->exists();
            
        $truckConflict = DriverTruckAssignment::where('truck_id', $validated['truck_id'])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->exists();
        
        if ($driverConflict) {
            $validator->errors()->add('driver_id', 'Driver is already assigned to another truck.');
        }
        
        if ($truckConflict) {
            $validator->errors()->add('truck_id', 'Truck is already assigned to another driver.');
        }
        
        if ($validator->errors()->any()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $assignment = new DriverTruckAssignment();
            $assignment->driver_id = $validated['driver_id'];
            $assignment->truck_id = $validated['truck_id'];
            $assignment->region_id = $validated['region_id'];
            $assignment->current_region_id = $validated['region_id'];
            $assignment->current_status = DriverTruckAssignment::STATUS_ACTIVE;
            $assignment->assigned_at = now();
            $assignment->is_active = true;
            $assignment->available_for_backhaul = false;
            $assignment->is_final_cooldown = false;
            $assignment->cooldown_ends_at = null;
            $assignment->backhaul_eligible_at = null;
            $assignment->deleted_reason = null;
            $assignment->deleted_by = null;
            $assignment->deleted_at = null;
            $assignment->save();

            DriverStatusLog::create([
                'driver_truck_assignment_id' => $assignment->id,
                'previous_status' => null,
                'new_status' => DriverTruckAssignment::STATUS_ACTIVE,
                'remarks' => "ASSIGNMENT CREATED: Driver {$assignment->driver->name} assigned to Truck {$assignment->truck->license_plate} in {$assignment->region->name} region.",
                'changed_at' => now()
            ]);

            DB::commit();

            \Log::info('Driver-truck assignment created successfully', [
                'assignment_id' => $assignment->id,
                'driver_id' => $validated['driver_id'],
                'truck_id' => $validated['truck_id'],
                'region_id' => $validated['region_id']
            ]);

            return redirect()->back()->with('success', 'Driver-Truck assignment created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create driver-truck assignment: ' . $e->getMessage(), [
                'driver_id' => $validated['driver_id'],
                'truck_id' => $validated['truck_id'],
                'region_id' => $validated['region_id']
            ]);
            return back()->with('error', 'Failed to create assignment: ' . $e->getMessage());
        }
    }

    public function cancel(Request $request, DriverTruckAssignment $assignment)
    {
        $request->validate([
            'reason' => 'required|in:' . implode(',', array_keys(DriverTruckAssignment::getDeleteReasons())),
            'remarks' => 'nullable|string|max:500'
        ]);

        try {
            if (!$assignment->canBeCancelled()) {
                \Log::warning('Attempted to cancel assignment that cannot be cancelled', [
                    'assignment_id' => $assignment->id,
                    'current_status' => $assignment->current_status,
                    'has_active_deliveries' => $assignment->hasActiveDeliveries()
                ]);
                return redirect()->back()->with('error', 
                    'Assignment cannot be cancelled. It may have active deliveries or packages assigned.'
                );
            }

            $reason = $request->reason;
            $remarks = $request->remarks ?: "Cancelled: " . DriverTruckAssignment::getDeleteReasons()[$reason];
            
            $assignment->cancelAssignment($reason, Auth::id());

            \Log::info('Assignment cancelled successfully', [
                'assignment_id' => $assignment->id,
                'reason' => $reason,
                'cancelled_by' => Auth::id()
            ]);

            return redirect()->back()->with('success', 'Assignment cancelled successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to cancel assignment: ' . $e->getMessage(), [
                'assignment_id' => $assignment->id,
                'reason' => $request->reason
            ]);
            return redirect()->back()->with('error', 'Failed to cancel assignment: ' . $e->getMessage());
        }
    }

    public function getByRegion(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id'
        ]);

        $assignments = DriverTruckAssignment::with(['driver', 'truck'])
            ->where(function($query) use ($request) {
                $query->where('region_id', $request->region_id)
                    ->orWhere(function($q) use ($request) {
                        $q->where('current_status', DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE)
                          ->where('current_region_id', $request->region_id);
                    });
            })
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        foreach ($assignments as $assignment) {
            $assignment->processAutomaticTransitions();
        }

        return response()->json($assignments);
    }

    public function getDriverStatusTimeline(DriverTruckAssignment $assignment)
    {
        $timeline = DriverStatusLog::with(['assignment.driver', 'assignment.truck'])
            ->where('driver_truck_assignment_id', $assignment->id)
            ->orderBy('changed_at', 'desc')
            ->get();

        return response()->json($timeline);
    }

    public function getBackhaulEligibleAssignments(Request $request)
    {
        $query = DriverTruckAssignment::with(['driver', 'truck', 'currentRegion'])
            ->backhaulEligible()
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($request->filled('region_id')) {
            $query->where('current_region_id', $request->region_id);
        }

        $assignments = $query->get();

        return response()->json($assignments);
    }

    public function checkBackhaulEligibility(DriverTruckAssignment $assignment)
    {
        try {
            $assignment->processAutomaticTransitions();
            
            $eligible = $assignment->current_status === DriverTruckAssignment::STATUS_BACKHAUL_ELIGIBLE;
            
            if ($eligible) {
                \Log::info('Backhaul eligibility check passed', [
                    'assignment_id' => $assignment->id,
                    'current_status' => $assignment->current_status
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Driver-truck set is eligible for backhaul assignments.',
                    'assignment' => $assignment->fresh()
                ]);
            } else {
                \Log::info('Backhaul eligibility check failed', [
                    'assignment_id' => $assignment->id,
                    'current_status' => $assignment->current_status,
                    'cooldown_ends_at' => $assignment->cooldown_ends_at,
                    'all_packages_delivered' => $assignment->allPackagesDelivered()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Driver-truck set is not yet eligible for backhaul.',
                    'current_status' => $assignment->current_status,
                    'cooldown_ends_at' => $assignment->cooldown_ends_at,
                    'is_final_cooldown' => $assignment->is_final_cooldown,
                    'all_packages_delivered' => $assignment->allPackagesDelivered()
                ], 422);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to check backhaul eligibility: ' . $e->getMessage(), [
                'assignment_id' => $assignment->id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to check backhaul eligibility: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getEligibilityReasons(DriverTruckAssignment $assignment): array
    {
        $reasons = [];
        
        if ($assignment->current_status !== DriverTruckAssignment::STATUS_COOLDOWN) {
            $reasons[] = 'Assignment must be in cooldown status';
        }
        
        if (!$assignment->allPackagesDelivered()) {
            $reasons[] = 'Not all packages are delivered';
        }
        
        if ($assignment->cooldown_ends_at && $assignment->cooldown_ends_at->isFuture()) {
            $reasons[] = 'Cooldown period has not finished yet';
        }
        
        return $reasons;
    }

    private function getAssignmentMetrics()
    {
        $metrics = [
            'total_assignments' => DriverTruckAssignment::where('is_active', true)->whereNull('deleted_at')->count(),
            'backhaul_eligible' => DriverTruckAssignment::backhaulEligible()->count(),
            'in_cooldown' => DriverTruckAssignment::inCooldown()->count(),
            'returning' => DriverTruckAssignment::where('current_status', DriverTruckAssignment::STATUS_RETURNING)->count(),
            'active_assignments' => DriverTruckAssignment::availableForAssignment()->count(),
        ];

        return $metrics;
    }

    public function showStatusTimeline(DriverTruckAssignment $assignment)
    {
        $timeline = DriverStatusLog::with(['assignment.driver', 'assignment.truck'])
            ->where('driver_truck_assignment_id', $assignment->id)
            ->orderBy('changed_at', 'desc')
            ->get();

        return Inertia::render('Admin/CargoAssignment/DriverTruckAssignments/StatusTimeline', [
            'assignment' => $assignment->load(['driver.employeeProfile', 'truck', 'region', 'currentRegion']),
            'timeline' => $timeline
        ]);
    }

    public function showBackhaulEligible(Request $request)
    {
        $query = DriverTruckAssignment::with(['driver.employeeProfile', 'truck', 'region', 'currentRegion'])
            ->backhaulEligible()
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($request->filled('region_id')) {
            $query->where('current_region_id', $request->region_id);
        }

        $assignments = $query->get();

        if ($request->expectsJson()) {
            return response()->json($assignments);
        }

        return Inertia::render('Admin/CargoAssignment/DriverTruckAssignments/BackhaulEligible', [
            'assignments' => $assignments,
            'filters' => $request->only(['region_id'])
        ]);
    }

    public function getAssignmentsByStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:active,cooldown,backhaul_eligible,returning'
        ]);

        $query = DriverTruckAssignment::with(['driver.employeeProfile', 'truck', 'region', 'currentRegion'])
            ->where('current_status', $request->status)
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        $assignments = $query->get();

        return response()->json($assignments);
    }

    public function forceCompleteAssignment(DriverTruckAssignment $assignment)
    {
        try {
            if (!$assignment->canBeForceCompleted()) {
                \Log::warning('Attempted to force complete assignment that cannot be force completed', [
                    'assignment_id' => $assignment->id,
                    'current_status' => $assignment->current_status,
                    'is_final_cooldown' => $assignment->is_final_cooldown,
                    'all_packages_delivered' => $assignment->allPackagesDelivered()
                ]);
                return redirect()->back()->with('error', 
                    'Assignment cannot be force-completed. It must be in final cooldown with all packages delivered.'
                );
            }

            $assignment->completeCooldown();
            
            \Log::info('Assignment force completed successfully', [
                'assignment_id' => $assignment->id,
                'completed_by' => Auth::id()
            ]);
            
            return redirect()->back()->with('success', 'Assignment force-completed successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to force complete assignment: ' . $e->getMessage(), [
                'assignment_id' => $assignment->id
            ]);
            return redirect()->back()->with('error', 'Failed to force complete assignment: ' . $e->getMessage());
        }
    }
}
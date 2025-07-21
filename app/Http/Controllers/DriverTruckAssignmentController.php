<?php

namespace App\Http\Controllers;

use App\Models\DriverTruckAssignment;
use App\Models\User;
use App\Models\Truck;
use App\Models\Region;
use App\Models\DriverRegionLog;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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
            'truck',
            'region'
        ])->latest();

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        } else {
            $query->active();
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

        // Defensive: Ensure driver and regionLogs are loaded and are collections
        $assignments->getCollection()->transform(function ($assignment) {
            // Get the current active delivery order for this assignment
            $activeOrder = $assignment->driver
                ? $assignment->driver->deliveryOrders()
                    ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                    ->latest()
                    ->first()
                : null;
            $deliveryOrderId = $activeOrder?->id;

            // --- NEW: If no active order, check for latest delivered order (for return verification) ---
            if (!$deliveryOrderId && $assignment->driver) {
                $recentDeliveredOrder = $assignment->driver->deliveryOrders()
                    ->where('status', 'delivered')
                    ->latest('updated_at')
                    ->first();
                if ($recentDeliveredOrder) {
                    $deliveryOrderId = $recentDeliveredOrder->id;
                }
            }

            $logs = collect();
            if (
                $assignment->driver &&
                isset($assignment->driver->regionLogs) &&
                $assignment->driver->regionLogs instanceof \Illuminate\Support\Collection
            ) {
                // Only consider logs for this region and current delivery order
                $logs = $assignment->driver->regionLogs
                    ->where('region_id', $assignment->region_id)
                    ->when($deliveryOrderId, function ($collection) use ($deliveryOrderId) {
                        return $collection->where('delivery_order_id', $deliveryOrderId);
                    })
                    ->whereIn('type', ['driver_returned', 'return_verified_by_staff'])
                    ->sortByDesc('logged_at')
                    ->values();
            }

            $latest = $logs->sortByDesc('logged_at')->first();

            // --- FIX: Only set 'Returned & Verified' if the latest log for this delivery order is 'return_verified_by_staff' ---
            if ($latest && $latest->type === 'return_verified_by_staff') {
                $assignment->return_status = 'Returned & Verified';
            } elseif ($latest && $latest->type === 'driver_returned') {
                $assignment->return_status = 'Pending Verification';
            } else {
                $assignment->return_status = 'Not Returned';
            }

            // Debug: log what is being set
            \Log::debug('Assignment status', [
                'id' => $assignment->id,
                'driver' => $assignment->driver?->name,
                'logs_count' => $logs->count(),
                'latest_type' => $latest?->type,
                'return_status' => $assignment->return_status
            ]);

            return $assignment;
        });

        return Inertia::render('Admin/CargoAssignment/DriverTruckAssignments/Index', [
            'assignments' => $assignments,
            'regions' => Region::active()->get(),
            'metrics' => [],
            'filters' => $request->only(['region_id', 'status', 'search'])
        ]);
    }

    public function getAvailableResources(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id'
        ]);

        // Debugging: Log the region ID being requested
        \Log::debug("Fetching resources for region: " . $request->region_id);

        // Get available drivers
        $drivers = User::with(['employeeProfile'])
            ->where('role', 'driver')
            ->where('is_active', true)
            ->whereHas('employeeProfile', function($q) use ($request) {
                $q->where('region_id', $request->region_id)
                  ->whereNull('archived_at');
            })
            ->whereDoesntHave('truckAssignments', function($q) {
                $q->where('is_active', true);
            })
            ->get();

        // Debugging: Log found drivers
        \Log::debug("Available drivers:", $drivers->toArray());

        // Get available trucks
        $trucks = Truck::where('region_id', $request->region_id)
            ->where('is_active', true)
            ->whereDoesntHave('driverAssignments', function($q) {
                $q->where('is_active', true);
            })
            ->where('status', Truck::STATUS_AVAILABLE) // Only show truly available trucks
            ->get();

        // Debugging: Log found trucks
        \Log::debug("Available trucks:", $trucks->toArray());

        return response()->json([
            'drivers' => $drivers,
            'trucks' => $trucks
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request) {
                    $driver = User::with('employeeProfile')->find($value);
                    if (!$driver || !$driver->employeeProfile || $driver->employeeProfile->region_id != $request->region_id) {
                        $fail('The selected driver is not from this region.');
                    }
                    if (DriverTruckAssignment::where('driver_id', $value)->active()->exists()) {
                        $fail('Driver is already assigned to another truck.');
                    }
                }
            ],
            'truck_id' => [
                'required',
                'exists:trucks,id',
                function ($attribute, $value, $fail) use ($request) {
                    $truck = Truck::find($value);
                    if (!$truck || $truck->region_id != $request->region_id) {
                        $fail('The selected truck is not from this region.');
                    }
                    if (DriverTruckAssignment::where('truck_id', $value)->active()->exists()) {
                        $fail('Truck is already assigned to another driver.');
                    }
                    if ($truck && $truck->status === Truck::STATUS_MAINTENANCE) {
                        $fail('Truck is currently in maintenance.');
                    }
                }
            ],
            'region_id' => 'required|exists:regions,id'
        ]);

        // Final check for race conditions
        if (DriverTruckAssignment::where('driver_id', $validated['driver_id'])
            ->orWhere('truck_id', $validated['truck_id'])
            ->active()
            ->exists()) {
            return back()->withErrors(['assignment' => 'Driver or truck is already assigned. Please refresh and try again.']);
        }

        $assignment = DriverTruckAssignment::create($validated);

        // Remove the direct status update - truck should remain available until assigned a delivery order
        // Truck status will be updated when a delivery order is assigned to it
        // Truck::where('id', $validated['truck_id'])->update(['status' => Truck::STATUS_ASSIGNED]);

        return redirect()->back()->with('success', 'Driver-Truck assignment created');
    }

    public function destroy(DriverTruckAssignment $assignment)
    {
        try {
            $assignment->deactivate();
            return redirect()->back()->with('success', 'Assignment deactivated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to deactivate assignment: ' . $e->getMessage());
        }
    }

    public function reactivate(DriverTruckAssignment $assignment)
    {
        // Check for conflicts
        $conflicts = DriverTruckAssignment::where(function($q) use ($assignment) {
                $q->where('driver_id', $assignment->driver_id)
                  ->orWhere('truck_id', $assignment->truck_id);
            })
            ->active()
            ->where('id', '!=', $assignment->id)
            ->exists();

        if ($conflicts) {
            return back()->withErrors([
                'assignment' => 'Cannot reactivate - driver or truck is already assigned to another active assignment'
            ]);
        }

        $assignment->update([
            'is_active' => true,
            'unassigned_at' => null
        ]);

        // Update truck status
        $assignment->truck()->update(['status' => Truck::STATUS_ASSIGNED]);

        return redirect()->back()->with('success', 'Assignment reactivated');
    }

    public function getByRegion(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id'
        ]);

        $assignments = DriverTruckAssignment::with(['driver', 'truck'])
            ->where('region_id', $request->region_id)
            ->active()
            ->get();

        return response()->json($assignments);
    }

    public function monitor(Request $request)
    {
        $query = DriverTruckAssignment::with([
            'driver.employeeProfile', // Add this line
            'truck',
            'region',
            'driver.deliveryOrders' => function($query) {
                $query->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
            }
        ]);

        // Filter by region if specified
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        // Search filter
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

        // Active only unless status specified
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        } else {
            $query->active();
        }

        $assignments = $query->latest()->paginate(10);

        return Inertia::render('Admin/DriverMonitoring/Index', [
            'assignments' => $assignments,
            'regions' => Region::active()->get(),
            'filters' => $request->only(['region_id', 'status', 'search']),
        ]);
    }

    public function confirmReturnToBase(DriverTruckAssignment $assignment)
    {
        \Log::debug('1 - Method entered', ['assignment' => $assignment->id]);
        
        try {
            \Log::debug('2 - Getting active orders');
            $activeOrder = $assignment->driver->deliveryOrders()
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                ->first();

            \Log::debug('3 - Active order found?', ['order' => $activeOrder?->id]);
            
            $deliveryOrderId = null;
            if ($activeOrder) {
                $deliveryOrderId = $activeOrder->id;
            } else {
                \Log::debug('4 - Checking recent delivered orders');
                $recentDeliveredOrder = $assignment->driver->deliveryOrders()
                    ->where('status', 'delivered')
                    ->latest('updated_at')
                    ->first();
                if ($recentDeliveredOrder) {
                    $deliveryOrderId = $recentDeliveredOrder->id;
                }
            }

            \Log::debug('5 - Creating driver return log', ['order_id' => $deliveryOrderId]);
            $log = DriverRegionLog::create([
                'driver_id' => $assignment->driver_id,
                'region_id' => $assignment->region_id,
                'delivery_order_id' => $deliveryOrderId,
                'type' => 'driver_returned',
                'logged_at' => now()
            ]);

            \Log::debug('6 - Log created', ['log_id' => $log->id]);

            \Log::debug('7 - Updating truck status');
            $assignment->truck()->update(['status' => Truck::STATUS_RETURNING]);

            \Log::debug('8 - Truck status updated');

            // Use redirect with flash for Inertia/Vue POST forms
            return back()->with('success', 'Return to base confirmed. Waiting for staff verification.');

        } catch (\Exception $e) {
            \Log::error('Error in confirmReturnToBase: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to confirm return to base.');
        }
    }

        public function verifyDriverReturn(DriverTruckAssignment $assignment)
    {
        // Get the latest driver return log
        $returnLog = $assignment->driver->regionLogs()
            ->where('type', 'driver_returned')
            ->latest()
            ->first();

        if (!$returnLog) {
            return back()->with('error', 'No return confirmation found from driver');
        }

        // Create verification log
        DriverRegionLog::create([
            'driver_id' => $assignment->driver_id,
            'region_id' => $assignment->region_id,
            'delivery_order_id' => $returnLog->delivery_order_id,
            'type' => 'return_verified_by_staff',
            'logged_at' => now()
        ]);

        // Update the delivery order's actual arrival time but keep status as 'delivered'

        // Reset truck status
        $assignment->truck()->update(['status' => Truck::STATUS_AVAILABLE]);

        return back()->with('success', 'Driver return verified. Delivery remains marked as delivered.');
    }
}
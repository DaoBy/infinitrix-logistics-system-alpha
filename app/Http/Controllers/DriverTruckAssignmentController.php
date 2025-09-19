<?php

namespace App\Http\Controllers;

use App\Models\DriverTruckAssignment;
use App\Models\User;
use App\Models\Truck;
use App\Models\Region;
use App\Models\DriverRegionLog;
use App\Models\DeliveryOrder;
use App\Models\Package;
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

        // Only apply status filter if not 'all'
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }
        // Remove else block that always applies ->active()

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

        // Set truck status to available (not assigned)
        $assignment->truck()->update(['status' => Truck::STATUS_AVAILABLE]);

        return redirect()->back()->with('success', 'Assignment reactivated and available for new cargo assignment.');
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

        // Only apply status filter if not 'all'
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }
        // Remove else block that always applies ->active()

        $assignments = $query->latest()->paginate(10);

        return Inertia::render('Admin/DriverMonitoring/Index', [
            'assignments' => $assignments,
            'regions' => Region::active()->get(),
            'filters' => $request->only(['region_id', 'status', 'search']),
        ]);
    }

    public function confirmReturnToBase(DriverTruckAssignment $assignment)
{
    \Log::debug('Confirm return to base initiated', ['assignment' => $assignment->id]);
    
    try {
        DB::beginTransaction();

        // Get the latest delivery order (active or delivered)
        $latestOrder = $assignment->driver->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'delivered'])
            ->latest()
            ->first();

        \Log::debug('Latest order found', ['order_id' => $latestOrder?->id, 'status' => $latestOrder?->status]);

        // Create return log
        $log = DriverRegionLog::create([
            'driver_id' => $assignment->driver_id,
            'region_id' => $assignment->region_id,
            'delivery_order_id' => $latestOrder?->id,
            'type' => 'driver_returned',
            'logged_at' => now()
        ]);

        \Log::debug('Return log created', ['log_id' => $log->id]);

        // Update truck status to returning
        $assignment->truck()->update(['status' => Truck::STATUS_RETURNING]);

        // Reset driver's location to home region
        if ($assignment->driver->employeeProfile && $assignment->driver->employeeProfile->region_id) {
            $assignment->driver->update([
                'current_region_id' => $assignment->driver->employeeProfile->region_id,
                'last_region_update' => now()
            ]);
            \Log::debug('Driver location reset', ['region_id' => $assignment->driver->employeeProfile->region_id]);
        }

        DB::commit();

        return redirect()->back()->with('success', 'Return to base confirmed. Waiting for staff verification.');

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Return confirmation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->back()->with('error', 'Failed to confirm return to base.');
    }
}

public function verifyDriverReturn(DriverTruckAssignment $assignment)
{
    \Log::debug('Verifying driver return', ['assignment' => $assignment->id]);

    try {
        DB::beginTransaction();

        // Get the latest return log
        $returnLog = $assignment->driver->regionLogs()
            ->where('region_id', $assignment->region_id)
            ->where('type', 'driver_returned')
            ->latest()
            ->first();

        if (!$returnLog) {
            \Log::warning('No return log found for verification');
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

        \Log::debug('Verification log created');

        // Update truck to available
        $assignment->truck()->update(['status' => Truck::STATUS_AVAILABLE]);

        // Update assignment with return status
        $assignment->update([
            'is_active' => false,
            'unassigned_at' => now(),
            'return_status' => 'Returned & Verified' // Ensure this is set
        ]);

        DB::commit();

        return redirect()->back()->with('success', 'Driver return verified. Truck is now available.');

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Return verification failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return redirect()->back()->with('error', 'Failed to verify driver return.');
    }
}
}
<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Models\TruckMaintenance;
use App\Models\TruckComponent;
use App\Models\Region; // <-- added
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class TruckController extends Controller
{
    public function index(Request $request)
    {
        $query = Truck::query()
            ->with([
                'maintenance' => fn($q) => $q->latest()->take(1),
                'region' // <-- Add this
            ])
            ->withCount('maintenance')
            ->where('is_active', true);

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortField = $request->get('sort_field', 'license_plate');
        $sortDirection = $request->get('sort_direction', 'asc');
        
        if (in_array($sortField, ['make', 'model', 'license_plate', 'status', 'volume_capacity', 'weight_capacity', 'last_maintenance_date'])) {
            if ($sortField === 'last_maintenance_date') {
                $query->leftJoin('truck_maintenances', function($join) {
                    $join->on('trucks.id', '=', 'truck_maintenances.truck_id')
                         ->whereRaw('truck_maintenances.id = (SELECT id FROM truck_maintenances WHERE truck_id = trucks.id ORDER BY maintenance_date DESC LIMIT 1)');
                })->orderBy('truck_maintenances.maintenance_date', $sortDirection);
            } else {
                $query->orderBy($sortField, $sortDirection);
            }
        }

        // Pagination
        $trucks = $query->paginate(10)->through(function ($truck) {
            return [
                'id' => $truck->id,
                'make' => $truck->make,
                'model' => $truck->model,
                'license_plate' => $truck->license_plate,
                'volume_capacity' => $truck->volume_capacity,
                'weight_capacity' => $truck->weight_capacity,
                'current_volume' => $truck->current_volume,
                'current_weight' => $truck->current_weight,
                'status' => $truck->status,
                'year' => $truck->year,
                'vin' => $truck->vin,
                'maintenance_count' => $truck->maintenance_count,
                'last_maintenance_date' => $truck->maintenance->first()?->maintenance_date?->format('Y-m-d'),
                'created_at' => $truck->created_at->format('Y-m-d H:i:s'),
                'region' => $truck->region ? [
                    'id' => $truck->region->id,
                    'name' => $truck->region->name,
                ] : null, // <-- Add this
            ];
        });

        return Inertia::render('Admin/Trucks/Index', [
            'trucks' => $trucks,
            'filters' => $request->only(['search', 'status']),
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
            'status' => session('status'),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Trucks/Create', [
            'default_status' => 'available',
            'status_options' => Truck::statuses(),
            'regions' => Region::all(), // <-- added
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'license_plate' => 'required|string|max:20|unique:trucks',
            'volume_capacity' => 'required|numeric|min:0',
            'weight_capacity' => 'required|numeric|min:0',
            'current_volume' => 'nullable|numeric|min:0',
            'current_weight' => 'nullable|numeric|min:0',
            'status' => 'required|in:'.implode(',', array_keys(Truck::statuses())),
            'year' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'vin' => 'nullable|string|max:50|unique:trucks',
            'purchase_date' => 'nullable|date|before_or_equal:today',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'region_id' => 'required|exists:regions,id', // <-- added
        ]);

        DB::beginTransaction();
        try {
            $truck = Truck::create(array_merge($validated, [
                'is_active' => true,
                'current_volume' => $validated['current_volume'] ?? 0,
                'current_weight' => $validated['current_weight'] ?? 0,
            ]));

            DB::commit();
            return redirect()->route('admin.trucks.index')
                ->with('success', 'Truck created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Truck creation failed: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Truck $truck)
    {
        $truck->load([
            'maintenance' => fn($q) => $q->latest()->take(5),
            'components'
        ]);

        return Inertia::render('Admin/Trucks/Show', [
            'truck' => $truck,
            'recent_maintenance' => $truck->maintenance,
            'status' => session('status'),
            'success' => session('success'),
            'error' => session('error'),
            'available_volume_capacity' => $truck->available_volume_capacity,
            'available_weight_capacity' => $truck->available_weight_capacity,
        ]);
    }

    public function edit(Truck $truck)
    {
        return Inertia::render('Admin/Trucks/Edit', [
            'truck' => $truck,
            'status_options' => Truck::statuses(),
            'regions' => Region::all(), // <-- added
        ]);
    }

    public function update(Request $request, Truck $truck)
    {
        $validated = $request->validate([
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'license_plate' => 'required|string|max:20|unique:trucks,license_plate,'.$truck->id,
            'volume_capacity' => 'required|numeric|min:0',
            'weight_capacity' => 'required|numeric|min:0',
            'current_volume' => 'nullable|numeric|min:0|lte:volume_capacity',
            'current_weight' => 'nullable|numeric|min:0|lte:weight_capacity',
            'status' => 'required|in:'.implode(',', array_keys(Truck::statuses())),
            'year' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'vin' => 'nullable|string|max:50|unique:trucks,vin,'.$truck->id,
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'region_id' => 'required|exists:regions,id', // <-- added
        ]);

        DB::beginTransaction();
        try {
            $truck->update($validated);

            DB::commit();
            return redirect()->route('admin.trucks.index')
                ->with('success', 'Truck updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Update failed: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function archive(Truck $truck)
    {
        DB::transaction(function () use ($truck) {
            $truck->update([
                'is_active' => false,
                'status' => 'under_repair',
            ]);
        });
    
        return redirect()->back()
            ->with('success', 'Truck archived successfully');
    }

    public function restore(Truck $truck)
    {
        DB::transaction(function () use ($truck) {
            $truck->update([
                'is_active' => true,
                'status' => Truck::STATUS_AVAILABLE, // Changed from 'available' to constant
            ]);
        });
    
        return redirect()->back()
            ->with('success', 'Truck restored successfully');
    }

    public function archived(Request $request)
    {
        $query = Truck::query()
            ->where('is_active', false)
            ->with(['maintenance' => fn($q) => $q->latest()->take(1)])
            ->withCount('maintenance');

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortField = $request->get('sort_field', 'archived_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        if (in_array($sortField, ['make', 'model', 'license_plate', 'status', 'archived_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        $trucks = $query->paginate(10)->through(function ($truck) {
            return [
                'id' => $truck->id,
                'make' => $truck->make,
                'model' => $truck->model,
                'license_plate' => $truck->license_plate,
                'status' => $truck->status,
                'maintenance_count' => $truck->maintenance_count,
                'last_maintenance_date' => $truck->maintenance->first()?->maintenance_date?->format('Y-m-d'),
                'created_at' => $truck->created_at->format('Y-m-d '),
                'archived_at' => $truck->updated_at->format('Y-m-d '), 
            ];
        });

        return Inertia::render('Admin/Trucks/Archived', [
            'trucks' => $trucks,
            'filters' => $request->only(['search', 'status']),
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
        ]);
    }

    public function destroy(Truck $truck)
    {
        if ($truck->is_active) {
            return back()->with('error', 'Only archived trucks can be deleted');
        }

        DB::beginTransaction();
        try {
            $truck->maintenance()->delete();
            $truck->forceDelete();
            
            DB::commit();
            return redirect()->route('admin.trucks.archived')
                   ->with('success', 'Truck permanently deleted');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Deletion failed: ' . $e->getMessage()]);
        }
    }

    public function maintenanceSchedule()
    {
        $trucks = Truck::with(['components' => function($query) {
            $query->where('condition', 'poor')
                ->orWhere('condition', 'needs_replacement')
                ->orWhere('last_maintenance_date', '<', now()->subMonths(6));
        }])->get();

        return Inertia::render('Admin/Trucks/MaintenanceSchedule', [
            'trucks' => $trucks,
            'urgent_count' => TruckComponent::whereIn('condition', ['poor', 'needs_replacement'])->count()
        ]);
    }

    public function capacityReport()
    {
        $trucks = Truck::selectRaw('
            trucks.*,
            (SELECT SUM((height * width * length)/1000000) 
             FROM packages 
             JOIN delivery_requests ON packages.delivery_request_id = delivery_requests.id
             JOIN delivery_orders ON delivery_orders.delivery_request_id = delivery_requests.id
             WHERE delivery_orders.truck_id = trucks.id
             AND delivery_orders.status IN ("assigned", "dispatched", "in_transit")
            ) as current_load_volume
        ')
        ->havingRaw('current_load_volume > 0 OR capacity > 0')
        ->get();

        return Inertia::render('Admin/Trucks/CapacityReport', [
            'trucks' => $trucks,
            'total_capacity' => Truck::sum('capacity'),
            'utilized_capacity' => $trucks->sum('current_load_volume')
        ]);
    }
}
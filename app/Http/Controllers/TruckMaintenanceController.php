<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Models\TruckMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TruckMaintenanceController extends Controller
{
    public function index(Request $request, Truck $truck)
    {
        $query = $truck->maintenance()->latest();

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('service_details', 'like', "%{$search}%")
                  ->orWhere('service_provider', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->where('maintenance_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('maintenance_date', '<=', $request->date_to);
        }

        // Cost range filter
        if ($request->has('cost_min') && $request->cost_min) {
            $query->where('cost', '>=', $request->cost_min);
        }
        if ($request->has('cost_max') && $request->cost_max) {
            $query->where('cost', '<=', $request->cost_max);
        }

        // Sorting
        $sortField = $request->get('sort_field', 'maintenance_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        if (in_array($sortField, ['maintenance_date', 'service_provider', 'cost'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        $maintenances = $query->paginate(10);

        return Inertia::render('Admin/Trucks/Maintenance/Index', [
            'truck' => $truck,
            'maintenances' => $maintenances,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'cost_min', 'cost_max']),
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
        ]);
    }


        protected function formatMaintenanceTypes(): array
        {
            return [
                ['value' => 'routine', 'text' => 'Routine Maintenance'],
                ['value' => 'repair', 'text' => 'Repair'],
                ['value' => 'component_replacement', 'text' => 'Component Replacement'],
                ['value' => 'inspection', 'text' => 'Inspection'],
            ];
        }
public function create(Truck $truck)
{
    return Inertia::render('Admin/Trucks/Maintenance/Create', [
        'truck' => $truck,
        'default_date' => now()->format('Y-m-d'),
        'components' => $truck->components,
        'maintenanceTypes' => [
            ['value' => 'routine', 'text' => 'Routine Maintenance'],
            ['value' => 'repair', 'text' => 'Repair'],
            ['value' => 'component_replacement', 'text' => 'Component Replacement'],
            ['value' => 'inspection', 'text' => 'Inspection'],
        ],
    ]);
}

     public function store(Request $request, Truck $truck)
    {
        $validated = $request->validate([
            'maintenance_date' => 'required|date|before_or_equal:today',
            'service_details' => 'required|string|max:1000',
            'cost' => 'required|numeric|min:0',
            'service_provider' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'type' => 'required|in:routine,repair,component_replacement,inspection',
            'component_id' => 'nullable|exists:truck_components,id',
        ]);

        DB::beginTransaction();
        try {
            $maintenance = $truck->maintenance()->create($validated);

            // Update component's last maintenance date if associated
            if ($request->component_id) {
                TruckComponent::where('id', $request->component_id)
                    ->update(['last_maintenance_date' => $request->maintenance_date]);
            }

            // Update truck status if it was under repair
            if ($truck->status === 'under_repair') {
                $truck->update(['status' => 'available']);
            }

            DB::commit();
            return redirect()->route('admin.trucks.maintenance.index', $truck->id)
                ->with('success', 'Maintenance record added successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Failed to add maintenance record: ' . $e->getMessage()])
                ->withInput();
        }
    }

public function edit(Truck $truck, TruckMaintenance $maintenance)
{
    return Inertia::render('Admin/Trucks/Maintenance/Edit', [
        'truck' => $truck,
        'maintenance' => $maintenance,
        'components' => $truck->components,
        'maintenance_types' => $this->formatMaintenanceTypes(),
    ]);
}

    public function update(Request $request, Truck $truck, TruckMaintenance $maintenance)
    {
      $validated = $request->validate([
        'maintenance_date' => 'required|date|before_or_equal:today',
        'service_details' => 'required|string|max:1000',
        'cost' => 'required|numeric|min:0',
        'service_provider' => 'required|string|max:255',
        'notes' => 'nullable|string|max:1000',
        'type' => 'required|in:routine,repair,component_replacement,inspection',
        'component_id' => 'nullable|exists:truck_components,id',
    ]);

        DB::beginTransaction();
        try {
            $maintenance->update($validated);
            
            DB::commit();
            return redirect()->route('admin.trucks.maintenance.index', $truck->id)
                ->with('success', 'Maintenance record updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Failed to update maintenance record: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Truck $truck, TruckMaintenance $maintenance)
    {
        DB::beginTransaction();
        try {
            $maintenance->delete();
            
            DB::commit();
            return redirect()->route('admin.trucks.maintenance.index', $truck->id)
                ->with('success', 'Maintenance record deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Failed to delete maintenance record: ' . $e->getMessage()]);
        }
    }
}
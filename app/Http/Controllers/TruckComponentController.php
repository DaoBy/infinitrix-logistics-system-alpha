<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Models\TruckComponent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TruckComponentController extends Controller
{
    protected function formatSelectOptions(array $options): array
    {
        return collect($options)->map(function ($text, $value) {
            return ['value' => $value, 'text' => $text];
        })->values()->toArray();
    }

    public function index(Truck $truck)
    {
        $components = $truck->components()
            ->with(['maintenanceRecords' => fn($q) => $q->latest()->take(3)])
            ->get();

        return Inertia::render('Admin/Trucks/Components/Index', [
            'truck' => $truck,
            'components' => $components,
            'component_types' => $this->formatSelectOptions(TruckComponent::getTypes()),
            'component_conditions' => $this->formatSelectOptions(TruckComponent::getConditions()),
            'status' => session('status'),
            'success' => session('success'),
            'error' => session('error'),
            'filters' => request()->only(['search', 'status', 'make', 'sort_field', 'sort_direction']),
        ]);
    }

public function create(Truck $truck)
{
    return Inertia::render('Admin/Trucks/Components/Create', [
        'truck' => $truck,
        'componentTypes' => array_map(function ($value, $key) {
            return ['value' => $key, 'text' => $value];
        }, TruckComponent::getTypes(), array_keys(TruckComponent::getTypes())),
        'conditionOptions' => array_map(function ($value, $key) {
            return ['value' => $key, 'text' => $value];
        }, TruckComponent::getConditions(), array_keys(TruckComponent::getConditions())),
    ]);
}

    public function store(Request $request, Truck $truck)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', array_keys(TruckComponent::getTypes())),
            'serial_number' => 'nullable|string|max:100',
            'installation_date' => 'required|date',
            'condition' => 'required|in:'.implode(',', array_keys(TruckComponent::getConditions())),
            'notes' => 'nullable|string',
        ]);

        $truck->components()->create($validated);

        return redirect()->route('admin.trucks.components.index', $truck->id)
            ->with('success', 'Component added successfully!');
    }

    public function show(Truck $truck, TruckComponent $component)
    {
        return Inertia::render('Admin/Trucks/Components/Show', [
            'truck' => $truck,
            'component' => $component->load('maintenanceRecords'),
            'component_types' => $this->formatSelectOptions(TruckComponent::getTypes()),
            'component_conditions' => $this->formatSelectOptions(TruckComponent::getConditions()),
        ]);
    }

    public function edit(Truck $truck, TruckComponent $component)
    {
        return Inertia::render('Admin/Trucks/Components/Edit', [
            'truck' => $truck,
            'component' => $component,
            'componentTypes' => $this->formatSelectOptions(TruckComponent::getTypes()),
            'conditionOptions' => $this->formatSelectOptions(TruckComponent::getConditions()),
        ]);
    }

    public function update(Request $request, Truck $truck, TruckComponent $component)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', array_keys(TruckComponent::getTypes())),
            'serial_number' => 'nullable|string|max:100',
            'last_maintenance_date' => 'nullable|date',
            'condition' => 'required|in:'.implode(',', array_keys(TruckComponent::getConditions())),
            'notes' => 'nullable|string',
        ]);

        $component->update($validated);

        return redirect()->route('admin.trucks.components.index', $truck->id)
            ->with('success', 'Component updated successfully!');
    }

    public function destroy(Truck $truck, TruckComponent $component)
    {
        $component->delete();

        return redirect()->route('admin.trucks.components.index', $truck->id)
            ->with('success', 'Component removed successfully!');
    }
}
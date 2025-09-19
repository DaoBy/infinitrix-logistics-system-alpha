<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegionController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Regions/Index', [
            'regions' => Region::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($region) => $region->toArray()),
            'status' => session('status'),
            'success' => session('success')
        ]);
    }

    public function getActiveRegions()
    {
        try {
            $regions = Region::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'created_at', 'updated_at']);
                
            return response()->json([
                'success' => true,
                'data' => $regions->map(function($region) {
                    return [
                        'id' => $region->id,
                        'name' => $region->name,
                        'created_at' => $region->created_at?->toISOString(),
                        'updated_at' => $region->updated_at?->toISOString()
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch regions'
            ], 500);
        }
    }

    public function create()
    {
        return Inertia::render('Admin/Regions/Create', [
            'mapsApiKey' => config('services.google_maps.key'),
            'status' => session('status')
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions',
            'warehouse_address' => 'required|string',
            'geographic_location.lat' => 'required|numeric|between:-90,90',
            'geographic_location.lng' => 'required|numeric|between:-180,180',
            'color_hex' => 'required|string|max:7|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ]);

        Region::create([
            'name' => $validated['name'],
            'warehouse_address' => $validated['warehouse_address'],
            'latitude' => $validated['geographic_location']['lat'],
            'longitude' => $validated['geographic_location']['lng'],
            'color_hex' => $validated['color_hex'],
            'is_active' => true
        ]);

        return redirect()->route('admin.regions.index')
            ->with('success', 'Region created successfully!');
    }

    public function show(Region $region)
    {
        return Inertia::render('Admin/Regions/Show', [
            'region' => $region->toArray(),
            'mapsApiKey' => config('services.google_maps.key'),
            'status' => session('status'),
            'success' => session('success')
        ]);
    }

    public function edit(Region $region)
    {
        return Inertia::render('Admin/Regions/Edit', [
            'region' => $region->toArray(),
            'mapsApiKey' => config('services.google_maps.key'),
            'status' => session('status')
        ]);
    }

    public function update(Request $request, Region $region)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name,'.$region->id,
            'warehouse_address' => 'required|string',
            'geographic_location.lat' => 'required|numeric|between:-90,90',
            'geographic_location.lng' => 'required|numeric|between:-180,180',
            'color_hex' => 'required|string|max:7|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
        ]);

        $region->update([
            'name' => $validated['name'],
            'warehouse_address' => $validated['warehouse_address'],
            'latitude' => $validated['geographic_location']['lat'],
            'longitude' => $validated['geographic_location']['lng'],
            'color_hex' => $validated['color_hex'],
        ]);

        return redirect()->route('admin.regions.index')
            ->with('success', 'Region updated successfully!');
    }

    public function archive(Region $region)
    {
        $region->update(['is_active' => false]);
        return back()->with('success', 'Region archived successfully');
    }

    public function restore(Region $region)
    {
        $region->update(['is_active' => true]);
        return back()->with('success', 'Region restored successfully');
    }

    public function archived()
    {
        return Inertia::render('Admin/Regions/Archived', [
            'regions' => Region::where('is_active', false)
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(fn($region) => $region->toArray()),
            'status' => session('status'),
            'success' => session('success')
        ]);
    }

    public function destroy(Region $region)
    {
        $region->delete();
        return redirect()->route('admin.regions.archived')
            ->with('success', 'Region permanently deleted');
    }
}
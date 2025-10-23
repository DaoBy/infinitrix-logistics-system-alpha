<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\RegionTravelDuration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegionController extends Controller
{
  public function index()
{
    return Inertia::render('Admin/Regions/Index', [
        'regions' => Region::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(10) 
            ->through(fn($region) => $region->toArray()),
        'status' => session('status'),
        'success' => session('success')
    ]);
}
   public function getActiveRegions()
{
    try {
        $regions = Region::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'warehouse_address', 'latitude', 'longitude', 'is_active', 'color_hex', 'created_at', 'updated_at']);
            
        return response()->json([
            'success' => true,
            'data' => $regions->map(function($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'warehouse_address' => $region->warehouse_address,
                    'latitude' => $region->latitude,
                    'longitude' => $region->longitude,
                    'is_active' => $region->is_active,
                    'color_hex' => $region->color_hex,
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
            'color_hex' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $region = Region::create([
            'name' => $validated['name'],
            'warehouse_address' => $validated['warehouse_address'],
            'latitude' => $validated['geographic_location']['lat'],
            'longitude' => $validated['geographic_location']['lng'],
            'color_hex' => $validated['color_hex'],
            'is_active' => true
        ]);

        // Auto-create travel durations to existing regions
        $this->createTravelDurationsForNewRegion($region);

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
            'color_hex' => 'required|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
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

    private function createTravelDurationsForNewRegion(Region $newRegion)
    {
        $existingRegions = Region::where('id', '!=', $newRegion->id)->get();
        $osrmService = new \App\Services\OSRMService();

        foreach ($existingRegions as $existingRegion) {
            // Calculate travel time using OSRM
            $minutes = $osrmService->getRouteTime($existingRegion->id, $newRegion->id);
            
            if ($minutes) {
                // Create bidirectional travel durations
                RegionTravelDuration::create([
                    'from_region_id' => $existingRegion->id,
                    'to_region_id' => $newRegion->id,
                    'estimated_minutes' => $minutes
                ]);
                
                RegionTravelDuration::create([
                    'from_region_id' => $newRegion->id,
                    'to_region_id' => $existingRegion->id,
                    'estimated_minutes' => $minutes
                ]);
            }
        }
    }
}
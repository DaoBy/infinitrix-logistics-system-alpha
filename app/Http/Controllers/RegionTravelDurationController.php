<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\RegionTravelDuration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegionTravelDurationController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/RegionDurations/Index', [
            'durations' => RegionTravelDuration::query()
                ->when($request->input('search'), function ($query, $search) {
                    $query->whereHas('fromRegion', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('toRegion', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                })
                ->with(['fromRegion:id,name', 'toRegion:id,name']) // Only fetch id and name for regions
                ->paginate(10)
                ->withQueryString(),
            'filters' => $request->only(['search']),
            'regions' => Region::all(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_region_id' => 'required|exists:regions,id',
            'to_region_id' => 'required|exists:regions,id|different:from_region_id',
            'estimated_minutes' => 'required|integer|min:1'
        ]);

        // Check for duplicate
        if (RegionTravelDuration::where([
            'from_region_id' => $validated['from_region_id'],
            'to_region_id' => $validated['to_region_id']
        ])->exists()) {
            return back()->withErrors(['route' => 'This route already exists']);
        }

        RegionTravelDuration::create($validated);

        return redirect()->back()->with('success', 'Duration added!');
    }

    public function update(Request $request, RegionTravelDuration $region_duration)
    {
        $validated = $request->validate([
            'estimated_minutes' => 'required|integer|min:1'
        ]);

        $region_duration->update($validated);

        return redirect()->back()->with('success', 'Duration updated!');
    }

    public function destroy(RegionTravelDuration $region_duration)
    {
        $region_duration->delete();
        return redirect()->back()->with('success', 'Duration deleted!');
    }
}
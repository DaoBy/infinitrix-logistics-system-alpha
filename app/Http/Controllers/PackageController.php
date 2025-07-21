<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Region;
use App\Models\PackageTransfer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::with(['deliveryRequest', 'currentRegion', 'transfers'])
            ->whereHas('deliveryRequest', function($query) {
                // FIX: Remove ->approved() so ALL requests (approved, completed, etc.) are included
                if (!auth()->user()->isAdmin()) {
                    $query->where(function($q) {
                        $q->where('sender_id', Auth::id())
                          ->orWhere('receiver_id', Auth::id())
                          ->orWhere('created_by', Auth::id());
                    });
                }
            })
            // DO NOT filter out delivered or completed packages here!
            // If you have a filter like ->where('status', '!=', 'delivered') or similar, REMOVE IT.
            ->latest()
            ->get()
            ->map(function ($package) {
                return $this->formatPackage($package);
            });

        return Inertia::render('Admin/Packages/Index', [
            'packages' => $packages,
            'package_statuses' => Package::getStatuses(),
            'status' => session('status'),
            'success' => session('success'),
        ]);
    }

    public function show(Package $package)
    {
        $this->authorizePackageAccess($package);
        
        $package->load([
            'deliveryRequest.sender', 
            'deliveryRequest.receiver',
            'transfers.fromRegion',
            'transfers.toRegion',
            'transfers.processor',
            'statusHistory.updatedBy',
            'waybill'
        ]);

        return Inertia::render('Admin/Packages/Show', [
            'package' => $this->formatPackage($package, true),
            'regions' => Region::active()->get(),
            'package_statuses' => Package::getStatuses(),
            'status' => session('status'),
            'success' => session('success'),
        ]);
    }

    public function transfer(Request $request, Package $package)
    {
        $this->authorizePackageAccess($package);
        
        $request->validate([
            'to_region_id' => 'required|exists:regions,id',
            'notes' => 'nullable|string',
            'is_return' => 'nullable|boolean'
        ]);

        if ($package->status === 'delivered' && !$request->is_return) {
            return back()->with('error', 'Delivered packages can only be returned');
        }

        $transfer = $package->transferToRegion(
            $request->to_region_id,
            auth()->user(),
            $request->notes,
            $request->is_return ?? false
        );

        return back()->with('success', 'Package transfer initiated!');
    }

    public function updateStatus(Request $request, Package $package)
    {
        $this->authorizePackageAccess($package);
        
        $request->validate([
            'status' => ['required', Rule::in(array_keys(Package::getStatuses()))],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $package->updateStatus(
            $request->status,
            auth()->user(),
            $request->remarks
        );

        return back()->with('success', 'Package status updated!');
    }

    public function markAsArrived(PackageTransfer $transfer)
    {
        $this->authorizePackageAccess($transfer->package);

        if ($transfer->package->status !== 'in_transit') {
            return back()->with('error', 'Only in-transit packages can be marked arrived');
        }

        $transfer->markAsArrived(auth()->user());

        return back()->with('success', 'Package marked as arrived!');
    }

    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'package_ids' => 'required|array',
            'package_ids.*' => 'exists:packages,id',
            'status' => ['required', Rule::in(array_keys(Package::getStatuses()))]
        ]);

        Package::whereIn('id', $request->package_ids)
            ->each(function($package) use ($request) {
                $package->updateStatus(
                    $request->status,
                    auth()->user(),
                    'Bulk status update'
                );
            });

        return back()->with('success', 'Bulk status update completed!');
    }

    protected function formatPackage(Package $package, bool $detailed = false): array
    {
        return [
            'id' => $package->id,
            'item_code' => $package->item_code,
            'item_name' => $package->item_name,
            'category' => $package->category,
            'status' => $package->status,
            'current_region' => $package->currentRegion ? [
                'id' => $package->currentRegion->id,
                'name' => $package->currentRegion->name
            ] : null,
            'waybill_number' => $package->waybill?->waybill_number,
        ];
    }

    protected function authorizePackageAccess(Package $package)
    {
        if (auth()->user()->isAdmin()) {
            return true;
        }

        $deliveryRequest = $package->deliveryRequest;

        if (!$deliveryRequest || 
            ($deliveryRequest->sender_id !== Auth::id() && 
             $deliveryRequest->receiver_id !== Auth::id() && 
             $deliveryRequest->created_by !== Auth::id())) {
            abort(403, 'Unauthorized action.');
        }
    }
}
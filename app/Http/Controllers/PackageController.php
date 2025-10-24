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
   public function index(Request $request)
    {
        $query = Package::with([
            'deliveryRequest.sender', 
            'deliveryRequest.receiver', 
            'currentRegion', 
            'transfers'
        ])
            ->whereHas('deliveryRequest', function($query) {
                if (!auth()->user()->isAdmin()) {
                    $query->where(function($q) {
                        $q->where('sender_id', Auth::id())
                          ->orWhere('receiver_id', Auth::id())
                          ->orWhere('created_by', Auth::id());
                    });
                }
            });

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                  ->orWhere('item_name', 'like', "%{$search}%")
                  ->orWhereHas('waybill', function($waybillQuery) use ($search) {
                      $waybillQuery->where('waybill_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('deliveryRequest', function($drQuery) use ($search) {
                      $drQuery->where('reference_number', 'like', "%{$search}%");
                  });
            });
        }

        // Apply category filter
        if ($request->has('category') && !empty($request->category)) {
            $query->where('category', $request->category);
        }

        // Apply status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortField = $request->get('sort', 'item_code');
        $sortDirection = $request->get('direction', 'asc');
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['item_code', 'item_name', 'category', 'status'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('item_code', 'asc');
        }

        $packages = $query->latest()->paginate(7);

        // Format packages for frontend - UPDATED to include delivery request data
        $formattedPackages = $packages->through(function ($package) {
            return $this->formatPackage($package);
        });

        return Inertia::render('Admin/Packages/Index', [
            'packages' => $formattedPackages,
            'filters' => $request->only(['search', 'category', 'status']),
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
    $baseData = [
        'id' => $package->id,
        'item_code' => $package->item_code,
        'item_name' => $package->item_name,
        'category' => $package->category,
        'status' => $package->status,
        'current_region' => $package->currentRegion ? [
            'id' => $package->currentRegion->id,
            'name' => $package->currentRegion->name,
            'color_hex' => $package->currentRegion->color_hex
        ] : null,
        'waybill_number' => $package->waybill?->waybill_number,
        'created_at' => $package->created_at?->toISOString(),
        'delivery_request_id' => $package->delivery_request_id,
    ];

    // Add delivery request data if relationship is loaded
    if ($package->relationLoaded('deliveryRequest')) {
        $baseData['delivery_request'] = $package->deliveryRequest ? [
            'id' => $package->deliveryRequest->id,
            'reference_number' => $package->deliveryRequest->reference_number,
            'sender' => $package->deliveryRequest->sender ? [
                'id' => $package->deliveryRequest->sender->id,
                'name' => $package->deliveryRequest->sender->name,
                'company_name' => $package->deliveryRequest->sender->company_name,
                'mobile' => $package->deliveryRequest->sender->mobile, // From Customer model
                'phone' => $package->deliveryRequest->sender->phone,   // From Customer model
                'email' => $package->deliveryRequest->sender->email,
            ] : null,
            'receiver' => $package->deliveryRequest->receiver ? [
                'id' => $package->deliveryRequest->receiver->id,
                'name' => $package->deliveryRequest->receiver->name,
                'company_name' => $package->deliveryRequest->receiver->company_name,
                'mobile' => $package->deliveryRequest->receiver->mobile, // From Customer model
                'phone' => $package->deliveryRequest->receiver->phone,   // From Customer model
                'email' => $package->deliveryRequest->receiver->email,
            ] : null,
        ] : null;
    }

    return $baseData;
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
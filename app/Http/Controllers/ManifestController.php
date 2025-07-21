<?php

namespace App\Http\Controllers;

use App\Models\Manifest;
use App\Models\Truck;
use App\Models\DeliveryOrder;
use App\Models\Package;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ManifestController extends Controller
{
    public function index()
    {
        $manifests = Manifest::with(['truck', 'driver.employeeProfile'])
            ->latest()
            ->paginate(10);

        // Get all trucks with active delivery orders
        $trucksWithAssignments = Truck::whereHas('deliveryOrders', function($query) {
                $query->where('status', 'assigned');
            })
            ->with(['deliveryOrders' => function($query) {
                $query->where('status', 'assigned')
                    ->with(['driver', 'deliveryRequest.packages']);
            }])
            ->get()
            ->filter(function($truck) {
                $currentPackageIds = $truck->deliveryOrders->flatMap(function($order) {
                    return $order->deliveryRequest->packages->pluck('id');
                })->values()->toArray();

                // Fix: Check if any of the current package IDs exist in any manifest's package_ids
                $hasActiveManifest = false;
                if (!empty($currentPackageIds)) {
                    $hasActiveManifest = Manifest::where('truck_id', $truck->id)
                        ->whereIn('status', ['draft', 'finalized'])
                        ->where(function($query) use ($currentPackageIds) {
                            foreach ($currentPackageIds as $pid) {
                                $query->orWhereJsonContains('package_ids', $pid);
                            }
                        })
                        ->exists();
                }

                return !$hasActiveManifest;
            })
            ->values()
            ->map(function($truck) {
                return [
                    'id' => $truck->id,
                    'license_plate' => $truck->license_plate,
                    'make' => $truck->make,
                    'model' => $truck->model,
                    'driver' => $truck->deliveryOrders->first()?->driver,
                    'delivery_orders_count' => $truck->deliveryOrders->count(),
                    'packages_count' => $truck->deliveryOrders->sum(function($order) {
                        return $order->deliveryRequest->packages->count();
                    }),
                    'status' => $truck->status
                ];
            });

        return Inertia::render('Admin/Manifest/Index', [
            'manifests' => $manifests,
            'trucksWithAssignments' => $trucksWithAssignments,
            'filters' => request()->all(['search'])
        ]);
    }

    public function create(Truck $truck)
    {
        // Get all current package IDs for this truck's active delivery orders
        $deliveries = $truck->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->with([
                'deliveryRequest' => function($query) {
                    $query->with(['packages' => function($q) {
                        $q->with('waybill');
                    }, 'dropOffRegion']);
                },
                'driver.employeeProfile'
            ])
            ->get();

        $currentPackageIds = $deliveries->flatMap(function ($order) {
            return $order->deliveryRequest->packages->pluck('id');
        })->values()->toArray();

        // Fix: Only block if a draft manifest exists for any of these packages
        $existingDraft = null;
        if (!empty($currentPackageIds)) {
            $existingDraft = Manifest::where('truck_id', $truck->id)
                ->where('status', 'draft')
                ->where(function($query) use ($currentPackageIds) {
                    foreach ($currentPackageIds as $pid) {
                        $query->orWhereJsonContains('package_ids', $pid);
                    }
                })
                ->first();
        }

        if ($existingDraft) {
            return redirect()->route('manifests.show', $existingDraft->id)
                ->with('info', 'A draft manifest already exists for this truck and these packages. Please finalize or delete it before creating a new one.');
        }

        $packages = $deliveries->flatMap(function ($order) {
            return $order->deliveryRequest->packages->map(function ($package) use ($order) {
                return [
                    'id' => $package->id,
                    'delivery_order_id' => $order->id,
                    'category' => $package->category,
                    'item_name' => $package->item_name,
                    // Use the waybill relationship if loaded, else null
                    'waybill_number' => $package->waybill?->waybill_number,
                    'drop_off_region' => $package->deliveryRequest->dropOffRegion?->name,
                    'volume' => $package->volume,
                    'weight' => $package->weight
                ];
            });
        });

        $manifestNumber = 'MNF-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        $driver = $deliveries->first()?->driver;
        $driverData = null;

        if ($driver) {
            $driverData = [
                'id' => $driver->id,
                'name' => $driver->name,
                // Fix: Always eager load employeeProfile and get the correct employee_id
                'employee_id' => $driver->employeeProfile->employee_id ?? 'N/A',
            ];
        }

        return Inertia::render('Admin/Manifest/Create', [
            'truck' => $truck,
            'driver' => $driverData,
            'packages' => $packages,
            'manifestNumber' => $manifestNumber
        ]);
    }

    public function store(Request $request, Truck $truck)
    {
        // Get all current package IDs for this truck's active delivery orders
        $deliveries = $truck->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->with(['deliveryRequest' => function($query) {
                $query->with('packages');
            }])
            ->get();

        $currentPackageIds = $deliveries->flatMap(function ($order) {
            return $order->deliveryRequest->packages->pluck('id');
        })->values()->toArray();

        // Fix: Only block if a draft manifest exists for any of these packages
        $existingDraft = null;
        if (!empty($currentPackageIds)) {
            $existingDraft = Manifest::where('truck_id', $truck->id)
                ->where('status', 'draft')
                ->where(function($query) use ($currentPackageIds) {
                    foreach ($currentPackageIds as $pid) {
                        $query->orWhereJsonContains('package_ids', $pid);
                    }
                })
                ->first();
        }

        if ($existingDraft) {
            return redirect()->route('manifests.show', $existingDraft->id)
                ->with('info', 'A draft manifest already exists for this truck and these packages. Please finalize or delete it before creating a new one.');
        }

        $request->validate([
            'manifest_number' => 'required|string|unique:manifests,manifest_number',
            'notes' => 'nullable|string'
        ]);

        $manifest = DB::transaction(function () use ($request, $truck) {
            // Load deliveries with their packages
            $deliveries = $truck->deliveryOrders()
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                ->with(['deliveryRequest' => function($query) {
                    $query->with('packages');
                }])
                ->get();

            $driver = $deliveries->first()?->driver;

            // Get all package IDs from all delivery requests
            $packageIds = $deliveries->flatMap(function ($order) {
                return $order->deliveryRequest->packages->pluck('id');
            })->toArray();

            $manifest = Manifest::create([
                'manifest_number' => $request->manifest_number,
                'truck_id' => $truck->id,
                'driver_id' => $driver?->id,
                'status' => 'draft',
                'package_ids' => $packageIds,
                'generated_by' => auth()->id(),
                'manifest_pdf_path' => null,
                'notes' => $request->notes
            ]);

            return $manifest;
        });

        return redirect()->route('manifests.show', $manifest)
            ->with('success', 'Manifest created successfully!');
    }

    public function show(Manifest $manifest)
    {
        $manifest->load(['truck', 'driver.employeeProfile', 'generator']);

        // Fix: Provide driver info with employee_id for the Vue page
        $driver = $manifest->driver;
        $driverData = null;
        if ($driver) {
            // Eager load employeeProfile if not loaded
            if (!$driver->relationLoaded('employeeProfile')) {
                $driver->load('employeeProfile');
            }
            $driverData = [
                'id' => $driver->id,
                'name' => $driver->name,
                'employee_id' => $driver->employeeProfile->employee_id ?? 'N/A',
            ];
        }

        $packages = Package::whereIn('id', $manifest->package_ids)
            ->with([
                'deliveryRequest.dropOffRegion',
                'waybill'
            ])
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    // Use delivery_request_id as delivery_order_id (1:1 mapping)
                    'delivery_order_id' => $package->delivery_request_id,
                    'category' => $package->category,
                    'item_name' => $package->item_name,
                    'waybill_number' => $package->waybill?->waybill_number,
                    'drop_off_region' => $package->deliveryRequest->dropOffRegion?->name,
                    'volume' => $package->volume,
                    'weight' => $package->weight
                ];
            });

        return Inertia::render('Admin/Manifest/Show', [
            'manifest' => $manifest,
            'truck' => $manifest->truck,
            'driver' => $driverData, // <-- pass driver info with employee_id
            'packages' => $packages,
        ]);
    }

    public function finalize(Manifest $manifest)
    {
        if ($manifest->status === 'finalized') {
            return back()->with('manifest_finalize_error', [
                'message' => 'Manifest is already finalized.'
            ]);
        }

        // Get all packages in the manifest
        $packages = \App\Models\Package::whereIn('id', $manifest->package_ids)->get();

        // Find packages with missing or pending waybills
        $invalidPackages = [];
        foreach ($packages as $package) {
            $waybill = \App\Models\Waybill::where('delivery_request_id', $package->delivery_request_id)->first();
            if (!$waybill || $waybill->status === 'pending') {
                $invalidPackages[] = [
                    'package_id' => $package->id,
                    'item_name' => $package->item_name ?? 'N/A',
                    'waybill_status' => $waybill?->status ?? 'none'
                ];
            }
        }

        if (count($invalidPackages) > 0) {
            return back()->with('manifest_finalize_error', [
                'message' => 'Some packages are missing valid waybills. Please resolve before finalizing.',
                'missing_packages' => $invalidPackages,
            ]);
        }

        // All waybills are valid - proceed with finalization
        $manifest->update([
            'status' => 'finalized',
            'finalized_by' => auth()->id()
        ]);

        return back()->with('success', 'Manifest finalized successfully!');
    }

    public function print(Manifest $manifest)
    {
        $manifest->load(['truck', 'driver.employeeProfile']);

        $packages = Package::whereIn('id', $manifest->package_ids)
            ->with(['deliveryRequest.dropOffRegion', 'waybill'])
            ->get();

        $filename = "manifest-{$manifest->manifest_number}.pdf";
        $path = "manifests/{$filename}";

        if (!\Illuminate\Support\Facades\Storage::exists($path)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('manifest', [
                'manifest' => $manifest,
                'packages' => $packages,
                'currentDate' => now()->format('F j, Y')
            ]);
            \Illuminate\Support\Facades\Storage::put($path, $pdf->output());
            $manifest->update(['manifest_pdf_path' => $path]);
        }

        return response()->file(\Illuminate\Support\Facades\Storage::path($path));
    }

    public function destroy(Manifest $manifest)
    {
        if ($manifest->status === 'finalized') {
            return back()->with('error', 'Cannot delete a finalized manifest');
        }

        $manifest->delete();

        return redirect()->route('manifests.index')
            ->with('success', 'Manifest deleted successfully');
    }
}
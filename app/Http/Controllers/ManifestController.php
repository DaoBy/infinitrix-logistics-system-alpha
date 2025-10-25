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
    try {
        \Log::info('ManifestController index method started');

        $manifests = Manifest::with(['truck', 'driver.employeeProfile'])
            ->latest()
            ->paginate(10);

        \Log::info('Manifests query executed', ['count' => $manifests->count()]);

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

                // Check if any of the current package IDs exist in any finalized manifest's package_ids
                $hasFinalizedManifest = false;
                if (!empty($currentPackageIds)) {
                    $hasFinalizedManifest = Manifest::where('truck_id', $truck->id)
                        ->where('status', 'finalized')
                        ->where(function($query) use ($currentPackageIds) {
                            foreach ($currentPackageIds as $pid) {
                                $query->orWhereJsonContains('package_ids', $pid);
                            }
                        })
                        ->exists();
                }

                return !$hasFinalizedManifest;
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

        \Log::info('Trucks with assignments processed', ['count' => $trucksWithAssignments->count()]);

        $responseData = [
            'manifests' => $manifests,
            'trucksWithAssignments' => $trucksWithAssignments,
            'filters' => request()->all(['search'])
        ];

        \Log::info('ManifestController index method completed successfully');

        return Inertia::render('Admin/Manifest/Index', $responseData);

    } catch (\Exception $e) {
        \Log::error('Manifest Controller Error Details:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Detailed debug output
        $debugInfo = [
            'error' => $e->getMessage(),
            'file' => $e->getFile(), 
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
            'previous' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null
        ];

        \Log::error('Full Debug Info:', $debugInfo);

        // Return the error details in development
        if (app()->environment('local')) {
            return response()->json([
                'error' => 'Manifest Controller Error',
                'details' => $debugInfo
            ], 500);
        }

        // In production, show a generic error
        return redirect()->back()->with('error', 'Unable to load manifests. Please try again.');
    }
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

        // Check if packages already exist in any finalized manifest
        $existingFinalized = null;
        if (!empty($currentPackageIds)) {
            $existingFinalized = Manifest::where('truck_id', $truck->id)
                ->where('status', 'finalized')
                ->where(function($query) use ($currentPackageIds) {
                    foreach ($currentPackageIds as $pid) {
                        $query->orWhereJsonContains('package_ids', $pid);
                    }
                })
                ->first();
        }

        if ($existingFinalized) {
            return redirect()->route('manifests.show', $existingFinalized->id)
                ->with('info', 'A finalized manifest already exists for this truck and these packages.');
        }

        $packages = $deliveries->flatMap(function ($order) {
            return $order->deliveryRequest->packages->map(function ($package) use ($order) {
                return [
                    'id' => $package->id,
                    'item_code' => $package->item_code,
                    'delivery_request_id' => $package->delivery_request_id,
                    'delivery_request_reference' => $package->deliveryRequest->reference_number, // Added reference number
                    'category' => $package->category,
                    'item_name' => $package->item_name,
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

        // Check if packages already exist in any finalized manifest
        $existingFinalized = null;
        if (!empty($currentPackageIds)) {
            $existingFinalized = Manifest::where('truck_id', $truck->id)
                ->where('status', 'finalized')
                ->where(function($query) use ($currentPackageIds) {
                    foreach ($currentPackageIds as $pid) {
                        $query->orWhereJsonContains('package_ids', $pid);
                    }
                })
                ->first();
        }

        if ($existingFinalized) {
            return redirect()->route('manifests.show', $existingFinalized->id)
                ->with('info', 'A finalized manifest already exists for this truck and these packages.');
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

            // Create finalized manifest directly
            $manifest = Manifest::create([
                'manifest_number' => $request->manifest_number,
                'truck_id' => $truck->id,
                'driver_id' => $driver?->id,
                'status' => 'finalized', // Create as finalized directly
                'package_ids' => $packageIds,
                'generated_by' => auth()->id(),
                'finalized_by' => auth()->id(), // Set finalized_by as well
                'manifest_pdf_path' => null,
                'notes' => $request->notes
            ]);

            return $manifest;
        });

        return redirect()->route('manifests.show', $manifest)
            ->with('success', 'Manifest created and finalized successfully!');
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
                    'item_code' => $package->item_code,
                    'delivery_request_id' => $package->delivery_request_id,
                    'delivery_request_reference' => $package->deliveryRequest->reference_number, // Added reference number
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
            'driver' => $driverData,
            'packages' => $packages,
        ]);
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
        // Allow deletion of finalized manifests if needed
        // You might want to add additional checks here based on your business logic
        $manifest->delete();

        return redirect()->route('manifests.index')
            ->with('success', 'Manifest deleted successfully');
    }
}
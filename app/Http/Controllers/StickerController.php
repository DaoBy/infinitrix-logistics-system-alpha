<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\DeliveryRequest;
use App\Models\Waybill;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class StickerController extends Controller
{
    /**
     * Display the main sticker management index page.
     */
    public function index(Request $request)
    {
        $query = Package::with([
            'deliveryRequest.receiver',
            'deliveryRequest.dropOffRegion',
            'deliveryRequest.waybill',
            'currentRegion',
            'printedBy'
        ])
        ->whereHas('deliveryRequest.waybill')
        ->whereHas('deliveryRequest', function ($q) {
            $q->where('status', 'approved');
        });

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                  ->orWhere('item_name', 'like', "%{$search}%")
                  ->orWhereHas('deliveryRequest', function ($q2) use ($search) {
                      $q2->where('reference_number', 'like', "%{$search}%")
                         ->orWhereHas('receiver', function ($q3) use ($search) {
                             $q3->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                         });
                  })
                  ->orWhereHas('deliveryRequest.waybill', function ($q2) use ($search) {
                      $q2->where('waybill_number', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sticker status filter based on tab
        $tab = $request->get('tab', 'not_printed');
        if ($tab === 'not_printed') {
            $query->whereNull('sticker_printed_at');
        } elseif ($tab === 'printed') {
            $query->whereNotNull('sticker_printed_at');
        }

        // Apply region filter
        if ($request->has('region_id') && !empty($request->region_id)) {
            $query->whereHas('deliveryRequest.dropOffRegion', function ($q) use ($request) {
                $q->where('id', $request->region_id);
            });
        }

        // Apply sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['item_code', 'item_name', 'created_at', 'sticker_printed_at'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Get pagination settings
        $perPage = $request->get('per_page', 15);

        // Get statistics for both tabs
        $stats = $this->getStickerStats();

        // Paginate results
        $packages = $query->paginate($perPage)
            ->withQueryString();

        // Format packages for frontend
        $formattedPackages = $packages->through(function ($package) {
            return $this->formatPackageForSticker($package);
        });

        $regions = Region::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color_hex']);

        return Inertia::render('Admin/Stickers/Index', [
            'packages' => $formattedPackages,
            'regions' => $regions,
            'stats' => $stats,
            'filters' => $request->only(['search', 'region_id', 'per_page', 'tab']),
            'status' => session('status'),
            'success' => session('success'),
            'error' => session('error'),
        ]);
    }

    /**
     * Get sticker statistics for both tabs
     */
    protected function getStickerStats()
    {
        $totalPackages = Package::whereHas('deliveryRequest.waybill')
            ->whereHas('deliveryRequest', function ($q) {
                $q->where('status', 'approved');
            })
            ->count();

        $printedPackages = Package::whereHas('deliveryRequest.waybill')
            ->whereHas('deliveryRequest', function ($q) {
                $q->where('status', 'approved');
            })
            ->whereNotNull('sticker_printed_at')
            ->count();

        $notPrintedPackages = $totalPackages - $printedPackages;

        return [
            'not_printed_total' => $notPrintedPackages,
            'printed_total' => $printedPackages,
            'total_packages' => $totalPackages,
            'print_rate' => $totalPackages > 0 ? round(($printedPackages / $totalPackages) * 100, 2) : 0,
        ];
    }

    /**
     * Print sticker for a single package.
     */
   public function print(Package $package)
    {
        // Only validate that package has a waybill and approved delivery request
        if (!$package->deliveryRequest || !$package->deliveryRequest->waybill) {
            return redirect()->back()
                ->with('error', 'Cannot print sticker - package does not have a waybill');
        }

        // Check if delivery request is approved (allow both prepaid and postpaid)
        if ($package->deliveryRequest->status !== 'approved') {
            return redirect()->back()
                ->with('error', 'Cannot print sticker - delivery request is not approved');
        }

        // Generate PDF
        $pdf = $this->generateStickerPdf($package);

        // Update sticker tracking
        $package->update([
            'sticker_printed_at' => now(),
            'sticker_printed_by' => auth()->id(),
        ]);

        // Return PDF with proper headers
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="sticker-'.$package->item_code.'.pdf"',
        ]);
    }

    /**
     * Bulk print stickers for multiple packages.
     */
    public function bulkPrint(Request $request)
    {
        // Get package IDs from query parameters instead of POST data
        $packageIds = $request->input('package_ids', []);
        
        if (empty($packageIds)) {
            return redirect()->back()->with('error', 'No packages selected for printing');
        }

        $packages = Package::with([
                'deliveryRequest.receiver',
                'deliveryRequest.dropOffRegion',
                'deliveryRequest.waybill',
                'currentRegion'
            ])
            ->whereIn('id', $packageIds)
            ->get();

        if ($packages->isEmpty()) {
            return redirect()->back()->with('error', 'No valid packages selected for printing');
        }

        // Generate combined PDF
        $pdf = $this->generateBulkStickersPdf($packages);

        // Update sticker tracking for all packages
        Package::whereIn('id', $packageIds)->update([
            'sticker_printed_at' => now(),
            'sticker_printed_by' => auth()->id(),
        ]);

        // Return PDF with proper headers
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="stickers-bulk-'.now()->format('Ymd-His').'.pdf"',
        ]);
    }

    /**
     * Print all stickers for a specific delivery request.
     */
    public function printForDeliveryRequest(DeliveryRequest $deliveryRequest)
    {
        if (!$deliveryRequest->waybill) {
            return redirect()->back()
                ->with('error', 'Cannot print stickers - delivery request does not have a waybill');
        }

        $packages = $deliveryRequest->packages()->with([
            'deliveryRequest.receiver',
            'deliveryRequest.dropOffRegion',
            'deliveryRequest.waybill',
            'currentRegion'
        ])->get();

        if ($packages->isEmpty()) {
            return redirect()->back()->with('error', 'No packages found for this delivery request');
        }

        // Generate combined PDF
        $pdf = $this->generateBulkStickersPdf($packages);

        // Update sticker tracking for all packages
        $deliveryRequest->packages()->update([
            'sticker_printed_at' => now(),
            'sticker_printed_by' => auth()->id(),
        ]);

        // Return PDF with proper headers - FOLLOWING WAYBILL PATTERN
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="stickers-'.$deliveryRequest->reference_number.'.pdf"',
        ]);
    }

    /**
     * Generate PDF for a single sticker.
     */
    protected function generateStickerPdf(Package $package)
    {
        $data = $this->formatPackageForSticker($package);

        // Use exact 80mm x 40mm dimensions for single sticker
        // 80mm = 226.77 points, 40mm = 113.39 points (1mm = 2.83465 points)
        $pdf = Pdf::loadView('stickers.single', [
            'package' => $data,
            'printDate' => now()->format('Y-m-d H:i'),
            'printedBy' => auth()->user()->name,
        ])->setPaper([0, 0, 226.77, 113.39], 'portrait'); // 80mm x 40mm in points

        // Set margins to zero for exact sizing
        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-right', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);

        return $pdf;
    }

    /**
     * Generate PDF for multiple stickers.
     */
    protected function generateBulkStickersPdf($packages)
    {
        $formattedPackages = $packages->map(function ($package) {
            return $this->formatPackageForSticker($package);
        });

        // Use A4 paper for bulk printing with proper margins
        $pdf = Pdf::loadView('stickers.bulk', [
            'packages' => $formattedPackages,
            'printDate' => now()->format('Y-m-d H:i'),
            'printedBy' => auth()->user()->name,
        ])->setPaper('a4', 'portrait');

        // Set minimal margins for optimal sticker placement
        $pdf->setOption('margin-top', 5);
        $pdf->setOption('margin-right', 5);
        $pdf->setOption('margin-bottom', 5);
        $pdf->setOption('margin-left', 5);

        return $pdf;
    }

    /**
     * Format package data for sticker display.
     */
    protected function formatPackageForSticker(Package $package)
    {
        $deliveryRequest = $package->deliveryRequest;
        $waybill = $deliveryRequest->waybill;
        $receiver = $deliveryRequest->receiver;
        $region = $deliveryRequest->dropOffRegion;

        return [
            'id' => $package->id,
            'item_code' => $package->item_code,
            'item_name' => $package->item_name,
            'weight' => $package->weight,
            'dimensions' => "{$package->height} x {$package->width} x {$package->length} cm",
            'volume' => $package->volume,
            'category' => $package->category,
            'current_region' => $package->currentRegion ? $package->currentRegion->name : 'N/A',
            'sticker_printed_at' => $package->sticker_printed_at,
            'sticker_printed_by' => $package->printedBy ? $package->printedBy->name : null,
            
            'delivery_request' => [
                'id' => $deliveryRequest->id,
                'reference_number' => $deliveryRequest->reference_number,
                'payment_type' => $deliveryRequest->payment_type,
                'payment_method' => $deliveryRequest->payment_method,
            ],
            
            'waybill' => $waybill ? [
                'id' => $waybill->id,
                'waybill_number' => $waybill->waybill_number,
                'invoice_number' => $waybill->invoice_number,
            ] : null,
            
            'receiver' => $receiver ? [
                'name' => $receiver->company_name ?: "{$receiver->first_name} {$receiver->last_name}",
                'address' => $this->formatReceiverAddress($receiver),
            ] : null,
            
            'destination_region' => $region ? [
                'id' => $region->id,
                'name' => $region->name,
                'color_hex' => $region->color_hex,
                'warehouse_address' => $region->warehouse_address,
            ] : null,
        ];
    }

    /**
     * Format receiver address for sticker display.
     */
    protected function formatReceiverAddress($receiver)
    {
        $addressParts = [
            $receiver->building_number,
            $receiver->street,
            $receiver->barangay,
            $receiver->city,
            $receiver->province,
            $receiver->zip_code ? "{$receiver->zip_code}" : null,
        ];

        return implode(', ', array_filter($addressParts));
    }

    /**
     * Mark sticker as not printed (reprint allowed).
     */
    public function reset(Package $package)
    {
        $package->update([
            'sticker_printed_at' => null,
            'sticker_printed_by' => null,
        ]);

        return redirect()->back()
            ->with('success', 'Sticker status reset - reprint is now allowed');
    }

    /**
     * Get sticker statistics for dashboard.
     */
    public function statistics()
    {
        $stats = $this->getStickerStats();

        $packagesByRegion = Package::with('deliveryRequest.dropOffRegion')
            ->whereHas('deliveryRequest.waybill')
            ->whereHas('deliveryRequest', function ($q) {
                $q->where('status', 'approved');
            })
            ->select('regions.name as region_name', 'regions.color_hex', DB::raw('COUNT(packages.id) as package_count'))
            ->join('delivery_requests', 'packages.delivery_request_id', '=', 'delivery_requests.id')
            ->join('regions', 'delivery_requests.drop_off_region_id', '=', 'regions.id')
            ->groupBy('regions.name', 'regions.color_hex')
            ->orderBy('package_count', 'desc')
            ->get();

        $stats['packages_by_region'] = $packagesByRegion;

        return response()->json($stats);
    }
}
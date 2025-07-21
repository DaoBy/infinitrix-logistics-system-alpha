<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRequest;
use App\Models\Truck;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Waybill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    // Cache duration in minutes
    const CACHE_DURATION = 60;

    public function deliveryReport()
    {
        $stats = Cache::remember('delivery_stats', self::CACHE_DURATION, function () {
            return [
                'completed' => DeliveryRequest::completed()->count(),
                'pending' => DeliveryRequest::pending()->count(),
                'revenue' => DeliveryRequest::completed()->sum('total_price'),
                'revenue_trend' => $this->getRevenueTrend(),
                'delivery_performance' => $this->getDeliveryPerformance(),
                'waybills_generated' => $this->getWaybillStats()
            ];
        });

        return Inertia::render('Admin/Reports/DeliveryReport', [
            'stats' => $stats,
            'exportUrl' => route('reports.delivery.export') // Add export link
        ]);
    }

    public function financialReport()
    {
        $cacheKey = 'financial_report_' . date('Y-m');
        $reportData = Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            return [
                'payments' => Payment::selectRaw('
                    SUM(amount) as total,
                    COUNT(*) as count,
                    method,
                    DATE_FORMAT(created_at, "%Y-%m") as month
                ')
                ->groupBy('method', 'month')
                ->orderBy('month', 'desc')
                ->paginate(15), // Added pagination
                'verified_payments' => Payment::whereHas('verification')->sum('amount'),
                'cod_collected' => $this->getCODCollectionStats()
            ];
        });

        return Inertia::render('Admin/Reports/FinancialReport', array_merge($reportData, [
            'exportUrl' => route('reports.financial.export')
        ]));
    }

    public function generateTruckManifest(Truck $truck)
    {
        $manifest = Cache::remember("truck_manifest_{$truck->id}", 30, function () use ($truck) {
            return $truck->deliveryOrders()
                ->with(['deliveryRequest.packages.waybill', 'driver'])
                ->whereIn('status', ['dispatched', 'in_transit'])
                ->get()
                ->flatMap(function ($order) {
                    return $order->deliveryRequest->packages->map(function ($package) use ($order) {
                        return [
                            'package_id' => $package->id,
                            'waybill_number' => $package->waybill?->waybill_number,
                            'item_name' => $package->item_name,
                            'destination' => $order->deliveryRequest->dropOffRegion?->name,
                            'driver' => $order->driver?->name
                        ];
                    });
                });
        });

        Report::create([
            'type' => 'truck_manifest',
            'generated_by' => auth()->id(),
            'parameters' => [
                'truck_id' => $truck->id,
                'package_count' => $manifest->count()
            ]
        ]);

        return Inertia::render('Admin/Reports/Manifest', [
            'manifest' => $manifest,
            'truck' => $truck,
            'exportUrl' => route('reports.manifest.export', ['truck' => $truck->id])
        ]);
    }

    // New export methods
    public function exportDeliveryReport(): StreamedResponse
    {
        $data = Cache::remember('delivery_export', 10, function () {
            return DeliveryRequest::with(['sender', 'receiver'])
                ->completed()
                ->orderBy('created_at', 'desc')
                ->get();
        });

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Sender', 'Receiver', 'Amount', 'Completed At']);
            
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->sender?->name,
                    $row->receiver?->name,
                    $row->total_price,
                    $row->deliveryOrder?->actual_arrival
                ]);
            }
            
            fclose($handle);
        }, 'delivery-report-' . now()->format('Y-m-d') . '.csv');
    }

    public function exportFinancialReport(): StreamedResponse
    {
        $data = Payment::with(['deliveryRequest'])
            ->orderBy('created_at', 'desc')
            ->cursor();

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Amount', 'Method', 'Status', 'Request ID', 'Date']);
            
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->amount,
                    $row->method,
                    $row->status,
                    $row->deliveryRequest?->id,
                    $row->created_at
                ]);
            }
            
            fclose($handle);
        }, 'financial-report-' . now()->format('Y-m-d') . '.csv');
    }

    public function exportManifestReport(Truck $truck): StreamedResponse
    {
        $manifest = $this->generateTruckManifest($truck)->getData()['manifest'];

        return response()->streamDownload(function () use ($manifest) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Package ID', 'Waybill Number', 'Item', 'Destination', 'Driver']);
            
            foreach ($manifest as $row) {
                fputcsv($handle, [
                    $row['package_id'],
                    $row['waybill_number'],
                    $row['item_name'],
                    $row['destination'],
                    $row['driver']
                ]);
            }
            
            fclose($handle);
        }, 'manifest-' . $truck->license_plate . '-' . now()->format('Y-m-d') . '.csv');
    }

    private function getRevenueTrend()
    {
        return DeliveryRequest::selectRaw('
            SUM(total_price) as revenue,
            DATE_FORMAT(created_at, "%Y-%m") as month
        ')
        ->where('status', 'completed')
        ->groupBy('month')
        ->orderBy('month')
        ->limit(6)
        ->get()
        ->map(function ($item) {
            return [
                'month' => Carbon::createFromFormat('Y-m', $item->month)->format('M Y'),
                'revenue' => $item->revenue
            ];
        });
    }

    private function getDeliveryPerformance()
    {
        return DeliveryRequest::selectRaw('
            COUNT(*) as count,
            AVG(TIMESTAMPDIFF(HOUR, created_at, delivery_orders.actual_arrival)) as avg_hours,
            status
        ')
        ->join('delivery_orders', 'delivery_orders.delivery_request_id', '=', 'delivery_requests.id')
        ->whereNotNull('delivery_orders.actual_arrival')
        ->groupBy('status')
        ->get();
    }

    // New helper methods
    private function getWaybillStats()
    {
        return DB::table('waybills')
            ->selectRaw('COUNT(*) as total, DATE(created_at) as date')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();
    }

    private function getCODCollectionStats()
    {
        return Payment::where('method', 'cash')
            ->selectRaw('SUM(amount) as total, DATE(created_at) as date')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();
    }
}
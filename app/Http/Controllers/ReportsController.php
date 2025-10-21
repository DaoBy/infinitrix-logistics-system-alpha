<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRequest;
use App\Models\DeliveryOrder;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Manifest;
use App\Models\Waybill;
use App\Models\Region;
use App\Models\Refund;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * DASHBOARD - Main Reports Page
     */
       public function dashboard(Request $request)
    {
        $dateFilter = $request->input('date_filter', 'this_month');
        $dateRange = $this->getDateRange($dateFilter);
        
        return Inertia::render('Admin/Reports/Dashboard', [
            'quickStats' => $this->getQuickStats($dateRange),
            'charts' => $this->getChartData($dateRange),
            'filters' => ['date_filter' => $dateFilter]
        ]);
    }

    /**
     * DOCUMENTS - Manifests & Waybills Page
     */
    public function documents(Request $request)
    {
        $manifests = Manifest::with(['truck', 'driver'])
            ->latest()
            ->paginate(10);

        $waybills = Waybill::with(['deliveryRequest.sender', 'generator'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Reports/Documents', [
            'manifests' => $manifests,
            'waybills' => $waybills
        ]);
    }

    /**
     * QUICK STATS - For Dashboard Cards
     */
    private function getQuickStats($dateRange)
    {
        $totalDeliveries = DeliveryRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();
        $completedDeliveries = DeliveryRequest::where('status', 'completed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();
        
        $successRate = $totalDeliveries > 0 ? round(($completedDeliveries / $totalDeliveries) * 100, 2) : 0;

        // Calculate refund metrics - ONLY PROCESSED REFUNDS
        $totalRefunds = Refund::where('status', 'processed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();
            
        $refundAmount = Refund::where('status', 'processed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('refund_amount');

        // Calculate net revenue (payments minus refunds)
        $grossRevenue = Payment::where('status', 'verified')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');

        $netRevenue = $grossRevenue - $refundAmount;

        return [
            'total_deliveries' => [
                'value' => $totalDeliveries,
                'label' => 'Total Deliveries'
            ],
            'success_rate' => [
                'value' => $successRate . '%',
                'label' => 'Success Rate'
            ],
            'total_revenue' => [
                'value' => '₱' . number_format($netRevenue, 2),
                'label' => 'Net Revenue'
            ],
            'total_refunds' => [
                'value' => '₱' . number_format($refundAmount, 2),
                'label' => 'Total Refunds'
            ],
            'refund_count' => [
                'value' => $totalRefunds,
                'label' => 'Refunds Processed'
            ],
        ];
    }

    /**
     * CHART DATA - For Dashboard Charts/Displays
     */
      private function getChartData($dateRange)
    {
        return [
            'package_types' => $this->getPackageTypesChart($dateRange),
            'top_locations' => $this->getTopLocationsChart($dateRange),
            'delivery_volume' => $this->getDeliveryVolumeChart($dateRange),
            'revenue_trend' => $this->getRevenueTrendChart($dateRange),
            'refund_analytics' => $this->getRefundAnalyticsChart($dateRange),
        ];
    }


     /**
     * REFUND ANALYTICS CHART DATA
     */
    private function getRefundAnalyticsChart($dateRange)
    {
        $data = Refund::where('status', 'processed') // ONLY PROCESSED REFUNDS
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('reason, COUNT(*) as count, SUM(refund_amount) as total_amount')
            ->groupBy('reason')
            ->get()
            ->map(function($item) {
                return [
                    'label' => Refund::REASONS[$item->reason] ?? ucfirst($item->reason),
                    'value' => $item->count,
                    'amount' => (float) $item->total_amount
                ];
            });

        return [
            'title' => 'Refund Reasons',
            'data' => $data
        ];
    }


    /**
     * PACKAGE TYPES CHART DATA
     */
     private function getPackageTypesChart($dateRange)
    {
        $data = Package::whereHas('deliveryRequest', function($query) use ($dateRange) {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            })
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->get()
            ->map(function($item) {
                return [
                    'label' => $item->category ? ucfirst($item->category) : 'Unknown',
                    'value' => $item->count
                ];
            });

        return [
            'title' => 'Package Types',
            'data' => $data
        ];
    }

    /**
     * TOP LOCATIONS CHART DATA
     */
    private function getTopLocationsChart($dateRange)
    {
        $data = DeliveryRequest::with('dropOffRegion')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('drop_off_region_id, COUNT(*) as count')
            ->groupBy('drop_off_region_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'label' => $item->dropOffRegion->name ?? 'Unknown Region',
                    'value' => $item->count
                ];
            });

        return [
            'title' => 'Top Locations',
            'data' => $data
        ];
    }

    /**
     * DELIVERY VOLUME CHART DATA
     */
    private function getDeliveryVolumeChart($dateRange)
    {
        $data = DeliveryRequest::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'label' => Carbon::parse($item->date)->format('M j'),
                    'value' => $item->count
                ];
            });

        return [
            'title' => 'Delivery Volume',
            'data' => $data
        ];
    }

    /**
     * REVENUE TREND CHART DATA
     */
     private function getRevenueTrendChart($dateRange)
    {
        // Get daily payments
        $payments = Payment::where('status', 'verified')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Get daily refunds - ONLY PROCESSED
        $refunds = Refund::where('status', 'processed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->selectRaw('DATE(created_at) as date, SUM(refund_amount) as refunds')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Combine data for net revenue
        $allDates = array_unique(array_merge(
            $payments->keys()->toArray(),
            $refunds->keys()->toArray()
        ));

        sort($allDates);

        $data = collect($allDates)->map(function($date) use ($payments, $refunds) {
            $paymentAmount = $payments->get($date)->revenue ?? 0;
            $refundAmount = $refunds->get($date)->refunds ?? 0;
            $netRevenue = $paymentAmount - $refundAmount;

            return [
                'label' => Carbon::parse($date)->format('M j'),
                'value' => (float) $netRevenue,
                'gross_revenue' => (float) $paymentAmount,
                'refunds' => (float) $refundAmount
            ];
        });

        return [
            'title' => 'Net Revenue Trend',
            'data' => $data
        ];
    }

    /**
     * DATE RANGE HELPER
     */
     private function getDateRange($filter)
    {
        $today = Carbon::today();
        
        switch ($filter) {
            case 'today':
                return [
                    'start' => $today, 
                    'end' => $today->copy()->endOfDay()
                ];
                
            case 'this_week':
                return [
                    'start' => $today->copy()->startOfWeek(), 
                    'end' => $today->copy()->endOfWeek()
                ];
                
            case 'this_month':
                return [
                    'start' => $today->copy()->startOfMonth(), 
                    'end' => $today->copy()->endOfMonth()
                ];
                
            case 'this_year':
                return [
                    'start' => $today->copy()->startOfYear(), 
                    'end' => $today->copy()->endOfYear()
                ];
                
            default:
                return [
                    'start' => $today->copy()->startOfMonth(), 
                    'end' => $today->copy()->endOfMonth()
                ];
        }
    }

    /**
     * PDF EXPORT (Optional - for future use)
     */
    public function exportPdf(Request $request)
    {
        $dateFilter = $request->input('date_filter', 'this_month');
        $dateRange = $this->getDateRange($dateFilter);
        
        // For now, just return JSON response
        // You can implement PDF generation later
        return response()->json([
            'message' => 'PDF export would be implemented here',
            'date_range' => $dateRange,
            'quick_stats' => $this->getQuickStats($dateRange)
        ]);
    }
}
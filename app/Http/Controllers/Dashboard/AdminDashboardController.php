<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRequest;
use App\Models\DeliveryOrder;
use App\Models\Truck;
use App\Models\EmployeeProfile;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        // Get counts for dashboard metrics
        $activeDeliveries = DeliveryOrder::whereIn('status', ['dispatched', 'in_transit'])->count();
        $pendingPayments = DeliveryRequest::where('status', 'approved')
            ->whereHas('deliveryOrder', function($query) {
                $query->where('payment_status', '!=', 'paid');
            })
            ->sum('total_price');
        
        $pendingRequests = DeliveryRequest::pending()->count();
        $activeDrivers = EmployeeProfile::whereHas('user', function($query) {
            $query->where('role', 'driver');
        })->count();

        // Get recent delivery requests
        $recentRequests = DeliveryRequest::with(['sender', 'receiver'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => 'DR-' . str_pad($request->id, 6, '0', STR_PAD_LEFT),
                    'customer' => $request->sender->name ?? $request->sender->company_name,
                    'status' => $request->status,
                ];
            });

        // Get driver statuses
        $driverStatuses = EmployeeProfile::with(['user', 'region'])
            ->whereHas('user', function($query) {
                $query->where('role', 'driver');
            })
            ->take(5)
            ->get()
            ->map(function ($driver) {
                $status = 'Idle'; // Default status
                $location = $driver->region->name ?? 'Unknown';
                
                // You would add logic here to determine actual status based on assigned deliveries
                
                return [
                    'id' => $driver->employee_id,
                    'name' => $driver->user->name,
                    'status' => $status,
                    'location' => $location,
                ];
            });

        return Inertia::render('Admin/AdminDash', [
            'metrics' => [
                'totalActiveDeliveries' => $activeDeliveries,
                'pendingPayments' => '$' . number_format($pendingPayments, 2),
                'pendingRequests' => $pendingRequests,
                'activeDrivers' => $activeDrivers,
            ],
            'recentDeliveryRequests' => $recentRequests,
            'driverStatuses' => $driverStatuses,
        ]);
    }
}
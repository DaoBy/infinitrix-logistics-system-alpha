<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\User;
use Inertia\Inertia;

class DriverMonitorController extends Controller
{
    public function index()
    {
        $drivers = User::role('driver')
            ->with(['currentRegion', 'deliveryOrders' => function ($query) {
                $query->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                    ->with(['deliveryRequest.packages', 'truck']);
            }])
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'current_region' => $driver->currentRegion->name ?? 'Unknown',
                    'active_deliveries' => $driver->deliveryOrders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'status' => $order->status,
                            'estimated_arrival' => $order->estimated_arrival,
                            'truck' => $order->truck->make . ' ' . $order->truck->model,
                            'package_count' => $order->deliveryRequest->packages->count(),
                        ];
                    }),
                ];
            });

        return Inertia::render('Admin/DriverMonitor', [
            'drivers' => $drivers
        ]);
    }

    public function show(User $driver)
    {
        $this->authorize('view', $driver);

        $driver->load([
            'currentRegion',
            'deliveryOrders' => function ($query) {
                $query->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                    ->with(['deliveryRequest.packages', 'truck']);
            },
            'regionLogs' => function ($query) {
                $query->latest()
                    ->take(10)
                    ->with(['region', 'deliveryOrder.deliveryRequest']);
            }
        ]);

        return Inertia::render('Admin/DriverDetail', [
            'driver' => $driver,
            'regionLogs' => $driver->regionLogs->map(function ($log) {
                return [
                    'region' => $log->region->name,
                    'type' => $log->type,
                    'logged_at' => $log->logged_at->format('M d, Y H:i'),
                    'order_id' => $log->deliveryOrder->id,
                    'request_id' => $log->deliveryOrder->deliveryRequest->id,
                ];
            }),
        ]);
    }
}
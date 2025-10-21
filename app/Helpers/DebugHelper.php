<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Package;
use App\Models\DeliveryOrder;

class DebugHelper
{
    public static function logDriverLocationUpdate(User $driver, array $context = [])
    {
        Log::info('DRIVER LOCATION UPDATE DEBUG', [
            'driver_id' => $driver->id,
            'driver_name' => $driver->name,
            'current_region_id' => $driver->current_region_id,
            'has_active_assignment' => $driver->currentTruckAssignment ? true : false,
            'active_assignment_id' => $driver->currentTruckAssignment?->id,
            'active_delivery_orders' => $driver->deliveryOrders()
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
                ->count(),
            'context' => $context
        ]);
    }

    public static function logPackageLocationUpdate(Package $package, array $context = [])
    {
        Log::info('PACKAGE LOCATION UPDATE DEBUG', [
            'package_id' => $package->id,
            'package_code' => $package->item_code,
            'current_region_id' => $package->current_region_id,
            'status' => $package->status,
            'delivery_request_id' => $package->delivery_request_id,
            'delivery_order_id' => $package->deliveryRequest?->deliveryOrder?->id,
            'destination_region_id' => $package->deliveryRequest?->drop_off_region_id,
            'context' => $context
        ]);
    }

    public static function logDeliveryOrderUpdate(DeliveryOrder $order, array $context = [])
    {
        Log::info('DELIVERY ORDER UPDATE DEBUG', [
            'delivery_order_id' => $order->id,
            'status' => $order->status,
            'current_region_id' => $order->current_region_id,
            'driver_id' => $order->driver_id,
            'package_count' => $order->packages->count(),
            'context' => $context
        ]);
    }

    public static function getDriverStatusReport(User $driver): array
    {
        $activeOrders = $driver->deliveryOrders()
            ->whereIn('status', ['assigned', 'dispatched', 'in_transit'])
            ->with(['packages', 'currentRegion'])
            ->get();

        $packages = Package::whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
            $query->where('driver_id', $driver->id)
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit']);
        })->get();

        return [
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'current_region_id' => $driver->current_region_id,
                'last_region_update' => $driver->last_region_update,
            ],
            'assignment' => $driver->currentTruckAssignment ? [
                'id' => $driver->currentTruckAssignment->id,
                'region_id' => $driver->currentTruckAssignment->region_id,
                'current_region_id' => $driver->currentTruckAssignment->current_region_id,
                'is_active' => $driver->currentTruckAssignment->is_active,
            ] : null,
            'active_orders' => $activeOrders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'current_region_id' => $order->current_region_id,
                    'package_count' => $order->packages->count(),
                    'packages' => $order->packages->map(function ($pkg) {
                        return [
                            'id' => $pkg->id,
                            'item_code' => $pkg->item_code,
                            'status' => $pkg->status,
                            'current_region_id' => $pkg->current_region_id,
                            'destination_region_id' => $pkg->deliveryRequest->drop_off_region_id,
                        ];
                    })
                ];
            }),
            'packages_summary' => [
                'total' => $packages->count(),
                'by_status' => $packages->groupBy('status')->map->count(),
                'by_region' => $packages->groupBy('current_region_id')->map->count(),
            ]
        ];
    }
}
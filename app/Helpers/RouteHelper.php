<?php

namespace App\Helpers;

use App\Models\Region;
use App\Models\RegionTravelDuration;
use App\Models\User;
use App\Models\Package;
use Illuminate\Support\Facades\Log;

class RouteHelper
{
    /**
     * Get the list of region IDs that form the path from origin to destination.
     */
    public static function getPathBetween(int $originId, int $destinationId): ?array
    {
        try {
            $graph = self::buildGraph();

            $queue = [[$originId]];
            $visited = [$originId];

            while (!empty($queue)) {
                $path = array_shift($queue);
                $last = end($path);

                if ($last === $destinationId) {
                    return $path;
                }

                foreach ($graph[$last] ?? [] as $neighbor) {
                    if (!in_array($neighbor, $visited)) {
                        $visited[] = $neighbor;
                        $queue[] = [...$path, $neighbor];
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Route path calculation failed', [
                'origin' => $originId,
                'destination' => $destinationId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Build the adjacency list from travel durations table
     */
    protected static function buildGraph(): array
    {
        $graph = [];

        RegionTravelDuration::all()->each(function ($route) use (&$graph) {
            $graph[$route->from_region_id][] = $route->to_region_id;
        });

        return $graph;
    }

    /**
     * Get path with region names for display
     */
    public static function getPathNamesBetween(int $originId, int $destinationId): ?array
    {
        $ids = self::getPathBetween($originId, $destinationId);
        if (!$ids) return null;

        return Region::whereIn('id', $ids)
            ->orderByRaw("FIELD(id, " . implode(',', $ids) . ")")
            ->pluck('name')
            ->toArray();
    }

    /**
     * Get only regions relevant to driver's current deliveries
     */
    public static function getRelevantRegionsForDriver(User $driver): array
    {
        $driver->load([
            'deliveryOrders.deliveryRequest.pickUpRegion',
            'deliveryOrders.deliveryRequest.dropOffRegion',
            'deliveryOrders.packages'
        ]);

        $regions = [];

        $driver->deliveryOrders->each(function ($order) use (&$regions) {
            if ($order->deliveryRequest) {
                $path = self::getPathBetween(
                    $order->deliveryRequest->pick_up_region_id,
                    $order->deliveryRequest->drop_off_region_id
                );
                
                if ($path) {
                    $regions = array_merge($regions, $path);
                }
            }
        });

        // Include driver's current region if not already in list
        if ($driver->current_region_id && !in_array($driver->current_region_id, $regions)) {
            $regions[] = $driver->current_region_id;
        }

        return array_unique($regions);
    }

    /**
     * Check if region is package's destination
     */
    public static function isDestinationRegion(Package $package, int $regionId): bool
    {
        if (!$package->relationLoaded('deliveryRequest.dropOffRegion')) {
            $package->load('deliveryRequest.dropOffRegion');
        }
        
        return $package->deliveryRequest && 
               $package->deliveryRequest->drop_off_region_id === $regionId;
    }
}
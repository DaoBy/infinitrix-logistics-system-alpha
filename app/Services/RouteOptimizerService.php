<?php

namespace App\Services;

use App\Models\RegionTravelDuration;
use App\Models\Region;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RouteOptimizerService
{
    private array $graph = [];
    private array $hubs = [];

    public function __construct()
    {
        $this->hubs = $this->identifyHubs();
        $this->buildGraph();
        
        // Validate connections on startup
        $validator = new \App\Services\RegionConnectionValidator();
        $isolated = $validator->findIsolatedRegions();
        
        if ($isolated->isNotEmpty()) {
            \Log::error('Isolated regions detected', $isolated->toArray());
            throw new \RuntimeException(
                "Isolated regions found: " .
                $isolated->pluck('region_name')->join(', ')
            );
        }

        Log::debug('RouteOptimizer initialized', ['hubs' => $this->hubs]);
    }

    private function buildGraph(): void
    {
        // Only create edges that exist in the database, don't force symmetry
        RegionTravelDuration::all()->each(function ($duration) {
            $this->graph[$duration->from_region_id][$duration->to_region_id] = $duration->estimated_minutes;
        });
        
        Log::debug('Graph built', ['regions' => array_keys($this->graph)]);
    }

    private function identifyHubs(): array
    {
        return RegionTravelDuration::select('from_region_id')
            ->groupBy('from_region_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(3)
            ->pluck('from_region_id')
            ->toArray();
    }

    public function findOptimalRoute(int $startRegionId, array $destinationRegionIds): array
    {
        Log::debug('Finding optimal route', [
            'start' => $startRegionId,
            'destinations' => $destinationRegionIds
        ]);

        // Remove start region from destinations if present
        $destinationRegionIds = array_values(array_diff($destinationRegionIds, [$startRegionId]));

        // If no destinations left (either empty input or only start region was provided)
        if (empty($destinationRegionIds)) {
            return [$startRegionId];
        }

        // For single destination, return direct path
        if (count($destinationRegionIds) === 1) {
            $destination = $destinationRegionIds[0];
            $path = $this->findShortestPath($startRegionId, $destination);
            return empty($path) ? [$startRegionId] : array_merge([$startRegionId], $path);
        }

        // Ensure all regions exist in graph
        $allRegions = array_unique([$startRegionId, ...$destinationRegionIds]);
        foreach ($allRegions as $regionId) {
            if (!isset($this->graph[$regionId])) {
                Log::error('Missing region in graph', ['region_id' => $regionId]);
                throw new \InvalidArgumentException("Region $regionId not found in travel graph");
            }
        }

        // Build complete path through all destinations
        $completePath = [];
        $current = $startRegionId;
        
        // First find path to the first destination
        $firstPath = $this->findShortestPath($current, $destinationRegionIds[0]);
        $completePath = array_merge($completePath, $firstPath);
        $current = end($firstPath) ?: $current;
        
        // Then connect remaining destinations
        for ($i = 1; $i < count($destinationRegionIds); $i++) {
            $next = $destinationRegionIds[$i];
            $path = $this->findShortestPath($current, $next);
            
            // Avoid duplicate regions at connection points
            if (!empty($path) && !empty($completePath) && end($completePath) === $path[0]) {
                array_shift($path);
            }
            
            $completePath = array_merge($completePath, $path);
            $current = $next;
        }

        // Prepend start region if not already present
        if (empty($completePath) || $completePath[0] !== $startRegionId) {
            array_unshift($completePath, $startRegionId);
        }

        // Ensure path is unique while maintaining order
        $uniquePath = [];
        $seen = [];
        foreach ($completePath as $regionId) {
            if (!isset($seen[$regionId])) {
                $uniquePath[] = $regionId;
                $seen[$regionId] = true;
            }
        }

        Log::debug('Final route', ['route' => $uniquePath]);
        return $uniquePath;
    }

    /**
     * Finds the shortest path between two regions using Dijkstra's algorithm
     * Returns the path (excluding start node) with the shortest duration,
     * preferring paths with fewer hops when durations are equal.
     */
    private function findShortestPath(int $from, int $to): array
    {
        if ($from === $to) {
            return [];
        }

        if (!isset($this->graph[$from]) || !isset($this->graph[$to])) {
            throw new \InvalidArgumentException("Invalid regions provided for path finding");
        }

        // Priority queue will store [regionId, distance, hops]
        $queue = new \SplPriorityQueue();
        $queue->setExtractFlags(\SplPriorityQueue::EXTR_DATA);
        
        // For tracking the best path to each node
        $distances = [];
        $previous = [];
        $hops = [];
        
        // Initialize all nodes
        foreach (array_keys($this->graph) as $regionId) {
            $distances[$regionId] = PHP_INT_MAX;
            $previous[$regionId] = null;
            $hops[$regionId] = PHP_INT_MAX;
        }
        
        $distances[$from] = 0;
        $hops[$from] = 0;
        
        // We use negative distance to simulate min-heap behavior
        $queue->insert([$from, 0, 0], 0);

        while (!$queue->isEmpty()) {
            $current = $queue->extract();
            $currentId = $current[0];
            $currentDistance = $current[1];
            $currentHops = $current[2];
            
            if ($currentId === $to) {
                break;
            }

            // Skip if we already found a better path to this node
            if ($currentDistance > $distances[$currentId] || 
                ($currentDistance === $distances[$currentId] && $currentHops > $hops[$currentId])) {
                continue;
            }

            foreach ($this->graph[$currentId] as $neighbor => $time) {
                $altDistance = $distances[$currentId] + $time;
                $altHops = $hops[$currentId] + 1;
                
                // If better distance, or same distance with fewer hops
                if ($altDistance < $distances[$neighbor] || 
                    ($altDistance === $distances[$neighbor] && $altHops < $hops[$neighbor])) {
                    
                    $distances[$neighbor] = $altDistance;
                    $hops[$neighbor] = $altHops;
                    $previous[$neighbor] = $currentId;
                    
                    // Priority is a combination of distance and hops (weighted heavily toward distance)
                    $priority = -($altDistance * 1000 + $altHops);
                    $queue->insert([$neighbor, $altDistance, $altHops], $priority);
                }
            }
        }

        // Reconstruct path
        $path = [];
        $current = $to;
        while ($current !== null && $current !== $from) {
            array_unshift($path, $current);
            $current = $previous[$current];
        }

        return $path;
    }

    private function buildTimeMatrix(array $regionIds): array
    {
        $matrix = [];
        
        foreach ($regionIds as $from) {
            foreach ($regionIds as $to) {
                if ($from === $to) {
                    $matrix[$from][$to] = 0;
                    continue;
                }
                
                try {
                    $matrix[$from][$to] = $this->getTravelTime($from, $to);
                } catch (\RuntimeException $e) {
                    // If no direct connection, find path time
                    $path = $this->findPathBetween($from, $to);
                    $matrix[$from][$to] = $this->calculatePathTime($from, $path);
                }
            }
        }
        
        return $matrix;
    }

    private function findOptimalVisitOrder(int $start, array $destinations, array $timeMatrix): array
    {
        if (!isset($timeMatrix[$start])) {
            throw new \InvalidArgumentException("Start region $start not found in time matrix");
        }

        $remaining = $destinations;
        $order = [];
        $current = $start;

        while (!empty($remaining)) {
            $bestNext = null;
            $bestTime = PHP_INT_MAX;

            foreach ($remaining as $next) {
                // Skip if current and next are the same
                if ($current === $next) {
                    continue;
                }

                if (!isset($timeMatrix[$current][$next])) {
                    Log::error('Missing time data', [
                        'current' => $current,
                        'next' => $next,
                        'available' => array_keys($timeMatrix[$current] ?? [])
                    ]);
                    throw new \RuntimeException("No travel time data from $current to $next");
                }

                $directTime = $timeMatrix[$current][$next];
                $lookaheadTime = $this->calculateLookaheadTime($next, $remaining, $timeMatrix);
                $totalTime = $directTime + $lookaheadTime;

                if ($totalTime < $bestTime) {
                    $bestTime = $totalTime;
                    $bestNext = $next;
                }
            }

            if ($bestNext !== null) {
                $order[] = $bestNext;
                $current = $bestNext;
                $remaining = array_diff($remaining, [$bestNext]);
            } else {
                Log::warning('No best next found', [
                    'current' => $current,
                    'remaining' => $remaining
                ]);
                break;
            }
        }

        return $order;
    }

    private function calculateLookaheadTime(int $from, array $remainingDests, array $timeMatrix): float
    {
        if (count($remainingDests) <= 1) {
            return 0;
        }

        $total = 0;
        $visited = [$from];
        $remaining = array_diff($remainingDests, [$from]);

        while (!empty($remaining)) {
            $minTime = PHP_INT_MAX;
            $bestDest = null;

            foreach ($visited as $v) {
                foreach ($remaining as $r) {
                    if (!isset($timeMatrix[$v][$r])) {
                        Log::error('Missing lookahead time', [
                            'from' => $v,
                            'to' => $r,
                            'available' => array_keys($timeMatrix[$v] ?? [])
                        ]);
                        continue;
                    }

                    if ($timeMatrix[$v][$r] < $minTime) {
                        $minTime = $timeMatrix[$v][$r];
                        $bestDest = $r;
                    }
                }
            }

            if ($bestDest !== null) {
                $total += $minTime;
                $visited[] = $bestDest;
                $remaining = array_diff($remaining, [$bestDest]);
            } else {
                Log::warning('No best destination found in lookahead', [
                    'visited' => $visited,
                    'remaining' => $remaining
                ]);
                break;
            }
        }

        return $total;
    }

    private function findPathBetween(int $from, int $to): array
    {
        if ($from === $to) {
            return [];
        }

        if (!isset($this->graph[$from]) || !isset($this->graph[$to])) {
            throw new \InvalidArgumentException("Invalid regions provided for path finding");
        }

        $distances = [];
        $previous = [];
        $queue = new \SplPriorityQueue();

        foreach (array_keys($this->graph) as $regionId) {
            $distances[$regionId] = $regionId === $from ? 0 : PHP_INT_MAX;
            $previous[$regionId] = null;
            $queue->insert($regionId, -$distances[$regionId]);
        }

        while (!$queue->isEmpty()) {
            $current = $queue->extract();
            
            if ($current === $to) {
                break;
            }

            foreach ($this->graph[$current] ?? [] as $neighbor => $time) {
                $alt = $distances[$current] + $time;
                if ($alt < $distances[$neighbor]) {
                    $distances[$neighbor] = $alt;
                    $previous[$neighbor] = $current;
                    $queue->insert($neighbor, -$alt);
                }
            }
        }

        // Reconstruct path
        $path = [];
        $current = $to;
        while ($current !== null && $current !== $from) {
            array_unshift($path, $current);
            $current = $previous[$current];
        }

        return $path;
    }

    private function calculatePathTime(int $start, array $path): int
    {
        if (empty($path)) {
            return 0;
        }
        $time = 0;
        $current = $start;
        
        foreach ($path as $next) {
            $time += $this->getTravelTime($current, $next);
            $current = $next;
        }
        
        return $time;
    }

    public function getTravelTime(int $fromRegionId, int $toRegionId): int
    {
        if (!isset($this->graph[$fromRegionId][$toRegionId])) {
            Log::error('Missing travel time', [
                'from' => $fromRegionId,
                'to' => $toRegionId,
                'available' => array_keys($this->graph[$fromRegionId] ?? [])
            ]);
            throw new \RuntimeException("No travel time data from $fromRegionId to $toRegionId");
        }

        return $this->graph[$fromRegionId][$toRegionId];
    }

    public function getDriverRouteDetails(User $driver): array
    {
        $currentRegionId = $driver->current_region_id;
        $destinationRegionIds = $this->getDriverDestinationRegions($driver);

        if (!$currentRegionId || empty($destinationRegionIds)) {
            return [
                'current_region' => null,
                'route' => []
            ];
        }

        $route = $this->findOptimalRoute($currentRegionId, $destinationRegionIds);

        // Update region selection
        $regions = Region::whereIn('id', array_merge([$currentRegionId], $route))
            ->get(['id', 'name', 'latitude', 'longitude']) // Add these fields
            ->keyBy('id');

        $routeDetails = [];
        $previousRegionId = $currentRegionId;

        foreach ($route as $regionId) {
            if ($regionId === $previousRegionId) continue;
            
            $routeDetails[] = [
                'region' => $regions[$regionId] ?? null,
                'estimated_minutes' => $this->getTravelTime($previousRegionId, $regionId)
            ];
            $previousRegionId = $regionId;
        }

        return [
            'current_region' => $regions[$currentRegionId] ?? null,
            'route' => $routeDetails
        ];
    }

    public function getDriverDestinationRegions(User $driver): array
{
    $packages = \App\Models\Package::whereHas('deliveryRequest.deliveryOrder', function ($query) use ($driver) {
            $query->where('driver_id', $driver->id)
                ->whereIn('status', ['assigned', 'dispatched', 'in_transit', 'delivered']); // Include delivered packages
        })
        ->whereNotIn('status', ['completed', 'returned']) // Only exclude completed/returned
        ->with('deliveryRequest.dropOffRegion')
        ->get();

    $destinations = $packages->pluck('deliveryRequest.drop_off_region_id')
        ->unique()
        ->values()
        ->toArray();

    $pickups = $packages->pluck('deliveryRequest.pick_up_region_id')
        ->unique()
        ->values()
        ->toArray();

    return array_unique(array_merge($destinations, $pickups));
}

    public function getRouteDetails(array $regionIds): array
    {
        $regions = Region::whereIn('id', $regionIds)->get()->keyBy('id');
        $details = [];
        $totalTime = 0;

        for ($i = 0; $i < count($regionIds) - 1; $i++) {
            $from = $regionIds[$i];
            $to = $regionIds[$i + 1];
            $time = $this->graph[$from][$to] ?? 0;
            $totalTime += $time;

            $details[] = [
                'from' => $regions[$from]->name,
                'to' => $regions[$to]->name,
                'time_minutes' => $time,
                'cumulative_minutes' => $totalTime
            ];
        }

        return $details;
    }
}
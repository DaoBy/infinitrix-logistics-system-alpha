<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;
use App\Models\RegionTravelDuration;
use App\Services\OSRMService;
use Illuminate\Support\Facades\Log;

class RegionTravelDurationSeeder extends Seeder
{
    // Minimum realistic travel time between branches (minutes)
    const MIN_TRAVEL_TIME = 30;
    
    // Maximum direct route distance (degrees)
    const MAX_DIRECT_DISTANCE = 5;
    
    // Traffic factors (urban vs rural)
    const TRAFFIC_FACTOR_URBAN = 1.3;  // 30% longer in cities
    const TRAFFIC_FACTOR_RURAL = 1.15; // 15% longer in rural areas
    
    // Additional urban minutes for city traffic
    const URBAN_PENALTY = 20;

    public function run()
    {
        RegionTravelDuration::truncate();
        $osrm = new OSRMService();
        $regions = Region::all();
        $createdPairs = [];

        foreach ($regions as $from) {
            foreach ($regions as $to) {
                if ($from->id === $to->id) continue;

                $pairKey = $this->getPairKey($from->id, $to->id);
                if (in_array($pairKey, $createdPairs)) continue;

                if ($this->getDirectDistance($from, $to) > self::MAX_DIRECT_DISTANCE) {
                    continue;
                }

                $minutes = $this->getRealisticTravelTime($osrm, $from, $to);
                $this->createBidirectionalRoute($from->id, $to->id, $minutes);
                $createdPairs[] = $pairKey;
            }
        }

        Log::info('Region travel durations seeded', [
            'total_routes' => RegionTravelDuration::count(),
            'average_time' => round(RegionTravelDuration::avg('estimated_minutes')),
            'min_time' => RegionTravelDuration::min('estimated_minutes'),
            'max_time' => RegionTravelDuration::max('estimated_minutes')
        ]);
    }

    private function getRealisticTravelTime(OSRMService $osrm, Region $from, Region $to): int
    {
        // Get base time from OSRM or fallback calculation
        $baseMinutes = $osrm->getRouteTime($from->id, $to->id) 
                     ?? $this->calculateFallbackTime($from, $to);
        
        // Apply traffic adjustments
        return $this->applyTrafficAdjustments($baseMinutes, $from, $to);
    }

    private function applyTrafficAdjustments(int $baseMinutes, Region $from, Region $to): int
    {
        $isUrban = $this->isUrbanArea($from) || $this->isUrbanArea($to);
        
        // Apply traffic factor
        $adjusted = $isUrban 
            ? $baseMinutes * self::TRAFFIC_FACTOR_URBAN
            : $baseMinutes * self::TRAFFIC_FACTOR_RURAL;
        
        // Add urban penalty if applicable
        if ($isUrban) {
            $adjusted += self::URBAN_PENALTY;
        }
        
        return (int) max(self::MIN_TRAVEL_TIME, round($adjusted));
    }

    private function isUrbanArea(Region $region): bool
    {
        // Define your urban region IDs here
        $urbanRegionIds = [1, 3, 5]; // Example IDs for Manila, Cebu, Davao
        return in_array($region->id, $urbanRegionIds);
    }

    private function calculateFallbackTime(Region $from, Region $to): int
    {
        $distance = $this->getDirectDistance($from, $to);
        
        // Conservative estimate: 40 km/h average including stops
        // 1 degree ≈ 111 km → hours = distance * 111 / 40
        $hours = $distance * 2.775; // 111/40 = 2.775
        
        return (int) max(
            self::MIN_TRAVEL_TIME,
            round($hours * 60)
        );
    }

    private function getDirectDistance(Region $from, Region $to): float
    {
        return sqrt(
            pow($from->latitude - $to->latitude, 2) +
            pow($from->longitude - $to->longitude, 2)
        );
    }

    private function createBidirectionalRoute(int $fromId, int $toId, int $minutes): void
    {
        RegionTravelDuration::create([
            'from_region_id' => $fromId,
            'to_region_id' => $toId,
            'estimated_minutes' => $minutes,
            'source' => 'osrm+traffic_adj'
        ]);

        RegionTravelDuration::create([
            'from_region_id' => $toId,
            'to_region_id' => $fromId,
            'estimated_minutes' => $minutes,
            'source' => 'osrm+traffic_adj'
        ]);
    }

    private function getPairKey(int $fromId, int $toId): string
    {
        return $fromId < $toId ? "$fromId-$toId" : "$toId-$fromId";
    }
}
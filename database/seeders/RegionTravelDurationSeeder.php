<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;
use App\Models\RegionTravelDuration;
use App\Services\OSRMService;
use Illuminate\Support\Facades\Log;

class RegionTravelDurationSeeder extends Seeder
{
    // Predefined travel times between regions (in minutes) as fallback
    private $fallbackTravelTimes = [
        // Malabon (1) to others
        [1, 2, 240], // Malabon to Labo: 4 hours
        [1, 3, 180], // Malabon to Daet: 3 hours
        [1, 4, 210], // Malabon to Milaor: 3.5 hours
        [1, 5, 240], // Malabon to Naga: 4 hours
        [1, 6, 270], // Malabon to Iriga: 4.5 hours
        [1, 7, 300], // Malabon to Tabaco: 5 hours
        [1, 8, 330], // Malabon to Legazpi: 5.5 hours
        [1, 9, 360], // Malabon to Sorsogon: 6 hours

        // Labo (2) to others
        [2, 3, 60],  // Labo to Daet: 1 hour
        [2, 4, 90],  // Labo to Milaor: 1.5 hours
        [2, 5, 120], // Labo to Naga: 2 hours
        [2, 6, 150], // Labo to Iriga: 2.5 hours
        [2, 7, 180], // Labo to Tabaco: 3 hours
        [2, 8, 210], // Labo to Legazpi: 3.5 hours
        [2, 9, 240], // Labo to Sorsogon: 4 hours

        // Daet (3) to others
        [3, 4, 60],  // Daet to Milaor: 1 hour
        [3, 5, 90],  // Daet to Naga: 1.5 hours
        [3, 6, 120], // Daet to Iriga: 2 hours
        [3, 7, 150], // Daet to Tabaco: 2.5 hours
        [3, 8, 180], // Daet to Legazpi: 3 hours
        [3, 9, 210], // Daet to Sorsogon: 3.5 hours

        // Milaor (4) to others
        [4, 5, 30],  // Milaor to Naga: 30 minutes
        [4, 6, 60],  // Milaor to Iriga: 1 hour
        [4, 7, 90],  // Milaor to Tabaco: 1.5 hours
        [4, 8, 120], // Milaor to Legazpi: 2 hours
        [4, 9, 150], // Milaor to Sorsogon: 2.5 hours

        // Naga (5) to others
        [5, 6, 30],  // Naga to Iriga: 30 minutes
        [5, 7, 60],  // Naga to Tabaco: 1 hour
        [5, 8, 90],  // Naga to Legazpi: 1.5 hours
        [5, 9, 120], // Naga to Sorsogon: 2 hours

        // Iriga (6) to others
        [6, 7, 60],  // Iriga to Tabaco: 1 hour
        [6, 8, 90],  // Iriga to Legazpi: 1.5 hours
        [6, 9, 120], // Iriga to Sorsogon: 2 hours

        // Tabaco (7) to others
        [7, 8, 30],  // Tabaco to Legazpi: 30 minutes
        [7, 9, 60],  // Tabaco to Sorsogon: 1 hour

        // Legazpi (8) to others
        [8, 9, 30],  // Legazpi to Sorsogon: 30 minutes
    ];

    public function run()
    {
        RegionTravelDuration::truncate();
        $createdPairs = [];
        $osrmService = new OSRMService();

        // Check if OSRM service is available
        $useOSRM = $osrmService->isAvailable();
        $this->command->info($useOSRM ? 
            '✅ OSRM service is available - using real route data' :
            '⚠️ OSRM service unavailable - using fallback travel times'
        );

        $regions = Region::all();
        $totalRegions = $regions->count();

        if ($useOSRM) {
            // Use OSRM for real route data
            foreach ($regions as $from) {
                foreach ($regions as $to) {
                    if ($from->id === $to->id) continue;

                    $pairKey = $this->getPairKey($from->id, $to->id);
                    if (in_array($pairKey, $createdPairs)) continue;

                    $minutes = $osrmService->getRouteTime($from->id, $to->id);

                    if ($minutes === null) {
                        // If OSRM fails, use fallback
                        $minutes = $this->getFallbackTime($from->id, $to->id);
                    }

                    $this->createBidirectionalRoute($from->id, $to->id, $minutes);
                    $createdPairs[] = $pairKey;
                }
            }
        } else {
            // Use fallback data
            foreach ($this->fallbackTravelTimes as $route) {
                $fromId = $route[0];
                $toId = $route[1];
                $minutes = $route[2];

                $pairKey = $this->getPairKey($fromId, $toId);
                if (in_array($pairKey, $createdPairs)) {
                    continue;
                }

                $this->createBidirectionalRoute($fromId, $toId, $minutes);
                $createdPairs[] = $pairKey;
            }
        }

        $totalRoutes = RegionTravelDuration::count();
        $this->command->info("✅ Region travel durations seeded successfully!");
        $this->command->info("   Total routes: {$totalRoutes}");
        $this->command->info("   Method: " . ($useOSRM ? 'OSRM API' : 'Fallback data'));
    }

    private function getFallbackTime(int $fromId, int $toId): int
    {
        foreach ($this->fallbackTravelTimes as $route) {
            if (($route[0] === $fromId && $route[1] === $toId) || 
                ($route[0] === $toId && $route[1] === $fromId)) {
                return $route[2];
            }
        }

        // If no specific fallback found, calculate approximate time
        $from = Region::find($fromId);
        $to = Region::find($toId);
        
        if (!$from || !$to) {
            return 60; // Default 1 hour
        }

        // Simple distance-based calculation
        $distance = sqrt(
            pow($from->latitude - $to->latitude, 2) +
            pow($from->longitude - $to->longitude, 2)
        );

        return (int) max(30, $distance * 100); // Approximate conversion
    }

    private function createBidirectionalRoute(int $fromId, int $toId, int $minutes): void
    {
        RegionTravelDuration::create([
            'from_region_id' => $fromId,
            'to_region_id' => $toId,
            'estimated_minutes' => $minutes,
            'source' => 'osrm+manual'
        ]);

        RegionTravelDuration::create([
            'from_region_id' => $toId,
            'to_region_id' => $fromId,
            'estimated_minutes' => $minutes,
            'source' => 'osrm+manual'
        ]);
    }

    private function getPairKey(int $fromId, int $toId): string
    {
        return $fromId < $toId ? "$fromId-$toId" : "$toId-$fromId";
    }
}
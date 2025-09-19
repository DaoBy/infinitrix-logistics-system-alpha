<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Region;
use Illuminate\Support\Facades\Log;

class OSRMService
{
    public function getRouteTime(int $fromId, int $toId): ?int
    {
        // Use cache to avoid repeated API calls
        return Cache::remember("osrm_{$fromId}_{$toId}", now()->addMonth(), function() use ($fromId, $toId) {
            $from = Region::find($fromId);
            $to = Region::find($toId);

            if (!$from || !$to) {
                Log::warning("OSRMService: Region not found", ['from' => $fromId, 'to' => $toId]);
                return null;
            }

            try {
                $response = Http::timeout(5)->get(
                    "http://router.project-osrm.org/route/v1/driving/" .
                    "{$from->longitude},{$from->latitude};" .
                    "{$to->longitude},{$to->latitude}?overview=false"
                );

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['routes'][0]['duration'])) {
                        $minutes = round($data['routes'][0]['duration'] / 60);
                        Log::info("OSRMService: Route time found", [
                            'from' => $from->name, 
                            'to' => $to->name, 
                            'minutes' => $minutes
                        ]);
                        return $minutes;
                    }
                }
                
                Log::warning("OSRMService: API request failed", [
                    'from' => $from->name,
                    'to' => $to->name,
                    'status' => $response->status()
                ]);
                
            } catch (\Exception $e) {
                Log::error("OSRMService: Exception occurred", [
                    'message' => $e->getMessage(),
                    'from' => $from->name,
                    'to' => $to->name
                ]);
            }

            // Fallback to manual calculation if OSRM fails
            return $this->calculateFallbackTime($from, $to);
        });
    }

    private function calculateFallbackTime(Region $from, Region $to): int
    {
        // Calculate distance using Haversine formula
        $lat1 = deg2rad($from->latitude);
        $lon1 = deg2rad($from->longitude);
        $lat2 = deg2rad($to->latitude);
        $lon2 = deg2rad($to->longitude);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        $distanceKm = 6371 * $c; // Earth radius in km

        // Conservative estimate: 40 km/h average speed including stops
        $hours = $distanceKm / 40;
        $minutes = (int) round($hours * 60);

        // Minimum travel time
        $minutes = max(30, $minutes);

        Log::info("OSRMService: Using fallback calculation", [
            'from' => $from->name,
            'to' => $to->name,
            'distance_km' => round($distanceKm, 2),
            'minutes' => $minutes
        ]);

        return $minutes;
    }

    /**
     * Batch get route times for multiple region pairs
     */
    public function getBatchRouteTimes(array $regionPairs): array
    {
        $results = [];
        
        foreach ($regionPairs as $pair) {
            $fromId = $pair[0];
            $toId = $pair[1];
            $results["{$fromId}-{$toId}"] = $this->getRouteTime($fromId, $toId);
        }

        return $results;
    }

    /**
     * Check if OSRM service is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get('http://router.project-osrm.org/');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Region;

class OSRMService
{
    public function getRouteTime(int $fromId, int $toId): ?int
    {
        return Cache::remember("osrm_{$fromId}_{$toId}", now()->addMonth(), function() use ($fromId, $toId) {
            $from = Region::find($fromId);
            $to = Region::find($toId);

            if (!$from || !$to) {
                return null;
            }

            $response = Http::timeout(10)->get(
                "http://router.project-osrm.org/route/v1/driving/" .
                "{$from->longitude},{$from->latitude};" .
                "{$to->longitude},{$to->latitude}?overview=false"
            );

            return $response->successful() 
                ? round($response->json()['routes'][0]['duration'] / 60) 
                : null;
        });
    }
}
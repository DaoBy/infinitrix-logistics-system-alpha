<?php

namespace App\Services;

use App\Models\Region;
use App\Models\RegionTravelDuration;
use Illuminate\Support\Collection;

class RegionConnectionValidator
{
    public function validateAllRegionConnections(): Collection
    {
        $regions = Region::all();
        $results = collect();

        foreach ($regions as $region) {
            $results->push($this->validateRegionConnections($region->id));
        }

        return $results;
    }

    public function validateRegionConnections(int $regionId): array
    {
        $connections = RegionTravelDuration::where(function($query) use ($regionId) {
            $query->where('from_region_id', $regionId)
                  ->where('to_region_id', '!=', $regionId);
        })->orWhere(function($query) use ($regionId) {
            $query->where('to_region_id', $regionId)
                  ->where('from_region_id', '!=', $regionId);
        })->with(['fromRegion', 'toRegion'])
        ->get();

        $connectionCount = $connections->count();
        $isIsolated = $connectionCount === 0;
        $isHub = $connectionCount > 5; // Arbitrary threshold for hub status

        return [
            'region_id' => $regionId,
            'region_name' => Region::find($regionId)->name,
            'connection_count' => $connectionCount,
            'is_isolated' => $isIsolated,
            'is_hub' => $isHub,
            'connections' => $connections->map(function($conn) use ($regionId) {
                return [
                    'connected_to' => $conn->from_region_id === $regionId 
                        ? $conn->toRegion->name 
                        : $conn->fromRegion->name,
                    'minutes' => $conn->estimated_minutes,
                    'direction' => $conn->from_region_id === $regionId ? 'outbound' : 'inbound'
                ];
            })->toArray()
        ];
    }

    public function findIsolatedRegions(): Collection
    {
        return $this->validateAllRegionConnections()
            ->filter(fn($result) => $result['is_isolated']);
    }

    public function generateConnectionReport(): string
    {
        $results = $this->validateAllRegionConnections();
        $isolated = $this->findIsolatedRegions();

        $report = "REGION CONNECTION REPORT\n";
        $report .= "=======================\n";
        $report .= "Total regions: ".$results->count()."\n";
        $report .= "Isolated regions: ".$isolated->count()."\n\n";

        $report .= "ISOLATED REGIONS:\n";
        foreach ($isolated as $region) {
            $report .= "- {$region['region_name']} (ID: {$region['region_id']})\n";
        }

        $report .= "\nCONNECTION DETAILS:\n";
        foreach ($results as $result) {
            $report .= "\n{$result['region_name']} (ID: {$result['region_id']}): ";
            $report .= "{$result['connection_count']} connections ";
            $report .= $result['is_hub'] ? "[HUB]" : "";
            $report .= $result['is_isolated'] ? "[ISOLATED]" : "";
            $report .= "\n";

            foreach ($result['connections'] as $conn) {
                $report .= "  → {$conn['connected_to']} ({$conn['direction']}): ";
                $report .= "{$conn['minutes']} mins\n";
            }
        }

        return $report;
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;
use App\Models\RegionTravelDuration;

class RegionTravelDurationSeeder extends Seeder
{
    public function run()
    {
        RegionTravelDuration::truncate();

        $regions = Region::all()->keyBy('name');
        
        RegionTravelDuration::create([
            'from_region_id' => $regions['Naga City Region']->id,
            'to_region_id' => $regions['Iriga Region']->id,
            'estimated_minutes' => 45,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Iriga Region']->id,
            'to_region_id' => $regions['Naga City Region']->id,
            'estimated_minutes' => 45,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Iriga Region']->id,
            'to_region_id' => $regions['Ligao Region']->id,
            'estimated_minutes' => 50,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Ligao Region']->id,
            'to_region_id' => $regions['Iriga Region']->id,
            'estimated_minutes' => 50,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Ligao Region']->id,
            'to_region_id' => $regions['Legazpi City Region']->id,
            'estimated_minutes' => 40,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Legazpi City Region']->id,
            'to_region_id' => $regions['Ligao Region']->id,
            'estimated_minutes' => 40,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Legazpi City Region']->id,
            'to_region_id' => $regions['Camalig Region']->id,
            'estimated_minutes' => 20,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Camalig Region']->id,
            'to_region_id' => $regions['Legazpi City Region']->id,
            'estimated_minutes' => 20,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Camalig Region']->id,
            'to_region_id' => $regions['Bacacay Region']->id,
            'estimated_minutes' => 25,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Bacacay Region']->id,
            'to_region_id' => $regions['Camalig Region']->id,
            'estimated_minutes' => 25,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Legazpi City Region']->id,
            'to_region_id' => $regions['Sorsogon City Region']->id,
            'estimated_minutes' => 90,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Sorsogon City Region']->id,
            'to_region_id' => $regions['Legazpi City Region']->id,
            'estimated_minutes' => 90,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Naga City Region']->id,
            'to_region_id' => $regions['Pamplona Region']->id,
            'estimated_minutes' => 30,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Pamplona Region']->id,
            'to_region_id' => $regions['Naga City Region']->id,
            'estimated_minutes' => 30,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Pamplona Region']->id,
            'to_region_id' => $regions['Sipocot Region']->id,
            'estimated_minutes' => 30,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Sipocot Region']->id,
            'to_region_id' => $regions['Pamplona Region']->id,
            'estimated_minutes' => 30,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Sipocot Region']->id,
            'to_region_id' => $regions['Bagacay Region']->id,
            'estimated_minutes' => 25,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Bagacay Region']->id,
            'to_region_id' => $regions['Sipocot Region']->id,
            'estimated_minutes' => 25,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Bagacay Region']->id,
            'to_region_id' => $regions['Daet Region']->id,
            'estimated_minutes' => 60,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Daet Region']->id,
            'to_region_id' => $regions['Bagacay Region']->id,
            'estimated_minutes' => 60,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Daet Region']->id,
            'to_region_id' => $regions['Talisay Region']->id,
            'estimated_minutes' => 20,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Talisay Region']->id,
            'to_region_id' => $regions['Daet Region']->id,
            'estimated_minutes' => 20,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Talisay Region']->id,
            'to_region_id' => $regions['Labo Region']->id,
            'estimated_minutes' => 20,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Labo Region']->id,
            'to_region_id' => $regions['Talisay Region']->id,
            'estimated_minutes' => 20,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Labo Region']->id,
            'to_region_id' => $regions['Basud Region']->id,
            'estimated_minutes' => 25,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Basud Region']->id,
            'to_region_id' => $regions['Labo Region']->id,
            'estimated_minutes' => 25,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Daet Region']->id,
            'to_region_id' => $regions['Basud Region']->id,
            'estimated_minutes' => 20,
        ]);
        RegionTravelDuration::create([
            'from_region_id' => $regions['Basud Region']->id,
            'to_region_id' => $regions['Daet Region']->id,
            'estimated_minutes' => 20,
        ]);
    }
}
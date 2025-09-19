<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;

class RegionSeeder extends Seeder
{
    public function run()
    {
        $regions = [
            [
                'name' => 'Malabon',
                'warehouse_address' => 'MXG9+4J City of Malabon, Metro Manila',
                'latitude' => 14.6625,
                'longitude' => 120.9564,
                'is_active' => true,
                'color_hex' => '#1F77B4',
            ],
            [
                'name' => 'Labo',
                'warehouse_address' => '4RXR+75Q, Labo, Camarines Norte',
                'latitude' => 14.1525,
                'longitude' => 122.8303,
                'is_active' => true,
                'color_hex' => '#2CA02C', 
            ],
            [
                'name' => 'Daet',
                'warehouse_address' => '4W5X+388, Daet, Camarines Norte',
                'latitude' => 14.1125,
                'longitude' => 122.9556,
                'is_active' => true,
                'color_hex' => '#D62728',
            ],
            [
                'name' => 'Milaor',
                'warehouse_address' => 'J52H+G8X, Milaor, Camarines Sur',
                'latitude' => 13.6000,
                'longitude' => 123.1833,
                'is_active' => true,
                'color_hex' => '#FF7F0E', 
            ],
            [
                'name' => 'Naga City',
                'warehouse_address' => 'J6C5+RXQ, Naga City, Camarines Sur',
                'latitude' => 13.6215,
                'longitude' => 123.1815,
                'is_active' => true,
                'color_hex' => '#9467BD', 
            ],
            [
                'name' => 'Iriga',
                'warehouse_address' => 'CCC4+4C4, Iriga City, Camarines Sur',
                'latitude' => 13.4333,
                'longitude' => 123.4167,
                'is_active' => true,
                'color_hex' => '#17BECF', 
            ],
            [
                'name' => 'Tabaco City',
                'warehouse_address' => '9P6M+GVR, Tabaco City, Albay',
                'latitude' => 13.3500,
                'longitude' => 123.7333,
                'is_active' => true,
                'color_hex' => '#E377C2', 
            ],
            [
                'name' => 'Legazpi City',
                'warehouse_address' => '5P3V+65 Legazpi City, Albay',
                'latitude' => 13.1437,
                'longitude' => 123.7437,
                'is_active' => true,
                'color_hex' => '#FFD700', 
            ],
            [
                'name' => 'Sorsogon',
                'warehouse_address' => 'XXCX+FR6, Sorsogon City, Sorsogon',
                'latitude' => 12.9719,
                'longitude' => 123.9185,
                'is_active' => true,
                'color_hex' => '#7F7F7F', 
            ],
        ];

        foreach ($regions as $regionData) {
            Region::updateOrCreate(
                ['name' => $regionData['name']],
                $regionData
            );
        }

        $this->command->info(count($regions) . ' regions seeded successfully!');
    }
}
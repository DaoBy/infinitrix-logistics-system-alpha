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
            'name' => 'Naga City Region',
            'warehouse_address' => '123 Mabolo St., Concepcion Pequeña, Naga City, Camarines Sur',
            'latitude' => 13.6215,
            'longitude' => 123.1815,
            'is_active' => true,
        ],
        [
            'name' => 'Legazpi City Region',
            'warehouse_address' => '456 Rizal Ave., Barangay Rawis, Legazpi City, Albay',
            'latitude' => 13.1437,
            'longitude' => 123.7437,
            'is_active' => true,
        ],
        [
            'name' => 'Sorsogon City Region',
            'warehouse_address' => '789 Rizal Blvd., Zone 2, Sorsogon City, Sorsogon',
            'latitude' => 12.9719,
            'longitude' => 123.9185,
            'is_active' => true,
        ],
        [
            'name' => 'Labo Region',
            'warehouse_address' => 'Warehouse St., Labo, Camarines Norte',
            'latitude' => 14.1525,
            'longitude' => 122.8303,
            'is_active' => true,
        ],
        [
            'name' => 'Talisay Region',
            'warehouse_address' => 'Warehouse Rd., Talisay, Camarines Norte',
            'latitude' => 14.1167,
            'longitude' => 122.8167,
            'is_active' => true,
        ],
        [
            'name' => 'Daet Region',
            'warehouse_address' => 'Highway 1, Daet, Camarines Norte',
            'latitude' => 14.1125,
            'longitude' => 122.9556,
            'is_active' => true,
        ],
        [
            'name' => 'Basud Region',
            'warehouse_address' => 'Central Rd., Basud, Camarines Norte',
            'latitude' => 14.0667,
            'longitude' => 122.9667,
            'is_active' => true,
        ],
        [
            'name' => 'Sipocot Region',
            'warehouse_address' => 'Main St., Sipocot, Camarines Sur',
            'latitude' => 13.8167,
            'longitude' => 122.9833,
            'is_active' => true,
        ],
        [
            'name' => 'Bagacay Region',
            'warehouse_address' => 'Barangay Hall Rd., Bagacay, Camarines Sur',
            'latitude' => 13.7333,
            'longitude' => 123.0500,
            'is_active' => true,
        ],
        [
            'name' => 'Pamplona Region',
            'warehouse_address' => 'Purok 3, Pamplona, Camarines Sur',
            'latitude' => 13.5833,
            'longitude' => 123.0667,
            'is_active' => true,
        ],
        [
            'name' => 'Iriga Region',
            'warehouse_address' => 'Downtown Rd., Iriga City, Camarines Sur',
            'latitude' => 13.4333,
            'longitude' => 123.4167,
            'is_active' => true,
        ],
        [
            'name' => 'Ligao Region',
            'warehouse_address' => 'National Hwy, Ligao City, Albay',
            'latitude' => 13.2167,
            'longitude' => 123.5167,
            'is_active' => true,
        ],
        [
            'name' => 'Camalig Region',
            'warehouse_address' => 'Rural Rd., Camalig, Albay',
            'latitude' => 13.1500,
            'longitude' => 123.6000,
            'is_active' => true,
        ],
        [
            'name' => 'Bacacay Region',
            'warehouse_address' => 'Coastal Rd., Bacacay, Albay',
            'latitude' => 13.3000,
            'longitude' => 123.7833,
            'is_active' => true,
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

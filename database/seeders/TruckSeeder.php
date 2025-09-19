<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Truck;
use App\Models\Region;

class TruckSeeder extends Seeder
{
    public function run()
    {
        $regions = Region::all();
        
        if ($regions->isEmpty()) {
            $this->command->error('No regions found! Please seed regions first.');
            return;
        }

        $truckTypes = [
            // Light-duty trucks (3-5 ton capacity)
            [
                'make' => 'Isuzu',
                'model' => 'Elf NHR',
                'volume_capacity' => 12.5,
                'weight_capacity' => 2100,
                'year_range' => [2018, 2022],
                'price_range' => [1800000, 2200000],
                'notes' => 'Ideal for urban deliveries and narrow streets'
            ],
            [
                'make' => 'Hino',
                'model' => '300 Series',
                'volume_capacity' => 14.2,
                'weight_capacity' => 2400,
                'year_range' => [2017, 2021],
                'price_range' => [2000000, 2400000],
                'notes' => 'Reliable for daily regional routes'
            ],

            // Medium-duty trucks (5-8 ton capacity)
            [
                'make' => 'Isuzu',
                'model' => 'NQR 4HK1',
                'volume_capacity' => 18.5,
                'weight_capacity' => 2700,
                'year_range' => [2016, 2020],
                'price_range' => [2500000, 3000000],
                'notes' => 'Versatile workhorse for inter-city transport'
            ],
            [
                'make' => 'Mitsubishi',
                'model' => 'Fuso Canter',
                'volume_capacity' => 17.2,
                'weight_capacity' => 2800,
                'year_range' => [2018, 2022],
                'price_range' => [2700000, 3200000],
                'notes' => 'Fuel-efficient medium-duty truck'
            ],

            // Heavy-duty trucks (8-12 ton capacity)
            [
                'make' => 'Hino',
                'model' => '500 Series',
                'volume_capacity' => 22.3,
                'weight_capacity' => 3000,
                'year_range' => [2015, 2019],
                'price_range' => [3500000, 4200000],
                'notes' => 'For heavy loads and long-distance hauling'
            ],
            [
                'make' => 'Isuzu',
                'model' => 'FVR',
                'volume_capacity' => 25.1,
                'weight_capacity' => 3000,
                'year_range' => [2014, 2018],
                'price_range' => [3800000, 4500000],
                'notes' => 'Heavy-duty with reinforced chassis'
            ]
        ];

        $statuses = ['available', 'available', 'available', 'in_transit', 'maintenance'];
        $platePrefixes = ['NAG', 'LEG', 'SOR', 'DAE', 'IRI', 'TAB', 'MAL', 'LAB', 'MIL'];

        foreach ($regions as $region) {
            // Assign 2-3 trucks per region
            $trucksPerRegion = rand(2, 3);
            
            for ($i = 1; $i <= $trucksPerRegion; $i++) {
                $truckType = $truckTypes[array_rand($truckTypes)];
                $year = rand($truckType['year_range'][0], $truckType['year_range'][1]);
                $purchasePrice = rand(
                    $truckType['price_range'][0], 
                    $truckType['price_range'][1]
                );
                $currentValue = $purchasePrice * (0.6 + (0.3 * (2023 - $year) / 10)); // Depreciation

                $truckData = [
                    'make' => $truckType['make'],
                    'model' => $truckType['model'],
                    'license_plate' => $platePrefixes[array_rand($platePrefixes)] . '-' . rand(1000, 9999),
                    'volume_capacity' => $truckType['volume_capacity'] * (0.9 + (rand(0, 20) / 100)), // ±10% variation
                    'weight_capacity' => $truckType['weight_capacity'] * (0.9 + (rand(0, 20) / 100)), // ±10% variation
                    'current_volume' => 0,
                    'current_weight' => 0,
                    'status' => $statuses[array_rand($statuses)],
                    'year' => $year,
                    'vin' => strtoupper(bin2hex(random_bytes(8))),
                    'purchase_date' => $year . '-' . rand(1, 12) . '-' . rand(1, 28),
                    'purchase_price' => $purchasePrice,
                    'current_value' => max(500000, $currentValue), // Minimum value of 500,000
                    'notes' => $truckType['notes'] . ' - Assigned to ' . $region->name,
                    'is_active' => rand(0, 10) > 1, // 90% chance of being active
                    'region_id' => $region->id,
                ];

                Truck::updateOrCreate(
                    ['license_plate' => $truckData['license_plate']],
                    $truckData
                );
            }
        }

        $this->command->info('🚛 ' . ($regions->count() * 2) . '-'. ($regions->count() * 3) . ' trucks seeded across all regions!');
    }
}
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

        $trucks = [
            // Malabon trucks
            [
                'make' => 'Isuzu',
                'model' => 'Elf NHR',
                'license_plate' => 'MAL-1234',
                'volume_capacity' => 12.5,
                'weight_capacity' => 2100,
                'year' => 2020,
                'region_id' => 1,
                'status' => 'available'
            ],
            [
                'make' => 'Hino',
                'model' => '300 Series',
                'license_plate' => 'MAL-5678',
                'volume_capacity' => 14.2,
                'weight_capacity' => 2400,
                'year' => 2019,
                'region_id' => 1,
                'status' => 'available'
            ],

            // Labo trucks
            [
                'make' => 'Isuzu',
                'model' => 'NQR 4HK1',
                'license_plate' => 'LAB-1234',
                'volume_capacity' => 18.5,
                'weight_capacity' => 2700,
                'year' => 2018,
                'region_id' => 2,
                'status' => 'available'
            ],

            // Daet trucks
            [
                'make' => 'Mitsubishi',
                'model' => 'Fuso Canter',
                'license_plate' => 'DAE-1234',
                'volume_capacity' => 17.2,
                'weight_capacity' => 2800,
                'year' => 2021,
                'region_id' => 3,
                'status' => 'available'
            ],

            // Milaor trucks
            [
                'make' => 'Hino',
                'model' => '500 Series',
                'license_plate' => 'MIL-1234',
                'volume_capacity' => 22.3,
                'weight_capacity' => 3000,
                'year' => 2017,
                'region_id' => 4,
                'status' => 'available'
            ],

            // Naga City trucks
            [
                'make' => 'Isuzu',
                'model' => 'FVR',
                'license_plate' => 'NAG-1234',
                'volume_capacity' => 25.1,
                'weight_capacity' => 3000,
                'year' => 2016,
                'region_id' => 5,
                'status' => 'available'
            ],
            [
                'make' => 'Isuzu',
                'model' => 'Elf NHR',
                'license_plate' => 'NAG-5678',
                'volume_capacity' => 12.5,
                'weight_capacity' => 2100,
                'year' => 2022,
                'region_id' => 5,
                'status' => 'available'
            ],

            // Iriga trucks
            [
                'make' => 'Hino',
                'model' => '300 Series',
                'license_plate' => 'IRI-1234',
                'volume_capacity' => 14.2,
                'weight_capacity' => 2400,
                'year' => 2020,
                'region_id' => 6,
                'status' => 'available'
            ],

            // Tabaco City trucks
            [
                'make' => 'Isuzu',
                'model' => 'NQR 4HK1',
                'license_plate' => 'TAB-1234',
                'volume_capacity' => 18.5,
                'weight_capacity' => 2700,
                'year' => 2019,
                'region_id' => 7,
                'status' => 'available'
            ],

            // Legazpi City trucks
            [
                'make' => 'Mitsubishi',
                'model' => 'Fuso Canter',
                'license_plate' => 'LEG-1234',
                'volume_capacity' => 17.2,
                'weight_capacity' => 2800,
                'year' => 2021,
                'region_id' => 8,
                'status' => 'available'
            ],
            [
                'make' => 'Hino',
                'model' => '500 Series',
                'license_plate' => 'LEG-5678',
                'volume_capacity' => 22.3,
                'weight_capacity' => 3000,
                'year' => 2018,
                'region_id' => 8,
                'status' => 'available'
            ],

            // Sorsogon trucks
            [
                'make' => 'Isuzu',
                'model' => 'FVR',
                'license_plate' => 'SOR-1234',
                'volume_capacity' => 25.1,
                'weight_capacity' => 3000,
                'year' => 2017,
                'region_id' => 9,
                'status' => 'available'
            ],
        ];

        foreach ($trucks as $truckData) {
            $purchasePrice = $this->calculatePurchasePrice($truckData['make'], $truckData['model'], $truckData['year']);
            $currentValue = $purchasePrice * (0.6 + (0.3 * (2023 - $truckData['year']) / 10));

            $truckData = array_merge($truckData, [
                'current_volume' => 0,
                'current_weight' => 0,
                'vin' => strtoupper(substr($truckData['make'], 0, 3) . substr($truckData['model'], 0, 3) . $truckData['year'] . rand(1000, 9999)),
                'purchase_date' => $truckData['year'] . '-01-15',
                'purchase_price' => $purchasePrice,
                'current_value' => max(500000, $currentValue),
                'notes' => $truckData['make'] . ' ' . $truckData['model'] . ' - Assigned to ' . Region::find($truckData['region_id'])->name,
                'is_active' => true,
            ]);

            Truck::updateOrCreate(
                ['license_plate' => $truckData['license_plate']],
                $truckData
            );
        }

        $this->command->info(count($trucks) . ' trucks seeded successfully!');
    }

    private function calculatePurchasePrice($make, $model, $year)
    {
        $basePrices = [
            'Isuzu' => [
                'Elf NHR' => 2000000,
                'NQR 4HK1' => 2800000,
                'FVR' => 4200000,
            ],
            'Hino' => [
                '300 Series' => 2200000,
                '500 Series' => 3800000,
            ],
            'Mitsubishi' => [
                'Fuso Canter' => 3000000,
            ]
        ];

        $basePrice = $basePrices[$make][$model] ?? 2500000;
        $ageFactor = 1.0 - ((2023 - $year) * 0.05); // 5% depreciation per year
        
        return (int)($basePrice * $ageFactor);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Truck;
use App\Models\Region;

class TruckSeeder extends Seeder
{
    public function run()
    {
        $regions = \App\Models\Region::pluck('id')->toArray();

        $trucks = [
            [
                'make' => 'Isuzu',
                'model' => 'NQR 4HK1',
                'license_plate' => 'ABC-1234',
                'volume_capacity' => 18.5,
                'weight_capacity' => 3400,
                'current_volume' => 0,
                'current_weight' => 0,
                'status' => 'available',
                'year' => 2018,
                'vin' => '1HTMMAAL9HH123456',
                'purchase_date' => '2018-06-15',
                'purchase_price' => 2500000.00,
                'current_value' => 1500000.00,
                'notes' => 'Well maintained, serviced quarterly.',
                'is_active' => true,
                'region_id' => $regions[array_rand($regions)],
            ],
            [
                'make' => 'Mitsubishi',
                'model' => 'Fuso Canter',
                'license_plate' => 'DEF-5678',
                'volume_capacity' => 17.2,
                'weight_capacity' => 3210,
                'current_volume' => 0,
                'current_weight' => 0,
                'status' => 'available',
                'year' => 2020,
                'vin' => 'JM2FN01Y810123456',
                'purchase_date' => '2020-02-20',
                'purchase_price' => 2700000.00,
                'current_value' => 2200000.00,
                'notes' => 'Used primarily for short city deliveries.',
                'is_active' => true,
                'region_id' => $regions[array_rand($regions)],
            ],
            [
                'make' => 'Hino',
                'model' => '500 Series',
                'license_plate' => 'GHI-9101',
                'volume_capacity' => 19.3,
                'weight_capacity' => 3200,
                'current_volume' => 0,
                'current_weight' => 0,
                'status' => 'maintenance',
                'year' => 2017,
                'vin' => 'JH4KA7650MC123456',
                'purchase_date' => '2017-10-10',
                'purchase_price' => 3000000.00,
                'current_value' => 1800000.00,
                'notes' => 'Undergoing major engine overhaul.',
                'is_active' => true,
                'region_id' => $regions[array_rand($regions)],
            ],
            [
                'make' => 'Isuzu',
                'model' => 'Elf 300',
                'license_plate' => 'JKL-2345',
                'volume_capacity' => 15.8,
                'weight_capacity' => 3400,
                'current_volume' => 0,
                'current_weight' => 0,
                'status' => 'available',
                'year' => 2019,
                'vin' => '4T1BF1FK5FU123456',
                'purchase_date' => '2019-05-05',
                'purchase_price' => 2000000.00,
                'current_value' => 1600000.00,
                'notes' => 'Used for light loads and express deliveries.',
                'is_active' => false,
                'region_id' => $regions[array_rand($regions)],
            ],
        ];

        foreach ($trucks as $truckData) {
            Truck::updateOrCreate(
                ['license_plate' => $truckData['license_plate']],
                $truckData
            );
        }

        $this->command->info('🚛 4 trucks seeded with region_id!');
    }
}

<?php

namespace Database\Seeders;

use App\Models\PriceMatrix;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PriceMatrixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        PriceMatrix::create([
            'base_fee' => 500,
            'volume_rate' => 50,
            'weight_rate' => 20,
            'package_rate' => 10,
        ]);
    }
}

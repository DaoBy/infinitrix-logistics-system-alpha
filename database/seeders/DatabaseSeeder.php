<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed everything in ALL environments (including production)
        $this->call([
            RegionSeeder::class,
            PriceMatrixSeeder::class,
            EmployeeSeeder::class,
            TruckSeeder::class,
            CustomerSeeder::class,
            RegionTravelDurationSeeder::class,
            PackageCategorySeeder::class,
        ]);
        
        $this->command->info('✅ Database seeded successfully!');
    }
}
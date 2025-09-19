<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seed the application's database for production.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting production database seeding...');
        
        // Always seed these basic configuration tables (safe for production)
        $this->command->info('📋 Seeding basic configuration...');
        $this->call([
            RegionSeeder::class,
            PriceMatrixSeeder::class,
        ]);
        
        $this->command->info('✅ Basic configuration seeded.');
        
        // Ask before seeding data that might overwrite existing records
        if ($this->command->confirm('🔒 Seed employees, trucks, customers, and travel durations? This may overwrite existing data.', false)) {
            $this->command->info('👥 Seeding employees, trucks, customers, and travel data...');
            $this->call([
                EmployeeSeeder::class,
                TruckSeeder::class,
                CustomerSeeder::class,
                RegionTravelDurationSeeder::class,
            ]);
            $this->command->info('✅ Sample data seeded.');
        } else {
            $this->command->info('⏭️  Skipping sample data seeding.');
        }
        
        $this->command->info('🎉 Production seeding completed!');
    }
}
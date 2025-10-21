<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // In production, use the dedicated ProductionSeeder
        if (App::environment('production')) {
            $this->command->info('🌐 Production environment detected.');
            $this->command->info('💡 Use "php artisan db:seed --class=ProductionSeeder" for controlled production seeding.');
            $this->command->info('💡 Or set SEED_DATABASE=true in your deployment to auto-seed.');
            return;
        }

        // For local/development environments, seed everything
        $this->call([
            RegionSeeder::class,
            PriceMatrixSeeder::class,
            EmployeeSeeder::class,
            TruckSeeder::class,
            CustomerSeeder::class,
            RegionTravelDurationSeeder::class,
                       // Region1ToRegion2ReadyOrdersSeeder::class,
            // DeliveryRequestSeeder::class,
        ]);
        
        $this->command->info('✅ Development database seeded successfully!');
    }
}
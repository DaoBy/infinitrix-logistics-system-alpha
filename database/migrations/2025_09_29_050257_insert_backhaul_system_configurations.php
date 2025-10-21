<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Only insert if configurations table exists and entries don't exist
        if (Schema::hasTable('configurations')) {
            $configExists = DB::table('configurations')->where('key', 'driver_cooldown_hours')->exists();
            
            if (!$configExists) {
                DB::table('configurations')->insert([
                    [
                        'key' => 'driver_cooldown_hours',
                        'value' => '4',
                        'type' => 'integer',
                        'description' => 'Number of hours for driver cooldown period between deliveries',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'key' => 'backhaul_eligibility_require_verification',
                        'value' => 'true',
                        'type' => 'boolean',
                        'description' => 'Require package verification before backhaul eligibility',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'key' => 'backhaul_eligibility_require_different_region',
                        'value' => 'true', 
                        'type' => 'boolean',
                        'description' => 'Require driver to be in different region from home for backhaul',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('configurations')) {
            DB::table('configurations')
                ->whereIn('key', [
                    'driver_cooldown_hours',
                    'backhaul_eligibility_require_verification', 
                    'backhaul_eligibility_require_different_region'
                ])
                ->delete();
        }
    }
};
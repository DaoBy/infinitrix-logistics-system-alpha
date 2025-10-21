<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            // Add automated status tracking columns
            $table->enum('current_status', ['active', 'cooldown', 'backhaul_eligible', 'inactive'])
                  ->default('active')
                  ->after('is_active');
                  
            $table->timestamp('cooldown_ends_at')
                  ->nullable()
                  ->after('current_status');
                  
            $table->timestamp('last_delivery_completed_at')
                  ->nullable()
                  ->after('cooldown_ends_at');
                  
            $table->timestamp('backhaul_eligible_at')
                  ->nullable()
                  ->after('last_delivery_completed_at');
            
            // Ensure current_region_id exists
            if (!Schema::hasColumn('driver_truck_assignments', 'current_region_id')) {
                $table->foreignId('current_region_id')
                      ->nullable()
                      ->after('region_id')
                      ->constrained('regions')
                      ->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'current_status',
                'cooldown_ends_at', 
                'last_delivery_completed_at',
                'backhaul_eligible_at'
            ]);
        });
    }
};
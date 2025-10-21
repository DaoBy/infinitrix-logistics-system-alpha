<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            // Remove the unique constraints temporarily to add backhaul fields
            $table->dropUnique(['driver_id', 'is_active']);
            $table->dropUnique(['truck_id', 'is_active']);
            
            // Add backhaul fields
            $table->boolean('available_for_backhaul')->default(false)->after('is_active');
            $table->foreignId('current_region_id')->nullable()->constrained('regions')->after('available_for_backhaul');
            $table->timestamp('available_for_backhaul_at')->nullable()->after('current_region_id');
            
            // Re-add unique constraints with backhaul consideration
            $table->unique(['driver_id', 'is_active', 'available_for_backhaul']);
            $table->unique(['truck_id', 'is_active', 'available_for_backhaul']);
            
            // Additional indexes for performance
            $table->index(['is_active', 'available_for_backhaul']);
            $table->index('current_region_id');
        });
    }

    public function down()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            // Drop indexes and backhaul fields
            $table->dropIndex(['is_active', 'available_for_backhaul']);
            $table->dropIndex(['current_region_id']);
            
            $table->dropUnique(['driver_id', 'is_active', 'available_for_backhaul']);
            $table->dropUnique(['truck_id', 'is_active', 'available_for_backhaul']);
            
            $table->dropForeign(['current_region_id']);
            $table->dropColumn([
                'available_for_backhaul',
                'current_region_id', 
                'available_for_backhaul_at'
            ]);
            
            // Re-add original unique constraints
            $table->unique(['driver_id', 'is_active']);
            $table->unique(['truck_id', 'is_active']);
        });
    }
};
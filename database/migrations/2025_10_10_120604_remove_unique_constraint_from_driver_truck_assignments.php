<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique(['driver_id', 'is_active', 'available_for_backhaul']);
            
            // Instead, add a regular index for performance
            $table->index(['driver_id', 'is_active']);
            $table->index(['truck_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            $table->dropIndex(['driver_id', 'is_active']);
            $table->dropIndex(['truck_id', 'is_active']);
            
            // Recreate the unique constraint
            $table->unique(['driver_id', 'is_active', 'available_for_backhaul']);
        });
    }
};
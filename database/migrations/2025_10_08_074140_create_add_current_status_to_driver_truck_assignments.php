<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('driver_truck_assignments', 'current_status')) {
                $table->string('current_status')->default('active')->after('is_active');
            }
            
            // Add other automated tracking columns if missing
            $columnsToAdd = [
                'cooldown_ends_at' => 'timestamp',
                'last_delivery_completed_at' => 'timestamp', 
                'backhaul_eligible_at' => 'timestamp',
            ];
            
            foreach ($columnsToAdd as $column => $type) {
                if (!Schema::hasColumn('driver_truck_assignments', $column)) {
                    $table->{$type}($column)->nullable();
                }
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
                'backhaul_eligible_at',
            ]);
        });
    }
};
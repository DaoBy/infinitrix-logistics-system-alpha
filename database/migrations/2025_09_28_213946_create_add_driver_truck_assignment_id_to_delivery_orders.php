<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_orders', 'driver_truck_assignment_id')) {
                $table->foreignId('driver_truck_assignment_id')
                      ->nullable()
                      ->constrained('driver_truck_assignments')
                      ->after('truck_id');
                $table->index('driver_truck_assignment_id');
            }
        });
    }

    public function down()
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_orders', 'driver_truck_assignment_id')) {
                $table->dropForeign(['driver_truck_assignment_id']);
                $table->dropColumn('driver_truck_assignment_id');
            }
        });
    }
};
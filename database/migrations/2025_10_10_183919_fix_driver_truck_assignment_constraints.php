<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        // Remove the constraint that's blocking cancellations
        \DB::statement('DROP INDEX IF EXISTS driver_truck_assignments_truck_id_is_active_available_for_backhaul_unique');
    }

    public function down()
    {
        // Optional: Recreate if needed for rollback
        // \DB::statement('CREATE UNIQUE INDEX driver_truck_assignments_truck_id_is_active_available_for_backhaul_unique ON driver_truck_assignments(truck_id, is_active, available_for_backhaul)');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            // Make sure these columns exist for the new backhaul logic
            if (!Schema::hasColumn('driver_truck_assignments', 'current_status')) {
                $table->string('current_status')->default('active')->after('is_active');
            }
            
            if (!Schema::hasColumn('driver_truck_assignments', 'cooldown_ends_at')) {
                $table->timestamp('cooldown_ends_at')->nullable()->after('current_status');
            }
            
            if (!Schema::hasColumn('driver_truck_assignments', 'backhaul_eligible_at')) {
                $table->timestamp('backhaul_eligible_at')->nullable()->after('cooldown_ends_at');
            }
            
            if (!Schema::hasColumn('driver_truck_assignments', 'available_for_backhaul')) {
                $table->boolean('available_for_backhaul')->default(false)->after('backhaul_eligible_at');
            }
            
            if (!Schema::hasColumn('driver_truck_assignments', 'deleted_reason')) {
                $table->string('deleted_reason')->nullable()->after('deleted_at');
            }
            
            if (!Schema::hasColumn('driver_truck_assignments', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->constrained('users')->after('deleted_reason');
            }
        });
    }

    public function down()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            // Don't drop columns in rollback to avoid data loss
            // $table->dropColumn(['current_status', 'cooldown_ends_at', 'backhaul_eligible_at', 'available_for_backhaul', 'deleted_reason', 'deleted_by']);
        });
    }
};
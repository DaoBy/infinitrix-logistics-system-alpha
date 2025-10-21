<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            // Add soft delete columns
            $table->softDeletes();
            $table->string('deleted_reason')->nullable()->after('deleted_at');
            $table->foreignId('deleted_by')->nullable()->after('deleted_reason')->constrained('users');
            
            // Add completed status support (if not already there)
            if (!Schema::hasColumn('driver_truck_assignments', 'current_status')) {
                $table->string('current_status')->default('active')->after('is_active');
            }
        });
    }

    public function down()
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('deleted_reason');
            $table->dropForeign(['deleted_by']);
            $table->dropColumn('deleted_by');
        });
    }
};
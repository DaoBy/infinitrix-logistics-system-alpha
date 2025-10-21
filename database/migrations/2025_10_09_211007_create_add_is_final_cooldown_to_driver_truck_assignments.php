<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            $table->boolean('is_final_cooldown')->default(false)->after('cooldown_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_truck_assignments', function (Blueprint $table) {
            $table->dropColumn('is_final_cooldown');
        });
    }
};
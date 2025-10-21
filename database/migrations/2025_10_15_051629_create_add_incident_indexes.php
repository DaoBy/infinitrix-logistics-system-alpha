<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            // Index for finding packages with unresolved incidents
            $table->index(['status', 'incident_resolved_at']);
            
            // Index for driver's problem packages
            $table->index(['delivery_request_id', 'status']);
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            // Index for finding orders with delivery issues
            $table->index(['status', 'actual_arrival']);
        });
    }

    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropIndex(['status', 'incident_resolved_at']);
            $table->dropIndex(['delivery_request_id', 'status']);
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'actual_arrival']);
        });
    }
};
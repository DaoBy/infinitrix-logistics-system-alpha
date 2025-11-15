<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('delivery_requests', function (Blueprint $table) {
            // Add cancellation fields
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            
            // Make sure soft deletes are enabled
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason', 'cancelled_by', 'cancelled_at']);
            $table->dropSoftDeletes();
        });
    }
};
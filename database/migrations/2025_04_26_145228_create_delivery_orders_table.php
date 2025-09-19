<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('truck_id')->nullable()->constrained('trucks')->nullOnDelete();
            $table->foreignId('current_region_id')
                  ->nullable()
                  ->constrained('regions')
                  ->onDelete('set null');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'pending', 
                'pending_payment',
                'ready', 
                'assigned', 
                'dispatched',
                'in_transit', 
                'delivered', 
                'completed', 
                'cancelled'
            ])->default('pending');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('estimated_departure')->nullable();
            $table->timestamp('estimated_arrival')->nullable();
            $table->timestamp('actual_departure')->nullable();
            $table->timestamp('actual_arrival')->nullable();
            $table->text('notes')->nullable();
            $table->json('route_plan')->nullable()->after('status');
            // --- add payment fields ---
            $table->string('payment_type')->default('prepaid');
            $table->string('payment_status')->default('unpaid');
            $table->timestamp('payment_verified_at')->nullable();
            // --- end add ---
            $table->timestamps();
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->index(['status', 'estimated_arrival'], 'delivery_orders_status_arrival_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_orders');
    }
};
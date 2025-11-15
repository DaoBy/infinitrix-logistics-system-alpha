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
        Schema::create('downpayments', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to delivery_requests table
            $table->foreignId('delivery_request_id')
                  ->constrained('delivery_requests')
                  ->onDelete('cascade');
            
            // Payment details
            $table->decimal('amount', 10, 2)->default(200.00);
            $table->enum('method', ['gcash', 'bank']);
            $table->string('reference_number');
            $table->string('receipt_image')->nullable();
            
            // Timestamps
            $table->timestamp('paid_at');
            
            // Status - always 'paid' since no verification needed
            $table->enum('status', ['pending', 'paid', 'failed'])->default('paid');
            
            // Polymorphic relationship for who submitted
            $table->string('submitted_by_type');
            $table->unsignedBigInteger('submitted_by_id');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['delivery_request_id']);
            $table->index(['status']);
            $table->index(['method']);
            $table->index(['submitted_by_type', 'submitted_by_id']);
        });
        
        // Also add the new columns to delivery_requests table
        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->boolean('processing_fee_paid')->default(false)->after('payment_due_date');
            $table->decimal('processing_fee_amount', 10, 2)->default(200.00)->after('processing_fee_paid');
            
            // Indexes
            $table->index(['processing_fee_paid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove columns from delivery_requests first
        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->dropIndex(['processing_fee_paid']);
            $table->dropColumn(['processing_fee_paid', 'processing_fee_amount', 'net_price']);
        });
        
        // Then drop the downpayments table
        Schema::dropIfExists('downpayments');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('delivery_requests', function (Blueprint $table) {
            $table->id();
            
            // Customer relationships
            $table->foreignId('sender_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('customers')->onDelete('cascade');
            
            // Region relationships (replacing the old branch columns)
            $table->foreignId('pick_up_region_id')->constrained('regions');
            $table->foreignId('drop_off_region_id')->constrained('regions');
            
            // Payment information
            $table->enum('payment_method', ['cash', 'gcash', 'bank', 'postpaid'])->nullable();
            $table->decimal('total_price', 10, 2);

            // Pricing breakdown
            $table->decimal('base_fee', 10, 2)->after('total_price');
            $table->decimal('volume_fee', 10, 2)->after('base_fee');
            $table->decimal('weight_fee', 10, 2)->after('volume_fee');
            $table->decimal('package_fee', 10, 2)->after('weight_fee');
            $table->json('price_breakdown')->nullable()->after('package_fee');
            
            // Status tracking
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'completed'])->default('pending');
            $table->string('payment_status')->nullable()->after('status');
            $table->boolean('payment_verified')->default(false)->after('payment_status');
            $table->string('reference_number')->nullable()->unique()->after('payment_verified');
            $table->text('rejection_reason')->nullable();
            
            // Approval tracking
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Rejection tracking
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            
            // Creator tracking
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('created_at');
            $table->string('payment_type')->default('prepaid'); // prepaid or postpaid
            $table->boolean('postpaid_approved')->default(false);

            // Payment terms and due date
            $table->enum('payment_terms', ['net_7', 'net_15', 'net_30', 'cnd'])->nullable()->after('payment_type'); // e.g. net_7, net_15, net_30, cnd
            $table->date('payment_due_date')->nullable()->after('payment_terms');

            // Reason for non-payment/refusal
            $table->text('non_payment_reason')->nullable()->after('payment_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_requests');
    }
};
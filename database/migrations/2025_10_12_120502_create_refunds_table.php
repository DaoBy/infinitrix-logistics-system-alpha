<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('processed_by')->constrained('users');
            $table->decimal('refund_amount', 10, 2);
            $table->decimal('original_amount', 10, 2);
            $table->enum('reason', ['damaged', 'lost', 'delayed', 'incomplete', 'customer_request', 'wrong_delivery', 'other']);
            $table->text('description');
            $table->json('refunded_packages')->nullable();
            $table->enum('status', ['pending', 'processed'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('refunds');
    }
};
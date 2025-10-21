<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::create('package_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('package_id')->constrained()->cascadeOnDelete();

            $table->enum('status', [
                'preparing', 
                'ready_for_pickup', 
                'loaded', 
                'in_transit', 
                'delivered', 
                'completed', 
                'returned',
                'rejected',
                'damaged_in_transit', 
                'lost_in_transit'
            ]);

            // NEW: Optional link to the transfer that caused the status change
            $table->foreignId('package_transfer_id')->nullable()->constrained('package_transfers')->nullOnDelete();

            $table->text('remarks')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_status_histories');
    }
};
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
      Schema::create('gcash_payment_attempts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
    $table->string('reference_number');
    $table->decimal('amount', 10, 2);
    $table->string('screenshot_path');
    $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
    $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('verified_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gcash_payment_attempts');
    }
};

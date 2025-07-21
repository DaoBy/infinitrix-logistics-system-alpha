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
    Schema::create('waybills', function (Blueprint $table) {
        $table->id();

        // Links the waybill to a delivery request
        $table->foreignId('delivery_request_id')
            ->constrained()
            ->cascadeOnDelete();

        // User who generated it
        $table->foreignId('generated_by')
            ->constrained('users')
            ->cascadeOnDelete();

        // The actual waybill reference number
        $table->string('waybill_number')->unique();

        // Path to the PDF or document file
        $table->string('file_path')->nullable(); 

        // Optional notes, like “Generated from manifest #XYZ”
        $table->text('notes')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waybills');
    }
};

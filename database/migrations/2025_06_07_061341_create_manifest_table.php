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
      Schema::create('manifests', function (Blueprint $table) {
    $table->id();
    $table->string('manifest_number')->unique();
    $table->foreignId('truck_id')->constrained()->onDelete('cascade');
    $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
    $table->enum('status', ['draft', 'finalized'])->default('draft');
    $table->json('package_ids'); // 🧃 Keep static snapshot
    $table->unsignedBigInteger('generated_by')->nullable();
    $table->foreign('generated_by')->references('id')->on('users')->onDelete('set null');

    $table->string('manifest_pdf_path')->nullable(); // 💾 Optional saved PDF path
    $table->text('notes')->nullable(); // 📝 Optional human-readable notes

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manifest');
    }
};

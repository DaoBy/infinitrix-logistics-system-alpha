<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('package_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_region_id')->constrained('regions');
            $table->foreignId('to_region_id')->constrained('regions');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamp('arrived_at')->nullable();
            $table->boolean('is_return')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::table('package_transfers', function (Blueprint $table) {
            $table->index(['package_id', 'transferred_at'], 'package_transfers_package_transferred_index');
        });
    }

    public function down(): void {
        Schema::dropIfExists('package_transfers');
    }
};
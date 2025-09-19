<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name');
            $table->foreignId('delivery_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_region_id')->constrained('regions');
            $table->foreignId('delivery_confirmation_id')
                  ->nullable()
                  ->constrained('delivery_confirmations')
                  ->onDelete('set null');

            // ⬇️ Added 'ready_for_pickup' to the status enum
            $table->enum('status', [
                'preparing',
                'loaded',
                'in_transit',
                'delivered',
                'ready_for_pickup',
                'completed',
                'returned'
            ])->default('preparing');

            $table->enum('category', ['piece', 'carton', 'sack', 'bundle', 'roll', 'B/R', 'C/S']);
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('volume', 8, 2)->nullable()->unsigned();
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('value', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->index('volume');
        });
    }

    public function down() {
        Schema::dropIfExists('packages');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();

            $table->string('make');
            $table->string('model');
            $table->string('license_plate')->unique();

            $table->decimal('volume_capacity', 10, 2)->default(50.00);
            $table->decimal('current_volume', 10, 2)->default(0.00);
            $table->decimal('weight_capacity', 10, 2)->default(10000.00);
            $table->decimal('current_weight', 10, 2)->default(0.00);

            // 🆕 Expanded status options
            $table->enum('status', [
                'available',
                'nearly_full',
                'assigned',
                'in_transit',
                'returning',
                'maintenance',
                'unavailable'
            ])->default('available');

            $table->integer('year')->nullable();
            $table->string('vin')->nullable()->unique();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('current_value', 10, 2)->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};

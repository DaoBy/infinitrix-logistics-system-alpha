<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('driver_region_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->foreignId('delivery_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'departure',
                'arrival',
                'driver_returned',
                'return_verified_by_staff'
            ]);
            $table->timestamp('logged_at');
            $table->timestamps();
            
            $table->index(['driver_id', 'logged_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('driver_region_logs');
    }
};
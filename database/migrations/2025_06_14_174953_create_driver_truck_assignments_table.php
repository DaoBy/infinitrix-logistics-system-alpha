<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('driver_truck_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('truck_id')->constrained();
            $table->foreignId('region_id')->constrained();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('unassigned_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['driver_id', 'is_active']);
            $table->unique(['truck_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('driver_truck_assignments');
    }
};

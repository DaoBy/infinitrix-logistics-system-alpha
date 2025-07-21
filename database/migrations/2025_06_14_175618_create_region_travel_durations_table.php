<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('region_travel_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_region_id')->constrained('regions');
            $table->foreignId('to_region_id')->constrained('regions');
            $table->integer('estimated_minutes');
            $table->timestamps();
            
            $table->unique(['from_region_id', 'to_region_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('region_travel_durations');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('driver_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_truck_assignment_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->text('remarks')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['driver_truck_assignment_id', 'changed_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('driver_status_logs');
    }
};
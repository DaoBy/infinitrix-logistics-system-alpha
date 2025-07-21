<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('warehouse_address');
            $table->decimal('latitude', 10, 8);  
            $table->decimal('longitude', 11, 8); 
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('name'); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
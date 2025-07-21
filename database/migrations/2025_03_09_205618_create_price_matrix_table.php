<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('price_matrix', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_fee', 10, 2)->default(50.00);
            $table->decimal('volume_rate', 10, 4)->default(10.0000);
            $table->decimal('weight_rate', 10, 2)->default(5.00);
            $table->decimal('package_rate', 10, 2)->default(2.00);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('price_matrix');
    }
};
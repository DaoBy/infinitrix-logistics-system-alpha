<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('actual_height', 8, 2)->nullable()->after('height');
            $table->decimal('actual_width', 8, 2)->nullable()->after('width');
            $table->decimal('actual_length', 8, 2)->nullable()->after('length');
            $table->decimal('actual_weight', 8, 2)->nullable()->after('weight');
        });
    }

    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['actual_height', 'actual_width', 'actual_length', 'actual_weight']);
        });
    }
};
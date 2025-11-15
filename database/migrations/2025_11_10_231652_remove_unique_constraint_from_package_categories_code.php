<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('package_categories', function (Blueprint $table) {
            // Remove the unique constraint from the code column
            $table->dropUnique(['code']);
        });
    }

    public function down()
    {
        Schema::table('package_categories', function (Blueprint $table) {
            // Add the unique constraint back if rolling back
            $table->unique(['code']);
        });
    }
};
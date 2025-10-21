<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            // Change photo_path from string to JSON to store multiple images
            $table->json('photo_path')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->change();
        });
    }
};
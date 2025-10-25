<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // For SQLite, we need to handle this differently
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
            // Create a new clean table
            Schema::create('package_status_histories_new', function (Blueprint $table) {
                $table->id();
                $table->integer('package_id');
                $table->string('status');
                $table->integer('updated_by')->nullable();
                $table->text('remarks')->nullable();
                $table->integer('package_transfer_id')->nullable();
                $table->timestamps();
            });
            
            // Copy data using chunking to avoid constraints
            \Illuminate\Support\Facades\DB::table('package_status_histories')
                ->orderBy('id')
                ->chunk(100, function ($records) {
                    \Illuminate\Support\Facades\DB::table('package_status_histories_new')->insert(
                        $records->toArray()
                    );
                });
            
            // Replace the tables
            Schema::dropIfExists('package_status_histories');
            Schema::rename('package_status_histories_new', 'package_status_histories');
        }
    }

    public function down()
    {
        // Cannot easily revert
    }
};
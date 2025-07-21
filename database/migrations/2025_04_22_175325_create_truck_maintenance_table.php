<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up()
{
    Schema::create('truck_maintenances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('truck_id')->constrained()->cascadeOnDelete();
        $table->foreignId('component_id')->nullable()->constrained('truck_components')->cascadeOnDelete();
        $table->string('type')->after('service_provider')->default('routine');
        $table->date('maintenance_date');
        $table->text('service_details');
        $table->decimal('cost', 10, 2)->nullable();
        $table->string('service_provider')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}

    public function down()
    {
        Schema::dropIfExists('truck_maintenances');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            
            // Enforce one-to-one relationship with users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->unique();

             $table->foreignId('region_id')
                  ->nullable()
                  ->constrained('regions')
                  ->onDelete('set null');
            
            // Company-assigned employee ID (unique across all employees)
            $table->string('employee_id', 50)->unique();
            
            // Contact information (expanded to match customer structure)
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable()->unique();
            $table->string('building_number')->nullable();
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zip_code', 4)->nullable();
            
            // Employment information
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->date('archived_at')->nullable();
            
            // Additional notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('hire_date');
            $table->index('archived_at');
            $table->index('mobile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
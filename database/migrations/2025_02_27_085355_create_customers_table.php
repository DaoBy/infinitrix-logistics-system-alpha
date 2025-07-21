<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); 
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('mobile')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('building_number')->nullable();
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zip_code')->nullable();
            $table->enum('customer_category', ['individual', 'company'])->default('individual');
            $table->enum('frequency_type', ['regular', 'occasional'])->default('occasional');
            $table->enum('payment_terms', ['prepaid', 'postpaid'])
                  ->default('prepaid');
            $table->decimal('credit_limit', 10, 2)
                  ->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->date('archived_at')->nullable();

            $table->index(['email', 'mobile']);
            $table->index(['customer_category']);
            $table->index(['frequency_type']);
        });
    }

    public function down() {
        Schema::dropIfExists('customers');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('has_delivery_history')->default(false)->after('is_profile_complete');
            $table->boolean('critical_fields_locked')->default(false)->after('has_delivery_history');
        });

        Schema::create('customer_profile_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->string('change_type'); // customer_update, admin_update, auto_locked, approved_request
            $table->string('field_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('change_reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profile_audits');
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['has_delivery_history', 'critical_fields_locked']);
        });
    }
};
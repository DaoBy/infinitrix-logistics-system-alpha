<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('method');
            $table->enum('source', ['branch_staff', 'collector', 'customer_online'])->default('branch_staff')->after('type');
            $table->string('submitted_by_type')->nullable()->after('collected_by'); // Morph to user or customer
            $table->unsignedBigInteger('submitted_by_id')->nullable()->after('submitted_by_type');
        });

        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->enum('payment_source', ['branch', 'online'])->nullable()->after('payment_method');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'source', 'submitted_by_type', 'submitted_by_id']);
        });

        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->dropColumn('payment_source');
        });
    }
};
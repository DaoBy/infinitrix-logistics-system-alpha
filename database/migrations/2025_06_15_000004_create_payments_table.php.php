<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_request_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['prepaid', 'postpaid']);
            $table->enum('method', ['cash', 'gcash', 'bank']);
            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at');
            $table->foreignId('verified_by')->nullable()->constrained('users'); // <-- make nullable
            $table->string('receipt_image')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('delivery_order_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('collected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('collected_at')->nullable()->after('collected_by');
            $table->string('status')->default('pending_verification');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['delivery_order_id']);
            $table->dropColumn('delivery_order_id');
            $table->dropForeign(['collected_by']);
            $table->dropColumn('collected_by');
            $table->dropColumn('collected_at');
            $table->dropColumn('status');
            $table->dropColumn('rejection_reason');
            $table->dropColumn('verified_at');
        });
        Schema::dropIfExists('payments');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('refunds', function (Blueprint $table) {
        $table->enum('type', ['refund', 'adjustment'])->default('refund')->after('processed_by');
        $table->decimal('adjusted_amount', 10, 2)->nullable()->after('original_amount');
        $table->enum('status', ['pending', 'pending_adjustment', 'processed', 'adjusted'])->default('pending')->change();
    });
}

public function down()
{
    Schema::table('refunds', function (Blueprint $table) {
        $table->dropColumn(['type', 'adjusted_amount']);
        // Note: You can't easily revert the enum change in SQLite
    });
}
};

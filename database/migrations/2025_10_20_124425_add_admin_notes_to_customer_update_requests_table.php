<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customer_update_requests', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('reason');
        });
    }

    public function down()
    {
        Schema::table('customer_update_requests', function (Blueprint $table) {
            $table->dropColumn('admin_notes');
        });
    }
};
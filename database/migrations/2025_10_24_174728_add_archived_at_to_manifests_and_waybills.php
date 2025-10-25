<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('manifests', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('updated_at');
        });

        Schema::table('waybills', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('updated_at');
        });
    }

    public function down()
    {
        Schema::table('manifests', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });

        Schema::table('waybills', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
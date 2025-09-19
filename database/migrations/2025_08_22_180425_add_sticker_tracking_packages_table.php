<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->timestamp('sticker_printed_at')->nullable();
            $table->foreignId('sticker_printed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropForeign(['sticker_printed_by']);
            $table->dropColumn('sticker_printed_at');
            $table->dropColumn('sticker_printed_by');
        });
    }
};
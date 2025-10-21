<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            // Add package verification columns
            $table->timestamp('verified_at')->nullable()->after('status');
            $table->string('received_by')->nullable()->after('verified_at');
            $table->enum('verification_status', ['verified', 'missing', 'damaged', 'wrong_branch'])
                  ->nullable()
                  ->after('received_by');
            $table->text('verification_notes')->nullable()->after('verification_status');
            $table->string('verification_method')->nullable()->after('verification_notes');
            $table->foreignId('verified_by')
                  ->nullable()
                  ->after('verification_method')
                  ->constrained('users')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'verified_at',
                'received_by',
                'verification_status',
                'verification_notes', 
                'verification_method',
                'verified_by'
            ]);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('users', 'pending_email')) {
                $table->string('pending_email')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'email_change_verification_code')) {
                $table->string('email_change_verification_code', 6)->nullable()->after('pending_email');
            }
            
            if (!Schema::hasColumn('users', 'email_change_verification_code_expires_at')) {
                $table->timestamp('email_change_verification_code_expires_at')->nullable()->after('email_change_verification_code');
            }
            
            // These columns likely already exist based on your error
            // if (!Schema::hasColumn('users', 'email_verification_code')) {
            //     $table->string('email_verification_code', 6)->nullable();
            // }
            // 
            // if (!Schema::hasColumn('users', 'email_verification_code_expires_at')) {
            //     $table->timestamp('email_verification_code_expires_at')->nullable();
            // }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pending_email',
                'email_change_verification_code',
                'email_change_verification_code_expires_at',
                // Don't drop these as they already existed
                // 'email_verification_code',
                // 'email_verification_code_expires_at'
            ]);
        });
    }
};
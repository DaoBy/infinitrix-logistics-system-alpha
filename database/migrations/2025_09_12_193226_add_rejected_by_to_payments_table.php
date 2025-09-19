<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('payments', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('payments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }
            
            // rejection_reason already exists, so skip it
            // if (!Schema::hasColumn('payments', 'rejection_reason')) {
            //     $table->text('rejection_reason')->nullable();
            // }
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Only drop columns if they exist
            if (Schema::hasColumn('payments', 'rejected_by')) {
                $table->dropForeign(['rejected_by']);
                $table->dropColumn('rejected_by');
            }
            
            if (Schema::hasColumn('payments', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            
            // Don't drop rejection_reason as it might contain data
            // if (Schema::hasColumn('payments', 'rejection_reason')) {
            //     $table->dropColumn('rejection_reason');
            // }
        });
    }
};
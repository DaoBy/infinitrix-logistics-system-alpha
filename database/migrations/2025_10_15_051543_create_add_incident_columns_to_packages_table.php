<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            // Incident reporting fields
            $table->json('incident_evidence')->nullable()->after('sticker_printed_by');
            $table->text('incident_description')->nullable()->after('incident_evidence');
            $table->timestamp('incident_reported_at')->nullable()->after('incident_description');
            $table->foreignId('incident_reported_by')->nullable()->after('incident_reported_at')
                  ->constrained('users')->onDelete('set null');
            $table->timestamp('incident_resolved_at')->nullable()->after('incident_reported_by');
            $table->foreignId('incident_resolved_by')->nullable()->after('incident_resolved_at')
                  ->constrained('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'incident_evidence',
                'incident_description', 
                'incident_reported_at',
                'incident_reported_by',
                'incident_resolved_at',
                'incident_resolved_by'
            ]);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Correlation ID for relating multiple logs from the same request
            $table->uuid('request_id')->nullable()->after('user_agent')->index();
            
            // Cryptographic link to previous record (hash chaining)
            $table->string('integrity_hash', 64)->nullable()->after('metadata')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['request_id', 'integrity_hash']);
        });
    }
};

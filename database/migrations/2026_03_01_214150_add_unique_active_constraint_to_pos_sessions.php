<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            // is_active: 1 if session is open/closing, NULL if closed
            // Allows multiple NULLs (closed sessions) but only one 1 (open session) per user
            $table->boolean('is_active')->nullable()->after('status');
            $table->unique(['opened_by', 'is_active'], 'uq_user_active_session');
        });

        // Initialize existing sessions
        DB::table('pos_sessions')->whereIn('status', ['open', 'closing'])->update(['is_active' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropUnique('uq_user_active_session');
            $table->dropColumn('is_active');
        });
    }
};

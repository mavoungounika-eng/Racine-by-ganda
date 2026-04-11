<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * CRITICAL SECURITY MIGRATION
     * 
     * Initialize auth_version for all existing users to prevent privilege escalation.
     * 
     * Context:
     * - UserContextResolver.validateSession() now FAILS CLOSED if auth_version is null
     * - All existing users have auth_version = null
     * - Without this migration, ALL existing users will be logged out
     * 
     * This migration:
     * 1. Sets auth_version = 1 for all users where auth_version is null
     * 2. Ensures future users get auth_version = 1 by default (via model or DB default)
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('auth_version')
            ->update([
                'auth_version' => 1,
                'updated_at' => now(),
            ]);

        \Log::info('[SECURITY MIGRATION] Initialized auth_version for existing users', [
            'affected_rows' => DB::table('users')->whereNull('auth_version')->count(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

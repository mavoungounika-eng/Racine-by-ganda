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
        // Ensure auth_version column exists and has proper defaults
        if (!Schema::hasColumn('users', 'auth_version')) {
            return; // Already handled by previous migration
        }

        // Set any null auth_versions to 1
        DB::table('users')->whereNull('auth_version')->update(['auth_version' => 1]);

        // For SQLite, we cannot modify columns directly, so we rely on the NOT NULL constraint
        // to be enforced by the application and factory defaults
        // The UserFactory now ensures auth_version is always set to at least 1
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse, column already exists from previous migration
    }
};

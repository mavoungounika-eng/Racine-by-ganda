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
        Schema::table('creator_profiles', function (Blueprint $table) {
            // Photo personnelle du vendeur/créateur (différent du logo boutique)
            $table->string('avatar_path')->nullable()->after('logo_path');
            
            // Titre/fonction du créateur (ex: "Artisan maroquinier", "Designer textile")
            $table->string('creator_title')->nullable()->after('brand_name');
            
            // Facebook moderne (remplace le champ legacy)
            $table->string('facebook_url')->nullable()->after('tiktok_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'creator_title', 'facebook_url']);
        });
    }
};

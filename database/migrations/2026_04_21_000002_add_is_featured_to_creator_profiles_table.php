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
            // T-02: Ajouter colonne is_featured pour distinguer le featured creator en vue
            if (!Schema::hasColumn('creator_profiles', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active')->comment('Marqué comme créateur vedette pour affichage en homepage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creator_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('creator_profiles', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
        });
    }
};

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
            // T-04: Ajouter colonne risk_level pour RiskDetectionService
            // Valeurs : 'normal', 'watch', 'high', 'critical'
            $table->string('risk_level')
                ->default('normal')
                ->after('status')
                ->comment('Risk level detected by RiskDetectionService (normal/watch/high/critical)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->dropColumn('risk_level');
        });
    }
};

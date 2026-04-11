<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Met à jour la table creator_subscriptions pour intégrer le système de plans.
     * Ajoute creator_plan_id et simplifie la structure pour le système capabilities.
     * 
     * Note: On garde la compatibilité avec Stripe mais on ajoute la référence au plan.
     */
    public function up(): void
    {
        Schema::table('creator_subscriptions', function (Blueprint $table) {
            // Ajouter creator_plan_id (référence au plan)
            $table->foreignId('creator_plan_id')
                ->nullable()
                ->after('creator_profile_id')
                ->constrained('creator_plans')
                ->onDelete('set null')
                ->comment('Plan d\'abonnement actuel');
            
            // Ajouter creator_id (user_id) pour faciliter les requêtes
            // On peut le dériver de creator_profile_id, mais c'est plus pratique d'avoir directement
            $table->foreignId('creator_id')
                ->nullable()
                ->after('creator_profile_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Référence directe au user créateur');
            
            // Ajouter started_at et ends_at pour gérer les périodes
            $table->timestamp('started_at')->nullable()->after('current_period_start')->comment('Date de début de l\'abonnement');
            $table->timestamp('ends_at')->nullable()->after('current_period_end')->comment('Date de fin de l\'abonnement');
            
            // Index pour améliorer les performances
            $table->index('creator_id');
            $table->index('creator_plan_id');
            $table->index('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creator_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['creator_plan_id']);
            $table->dropForeign(['creator_id']);
            $table->dropIndex(['creator_id']);
            $table->dropIndex(['creator_plan_id']);
            $table->dropIndex(['ends_at']);
            $table->dropColumn(['creator_plan_id', 'creator_id', 'started_at', 'ends_at']);
        });
    }
};

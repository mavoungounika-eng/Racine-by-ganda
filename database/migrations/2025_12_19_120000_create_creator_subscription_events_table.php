<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crée la table pour tracker l'historique des événements d'abonnement.
     * Phase 6.4 - Optimisation Automatique
     */
    public function up(): void
    {
        Schema::create('creator_subscription_events', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('creator_subscription_id')
                ->constrained('creator_subscriptions')
                ->onDelete('cascade');
            
            $table->foreignId('creator_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            // Type d'événement
            $table->string('event_type'); // created, upgraded, downgraded, canceled, suspended, reactivated, etc.
            
            // Métadonnées
            $table->json('metadata')->nullable();
            
            // Ancien et nouveau statut (pour tracking des changements)
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            
            // Ancien et nouveau plan (pour tracking des changements)
            $table->foreignId('old_plan_id')->nullable()->constrained('creator_plans');
            $table->foreignId('new_plan_id')->nullable()->constrained('creator_plans');
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('creator_id');
            $table->index('event_type');
            $table->index('created_at');
            $table->index(['creator_id', 'event_type', 'created_at'], 'sub_events_composite_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_subscription_events');
    }
};


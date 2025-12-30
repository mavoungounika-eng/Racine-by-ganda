<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     * 
     * Crée la table pour stocker les comptes Stripe Connect des créateurs.
     * Cette table permet de :
     * - Lier un créateur à son compte Stripe Connect
     * - Suivre l'état de l'onboarding Stripe
     * - Vérifier si le créateur peut recevoir des paiements
     */
    public function up(): void
    {
        Schema::create('creator_stripe_accounts', function (Blueprint $table) {
            $table->id();
            
            // Relation avec creator_profiles (un créateur = un compte Stripe)
            $table->foreignId('creator_profile_id')
                ->unique()
                ->constrained('creator_profiles')
                ->onDelete('cascade');
            
            // Identifiant du compte Stripe Connect (acct_xxx)
            $table->string('stripe_account_id')->unique();
            
            // Type de compte (Express pour notre cas)
            $table->enum('account_type', ['express'])->default('express');
            
            // Statut de l'onboarding Stripe
            $table->enum('onboarding_status', [
                'pending',      // Pas encore commencé
                'in_progress',  // En cours de remplissage
                'complete',     // Terminé et actif
                'failed'        // Échec (données invalides, refus, etc.)
            ])->default('pending');
            
            // Indicateurs Stripe
            $table->boolean('charges_enabled')->default(false)->comment('Le créateur peut recevoir des paiements');
            $table->boolean('payouts_enabled')->default(false)->comment('Le créateur peut recevoir des versements');
            $table->boolean('details_submitted')->default(false)->comment('Informations KYC soumises');
            
            // Exigences Stripe (JSON)
            $table->json('requirements_currently_due')->nullable()->comment('Exigences en attente (ex: external_account, representative)');
            $table->json('requirements_eventually_due')->nullable()->comment('Exigences futures');
            
            // Capacités du compte (JSON)
            $table->json('capabilities')->nullable()->comment('Capacités activées (card_payments, transfers, etc.)');
            
            // Lien d'onboarding Stripe
            $table->string('onboarding_link_url')->nullable()->comment('URL du lien d\'onboarding Stripe');
            $table->timestamp('onboarding_link_expires_at')->nullable()->comment('Date d\'expiration du lien');
            
            // Synchronisation
            $table->timestamp('last_synced_at')->nullable()->comment('Dernière synchronisation avec Stripe');
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('creator_profile_id');
            $table->index('stripe_account_id');
            $table->index('onboarding_status');
            $table->index('charges_enabled');
            $table->index('payouts_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_stripe_accounts');
    }
};

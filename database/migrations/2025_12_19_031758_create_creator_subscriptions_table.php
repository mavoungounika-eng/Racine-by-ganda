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
     * Crée la table pour gérer les abonnements mensuels des créateurs à la plateforme.
     * Chaque créateur doit avoir un abonnement actif pour pouvoir vendre.
     * Si l'abonnement n'est pas payé, le créateur est suspendu automatiquement.
     */
    public function up(): void
    {
        Schema::create('creator_subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Relation avec creator_profiles (un créateur = un abonnement)
            $table->foreignId('creator_profile_id')
                ->unique()
                ->constrained('creator_profiles')
                ->onDelete('cascade');
            
            // Identifiants Stripe
            $table->string('stripe_subscription_id')->unique()->comment('ID de l\'abonnement Stripe (sub_xxx)');
            $table->string('stripe_customer_id')->comment('ID du client Stripe Billing (cus_xxx)');
            $table->string('stripe_price_id')->comment('ID du prix Stripe (price_xxx)');
            
            // Statut de l'abonnement
            $table->enum('status', [
                'incomplete',           // Créé mais premier paiement non effectué
                'incomplete_expired',   // Premier paiement expiré
                'trialing',             // Période d'essai active
                'active',               // Abonnement actif et payé
                'past_due',             // Paiement en retard (période de grâce)
                'canceled',             // Annulé (peut encore être actif jusqu'à fin période)
                'unpaid'                // Impayé (doit suspendre le créateur)
            ])->default('incomplete');
            
            // Période actuelle
            $table->timestamp('current_period_start')->nullable()->comment('Début de la période actuelle');
            $table->timestamp('current_period_end')->nullable()->index()->comment('Fin de la période actuelle');
            
            // Annulation
            $table->boolean('cancel_at_period_end')->default(false)->comment('Annulation à la fin de la période');
            $table->timestamp('canceled_at')->nullable()->comment('Date d\'annulation');
            
            // Période d'essai (optionnelle)
            $table->timestamp('trial_start')->nullable()->comment('Début de la période d\'essai');
            $table->timestamp('trial_end')->nullable()->comment('Fin de la période d\'essai');
            
            // Métadonnées supplémentaires
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('creator_profile_id');
            // stripe_subscription_id a déjà un index unique
            $table->index('stripe_customer_id');
            $table->index('status');
            // current_period_end a déjà un index défini plus haut
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_subscriptions');
    }
};

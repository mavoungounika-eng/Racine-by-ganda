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
     * Crée la table pour stocker l'historique des factures d'abonnement.
     * Cette table permet de :
     * - Avoir un historique complet des paiements
     * - Faciliter l'audit et la conformité
     * - Permettre au support client de voir les factures
     * - Générer des rapports financiers
     */
    public function up(): void
    {
        Schema::create('creator_subscription_invoices', function (Blueprint $table) {
            $table->id();
            
            // Relation avec creator_subscriptions
            $table->foreignId('creator_subscription_id')
                ->constrained('creator_subscriptions')
                ->onDelete('cascade');
            
            // Identifiants Stripe
            $table->string('stripe_invoice_id')->unique()->comment('ID de la facture Stripe (in_xxx)');
            $table->string('stripe_charge_id')->nullable()->comment('ID du paiement Stripe (ch_xxx)');
            
            // Montant et devise
            $table->decimal('amount', 10, 2)->comment('Montant de la facture');
            $table->string('currency', 3)->default('XAF')->comment('Devise (XAF, XOF, etc.)');
            
            // Statut de la facture
            $table->enum('status', [
                'draft',         // Brouillon
                'open',          // Ouverte (en attente de paiement)
                'paid',          // Payée
                'uncollectible', // Irrécouvrable
                'void'           // Annulée
            ])->default('open');
            
            // Dates
            $table->timestamp('paid_at')->nullable()->comment('Date de paiement');
            $table->timestamp('due_date')->nullable()->comment('Date d\'échéance');
            
            // URLs Stripe
            $table->string('hosted_invoice_url')->nullable()->comment('URL de la facture sur Stripe');
            $table->string('invoice_pdf')->nullable()->comment('URL du PDF de la facture');
            
            // Métadonnées
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('creator_subscription_id');
            // stripe_invoice_id a déjà un index unique
            $table->index('stripe_charge_id');
            $table->index('status');
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_subscription_invoices');
    }
};

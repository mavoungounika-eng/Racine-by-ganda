<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Créer table financial_intents
 * 
 * OBJECTIF: Préparer l'architecture Intent-Based pour les opérations financières.
 * 
 * PRINCIPE:
 * - Un intent représente l'INTENTION d'une opération financière
 * - Les listeners CONSOMMENT des intents, ils ne CRÉENT pas de vérité financière
 * - Le point d'irréversibilité est marqué par le passage à 'committed'
 * 
 * STATUTS:
 * - pending: Intent créé, en attente de traitement
 * - processing: En cours de traitement par un listener
 * - committed: Écriture comptable créée, IRRÉVERSIBLE
 * - reversed: Contre-passation effectuée
 * - failed: Échec définitif après retries
 * 
 * USAGE FUTUR:
 * 1. Controller/Service crée un intent (pending)
 * 2. Event dispatché avec intent_id
 * 3. Listener vérifie intent, passe à processing
 * 4. Listener crée écriture, passe à committed
 * 5. En cas de retry, listener vérifie statut et skip si committed
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_intents', function (Blueprint $table) {
            $table->id();
            
            // Type d'opération financière
            $table->string('intent_type', 50)->comment('Type: payment, payout, refund, adjustment');
            
            // Référence métier
            $table->string('reference_type', 50)->comment('order, creator_payout, refund, etc.');
            $table->unsignedBigInteger('reference_id');
            
            // Montant et devise
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('XAF');
            
            // Statut de l'intent
            $table->enum('status', [
                'pending',     // Créé, en attente
                'processing',  // En cours de traitement
                'committed',   // Écriture créée, IRRÉVERSIBLE
                'reversed',    // Contre-passation effectuée
                'failed',      // Échec définitif
            ])->default('pending');
            
            // Lien vers l'écriture comptable (quand committed)
            $table->unsignedBigInteger('accounting_entry_id')->nullable();
            
            // Métadonnées
            $table->json('metadata')->nullable()->comment('Données contextuelles');
            
            // Traçabilité
            $table->string('idempotency_key', 64)->unique()->comment('Clé unique pour garantir idempotence');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('committed_by')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
            
            // Index
            $table->unique(['reference_type', 'reference_id'], 'uq_financial_intents_reference');
            $table->index('status');
            $table->index('intent_type');
            $table->index(['status', 'created_at']); // Pour cleanup pending
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_intents');
    }
};

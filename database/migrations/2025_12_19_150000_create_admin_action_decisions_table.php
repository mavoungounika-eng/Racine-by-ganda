<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crée la table pour tracker toutes les actions proposées et leurs décisions.
     * Aucune action ne peut s'exécuter sans enregistrement ici.
     */
    public function up(): void
    {
        Schema::create('admin_action_decisions', function (Blueprint $table) {
            $table->id();
            
            // Type d'action proposée
            $table->enum('action_type', [
                'MONITOR',
                'SEND_REMINDER',
                'REQUEST_KYC_UPDATE',
                'FLAG_FOR_REVIEW',
                'PROPOSE_SUSPENSION',
                'NO_ACTION',
            ])->comment('Type d\'action proposée par le système');
            
            // Cible de l'action
            $table->enum('target_type', [
                'creator',
                'subscription',
                'system',
            ])->comment('Type de cible (creator, subscription, system)');
            
            $table->unsignedBigInteger('target_id')->comment('ID de la cible');
            
            // Proposé par (système = null, admin = user_id)
            $table->unsignedBigInteger('proposed_by')->nullable()->comment('User ID qui a proposé (null = système)');
            
            // Approuvé/rejeté par
            $table->unsignedBigInteger('approved_by')->nullable()->comment('User ID admin qui a approuvé');
            $table->unsignedBigInteger('rejected_by')->nullable()->comment('User ID admin qui a rejeté');
            
            // Statut
            $table->enum('status', [
                'pending',      // En attente de validation
                'approved',     // Approuvé mais pas encore exécuté
                'rejected',     // Rejeté par admin
                'executed',     // Exécuté avec succès
                'failed',       // Échec d'exécution
                'cancelled',    // Annulé avant exécution
            ])->default('pending');
            
            // Métadonnées
            $table->decimal('confidence', 5, 2)->nullable()->comment('Niveau de confiance (0-100)');
            $table->string('risk_level', 20)->nullable()->comment('Niveau de risque (low, medium, high)');
            $table->text('justification')->comment('Justification de l\'action proposée');
            $table->text('decision_reason')->nullable()->comment('Raison de la décision admin');
            $table->json('source_data')->nullable()->comment('Données sources (scores, alertes, risques)');
            $table->json('state_before')->nullable()->comment('État avant exécution (pour audit)');
            $table->json('state_after')->nullable()->comment('État après exécution (pour audit)');
            $table->json('execution_result')->nullable()->comment('Résultat de l\'exécution');
            
            // Timestamps
            $table->timestamp('proposed_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            
            // Index
            $table->index(['target_type', 'target_id']);
            $table->index('status');
            $table->index('action_type');
            $table->index('proposed_at');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_action_decisions');
    }
};




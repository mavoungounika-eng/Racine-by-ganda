<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crée la table pour stocker les préférences de paiement des créateurs.
     * Cette table permet de :
     * - Configurer Mobile Money comme moyen de secours
     * - Définir le calendrier de versement
     * - Gérer le seuil minimum de paiement
     * - Configurer les préférences de notification
     */
    public function up(): void
    {
        Schema::create('payment_preferences', function (Blueprint $table) {
            $table->id();
            
            // Relation avec creator_profiles (un créateur = une préférence)
            $table->foreignId('creator_profile_id')
                ->unique()
                ->constrained('creator_profiles')
                ->onDelete('cascade');
            
            // ========================================
            // MOBILE MONEY (Moyen de secours)
            // ========================================
            $table->enum('mobile_money_operator', ['orange', 'mtn', 'wave'])
                ->nullable()
                ->comment('Opérateur Mobile Money');
            
            $table->string('mobile_money_number', 20)
                ->nullable()
                ->comment('Numéro de téléphone Mobile Money');
            
            $table->boolean('mobile_money_verified')
                ->default(false)
                ->comment('Numéro Mobile Money vérifié');
            
            $table->timestamp('mobile_money_verified_at')
                ->nullable()
                ->comment('Date de vérification Mobile Money');
            
            // ========================================
            // CALENDRIER DE VERSEMENT
            // ========================================
            $table->enum('payout_schedule', ['automatic', 'monthly', 'manual'])
                ->default('automatic')
                ->comment('Fréquence des versements');
            
            $table->integer('minimum_payout_threshold')
                ->default(25000)
                ->comment('Seuil minimum de versement en FCFA');
            
            // ========================================
            // PRÉFÉRENCES DE NOTIFICATION
            // ========================================
            $table->boolean('notify_email')
                ->default(true)
                ->comment('Notifications par email');
            
            $table->boolean('notify_sms')
                ->default(false)
                ->comment('Notifications par SMS');
            
            $table->boolean('notify_push')
                ->default(true)
                ->comment('Notifications push');
            
            // ========================================
            // INFORMATIONS FISCALES
            // ========================================
            $table->boolean('tax_info_completed')
                ->default(false)
                ->comment('Informations fiscales complétées');
            
            $table->string('tax_id', 50)
                ->nullable()
                ->comment('Numéro d\'identification fiscale');
            
            $table->string('tax_country', 2)
                ->default('CG')
                ->comment('Pays fiscal (code ISO)');
            
            // ========================================
            // MÉTADONNÉES
            // ========================================
            $table->json('metadata')
                ->nullable()
                ->comment('Données supplémentaires (JSON)');
            
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('creator_profile_id');
            $table->index('payout_schedule');
            $table->index('mobile_money_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_preferences');
    }
};

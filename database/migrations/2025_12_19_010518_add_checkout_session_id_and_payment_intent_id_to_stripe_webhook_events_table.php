<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajoute les colonnes checkout_session_id et payment_intent_id
     * pour stocker les identifiants Stripe extraits des événements webhook.
     * 
     * Rétrocompatible : vérifie si les colonnes et index existent déjà avant de les ajouter.
     * 
     * NOTE: Cette migration est redondante avec 2025_12_17_185500_add_stripe_identifiers_to_webhook_events_table.php
     * mais elle est conservée pour garantir la compatibilité si l'ancienne migration n'a pas été exécutée.
     */
    public function up(): void
    {
        // Vérifier l'existence des colonnes AVANT d'entrer dans la closure
        $hasCheckoutSessionId = Schema::hasColumn('stripe_webhook_events', 'checkout_session_id');
        $hasPaymentIntentId = Schema::hasColumn('stripe_webhook_events', 'payment_intent_id');

        // Ajouter les colonnes seulement si elles n'existent pas
        if (!$hasCheckoutSessionId || !$hasPaymentIntentId) {
            Schema::table('stripe_webhook_events', function (Blueprint $table) use ($hasCheckoutSessionId, $hasPaymentIntentId) {
                if (!$hasCheckoutSessionId) {
                    $table->string('checkout_session_id')->nullable()->after('event_type');
                }
                
                if (!$hasPaymentIntentId) {
                    $table->string('payment_intent_id')->nullable()->after('checkout_session_id');
                }
            });
        }

        // Ajouter les index seulement si les colonnes existent et que les index n'existent pas
        if ($hasCheckoutSessionId && !$this->hasIndex('stripe_webhook_events', 'checkout_session_id')) {
            Schema::table('stripe_webhook_events', function (Blueprint $table) {
                try {
                    $table->index('checkout_session_id');
                } catch (\Exception $e) {
                    // Index existe déjà ou erreur, ignorer seulement si c'est une clé dupliquée
                    if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                        !str_contains($e->getMessage(), 'already exists')) {
                        throw $e;
                    }
                }
            });
        }
        
        if ($hasPaymentIntentId && !$this->hasIndex('stripe_webhook_events', 'payment_intent_id')) {
            Schema::table('stripe_webhook_events', function (Blueprint $table) {
                try {
                    $table->index('payment_intent_id');
                } catch (\Exception $e) {
                    // Index existe déjà ou erreur, ignorer seulement si c'est une clé dupliquée
                    if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                        !str_contains($e->getMessage(), 'already exists')) {
                        throw $e;
                    }
                }
            });
        }
    }

    /**
     * Vérifier si un index existe sur une colonne donnée
     * Compatible MySQL et SQLite
     * 
     * @param string $table
     * @param string $column
     * @return bool
     */
    private function hasIndex(string $table, string $column): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        // Nom d'index standard Laravel: {table}_{column}_index
        $indexName = "{$table}_{$column}_index";
        
        try {
            if ($driver === 'sqlite') {
                // SQLite: utiliser sqlite_master
                $indexes = DB::select(
                    "SELECT COUNT(*) as count 
                     FROM sqlite_master 
                     WHERE type = 'index' 
                     AND name = ?",
                    [$indexName]
                );
                
                return isset($indexes[0]) && $indexes[0]->count > 0;
            } else {
                // MySQL/PostgreSQL: utiliser information_schema
                $databaseName = $connection->getDatabaseName();
                
                $indexes = DB::select(
                    "SELECT COUNT(*) as count 
                     FROM information_schema.statistics 
                     WHERE table_schema = ? 
                     AND table_name = ? 
                     AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );
                
                return isset($indexes[0]) && $indexes[0]->count > 0;
            }
        } catch (\Exception $e) {
            // En cas d'erreur, retourner false pour tenter la création
            return false;
        }
    }

    /**
     * Reverse the migrations.
     * 
     * NOTE: Cette méthode ne supprime PAS les colonnes si elles ont été créées
     * par la migration 2025_12_17_185500 pour éviter les conflits.
     */
    public function down(): void
    {
        Schema::table('stripe_webhook_events', function (Blueprint $table) {
            // Supprimer les index seulement s'ils existent
            if ($this->hasIndex('stripe_webhook_events', 'checkout_session_id')) {
                try {
                    $table->dropIndex(['checkout_session_id']);
                } catch (\Exception $e) {
                    // Index n'existe pas, ignorer
                }
            }
            
            if ($this->hasIndex('stripe_webhook_events', 'payment_intent_id')) {
                try {
                    $table->dropIndex(['payment_intent_id']);
                } catch (\Exception $e) {
                    // Index n'existe pas, ignorer
                }
            }
            
            // Ne PAS supprimer les colonnes car elles peuvent avoir été créées par l'autre migration
            // Si vous voulez vraiment les supprimer, faites-le manuellement ou via l'autre migration
        });
    }
};

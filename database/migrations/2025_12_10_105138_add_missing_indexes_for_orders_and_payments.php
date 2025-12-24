<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration P4 : Ajouter les index manquants pour améliorer les performances
 * 
 * Ces index sont utilisés dans :
 * - CleanupAbandonedOrders (filtrage par payment_method)
 * - Requêtes admin/back-office (filtrage par payment_method, provider, channel)
 * - Statistiques et rapports
 * 
 * Colonnes indexées :
 * - orders.payment_method : utilisé pour filtrer les commandes par méthode de paiement
 * - payments.provider : utilisé pour filtrer les paiements par fournisseur (stripe, monetbil, etc.)
 * - payments.channel : utilisé pour filtrer les paiements par canal (card, mobile_money, etc.)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Workaround SQLite (RBG-P0-002) : SQLite ne supporte pas information_schema.statistics.
     * Utilisation de try-catch pour gérer les erreurs "index already exists" de manière cross-DB.
     */
    public function up(): void
    {
        // Protéger l'ajout de l'index sur payment_method : vérifier que la table et la colonne existent
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_method')) {
            Schema::table('orders', function (Blueprint $table) {
                // Index sur payment_method pour améliorer les requêtes de filtrage
                // Utilisé notamment dans CleanupAbandonedOrders et les statistiques
                // Workaround SQLite (RBG-P0-002) : try-catch au lieu de hasIndex()
                try {
                    $table->index('payment_method', 'orders_payment_method_index');
                } catch (\Exception $e) {
                    // Index existe déjà, ignorer l'erreur
                    // SQLite : "index orders_payment_method_index already exists"
                    // MySQL : "Duplicate key name 'orders_payment_method_index'"
                    if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                        !str_contains($e->getMessage(), 'already exists')) {
                        throw $e;
                    }
                }
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            // Index sur provider pour améliorer les requêtes de filtrage par fournisseur
            // Workaround SQLite (RBG-P0-002) : try-catch au lieu de hasIndex()
            try {
                $table->index('provider', 'payments_provider_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }

            // Index sur channel pour améliorer les requêtes de filtrage par canal
            // Utilisé notamment dans MobileMoneyPaymentController
            // Workaround SQLite (RBG-P0-002) : try-catch au lieu de hasIndex()
            try {
                $table->index('channel', 'payments_channel_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Workaround SQLite (RBG-P0-002) : try-catch pour gérer les erreurs "index does not exist".
     */
    public function down(): void
    {
        // Protéger la suppression de l'index : vérifier que la table et la colonne existent
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_method')) {
            Schema::table('orders', function (Blueprint $table) {
                // Workaround SQLite (RBG-P0-002) : try-catch au lieu de hasIndex()
                try {
                    $table->dropIndex('orders_payment_method_index');
                } catch (\Exception $e) {
                    // Index n'existe pas, ignorer l'erreur
                    if (!str_contains($e->getMessage(), 'does not exist') && 
                        !str_contains($e->getMessage(), 'Unknown key')) {
                        throw $e;
                    }
                }
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            // Workaround SQLite (RBG-P0-002) : try-catch au lieu de hasIndex()
            try {
                $table->dropIndex('payments_provider_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }

            try {
                $table->dropIndex('payments_channel_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
        });
    }
};

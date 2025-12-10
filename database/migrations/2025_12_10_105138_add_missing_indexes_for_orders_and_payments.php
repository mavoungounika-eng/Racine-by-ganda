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
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Index sur payment_method pour améliorer les requêtes de filtrage
            // Utilisé notamment dans CleanupAbandonedOrders et les statistiques
            if (!$this->hasIndex('orders', 'orders_payment_method_index')) {
                $table->index('payment_method', 'orders_payment_method_index');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            // Index sur provider pour améliorer les requêtes de filtrage par fournisseur
            if (!$this->hasIndex('payments', 'payments_provider_index')) {
                $table->index('provider', 'payments_provider_index');
            }

            // Index sur channel pour améliorer les requêtes de filtrage par canal
            // Utilisé notamment dans MobileMoneyPaymentController
            if (!$this->hasIndex('payments', 'payments_channel_index')) {
                $table->index('channel', 'payments_channel_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if ($this->hasIndex('orders', 'orders_payment_method_index')) {
                $table->dropIndex('orders_payment_method_index');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if ($this->hasIndex('payments', 'payments_provider_index')) {
                $table->dropIndex('payments_provider_index');
            }

            if ($this->hasIndex('payments', 'payments_channel_index')) {
                $table->dropIndex('payments_channel_index');
            }
        });
    }

    /**
     * Vérifier si un index existe déjà
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $indexName]
        );

        return $result[0]->count > 0;
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Index pour user_id (filtrage fréquent)
            if (!$this->hasIndex('orders', 'orders_user_id_index')) {
                $table->index('user_id', 'orders_user_id_index');
            }
            
            // Index pour status (filtrage fréquent)
            if (!$this->hasIndex('orders', 'orders_status_index')) {
                $table->index('status', 'orders_status_index');
            }
            
            // Index pour payment_status
            if (!$this->hasIndex('orders', 'orders_payment_status_index')) {
                $table->index('payment_status', 'orders_payment_status_index');
            }
            
            // Index composite pour requêtes fréquentes
            if (!$this->hasIndex('orders', 'orders_user_status_index')) {
                $table->index(['user_id', 'status'], 'orders_user_status_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            // Index pour category_id
            if (!$this->hasIndex('products', 'products_category_id_index')) {
                $table->index('category_id', 'products_category_id_index');
            }
            
            // Index pour is_active (filtrage fréquent)
            if (!$this->hasIndex('products', 'products_is_active_index')) {
                $table->index('is_active', 'products_is_active_index');
            }
            
            // Index composite pour recherche
            if (!$this->hasIndex('products', 'products_category_active_index')) {
                $table->index(['category_id', 'is_active'], 'products_category_active_index');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            // Index pour order_id
            if (!$this->hasIndex('payments', 'payments_order_id_index')) {
                $table->index('order_id', 'payments_order_id_index');
            }
            
            // Index pour status
            if (!$this->hasIndex('payments', 'payments_status_index')) {
                $table->index('status', 'payments_status_index');
            }
            
            // Index composite pour statistiques
            if (!$this->hasIndex('payments', 'payments_status_created_index')) {
                $table->index(['status', 'created_at'], 'payments_status_created_index');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Index pour product_id
            if (!$this->hasIndex('order_items', 'order_items_product_id_index')) {
                $table->index('product_id', 'order_items_product_id_index');
            }
            
            // Index pour order_id
            if (!$this->hasIndex('order_items', 'order_items_order_id_index')) {
                $table->index('order_id', 'order_items_order_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_index');
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_payment_status_index');
            $table->dropIndex('orders_user_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_id_index');
            $table->dropIndex('products_is_active_index');
            $table->dropIndex('products_category_active_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_order_id_index');
            $table->dropIndex('payments_status_index');
            $table->dropIndex('payments_status_created_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_product_id_index');
            $table->dropIndex('order_items_order_id_index');
        });
    }

    /**
     * Vérifier si un index existe déjà
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();
            $indexes = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};


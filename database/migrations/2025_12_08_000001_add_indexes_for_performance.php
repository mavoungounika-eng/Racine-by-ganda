<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Workaround SQLite (RBG-P0-002) : SQLite ne supporte pas SHOW INDEX (MySQL only).
     * Utilisation de try-catch pour gérer les erreurs "index already exists" de manière cross-DB.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Index pour user_id (filtrage fréquent)
            // Workaround SQLite (RBG-P0-002) : try-catch au lieu de hasIndex()
            try {
                $table->index('user_id', 'orders_user_id_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
            
            // Index pour status (filtrage fréquent)
            try {
                $table->index('status', 'orders_status_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
            
            // Index pour payment_status
            try {
                $table->index('payment_status', 'orders_payment_status_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
            
            // Index composite pour requêtes fréquentes
            try {
                $table->index(['user_id', 'status'], 'orders_user_status_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            // Index pour category_id
            try {
                $table->index('category_id', 'products_category_id_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
            
            // Index pour is_active (filtrage fréquent)
            try {
                $table->index('is_active', 'products_is_active_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
            
            // Index composite pour recherche
            try {
                $table->index(['category_id', 'is_active'], 'products_category_active_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            // Index pour order_id
            try {
                $table->index('order_id', 'payments_order_id_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
            
            // Index pour status
            try {
                $table->index('status', 'payments_status_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
            
            // Index composite pour statistiques
            try {
                $table->index(['status', 'created_at'], 'payments_status_created_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Index pour product_id
            try {
                $table->index('product_id', 'order_items_product_id_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'Duplicate key name') && 
                    !str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }
            
            // Index pour order_id
            try {
                $table->index('order_id', 'order_items_order_id_index');
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
        Schema::table('orders', function (Blueprint $table) {
            try {
                $table->dropIndex('orders_user_id_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
            try {
                $table->dropIndex('orders_status_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
            try {
                $table->dropIndex('orders_payment_status_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
            try {
                $table->dropIndex('orders_user_status_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            try {
                $table->dropIndex('products_category_id_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
            try {
                $table->dropIndex('products_is_active_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
            try {
                $table->dropIndex('products_category_active_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->dropIndex('payments_order_id_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
            try {
                $table->dropIndex('payments_status_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
            try {
                $table->dropIndex('payments_status_created_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            try {
                $table->dropIndex('order_items_product_id_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
            try {
                $table->dropIndex('order_items_order_id_index');
            } catch (\Exception $e) {
                if (!str_contains($e->getMessage(), 'does not exist') && 
                    !str_contains($e->getMessage(), 'Unknown key')) {
                    throw $e;
                }
            }
        });
    }
};


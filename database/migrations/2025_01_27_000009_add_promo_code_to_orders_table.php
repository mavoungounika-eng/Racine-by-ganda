<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour ajouter les colonnes promo_code_id, discount_amount, shipping_method et shipping_cost à la table orders.
 * 
 * TODO: Vérifier en environnement réel que cette migration a un timestamp
 * postérieur à create_orders_table (2025_11_23_000004). Si ce n'est pas le cas, renommer le fichier
 * pour éviter des problèmes d'ordre d'exécution. Actuellement, cette migration (2025_01_27) est
 * antérieure à create_orders_table (2025_11_23), ce qui peut causer des erreurs dans les tests SQLite.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Si la table 'orders' n'existe pas (cas des tests SQLite ou env incomplet), on ne fait rien
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            // Éviter de recréer la colonne si elle existe déjà
            if (!Schema::hasColumn('orders', 'promo_code_id')) {
                $table->foreignId('promo_code_id')->nullable()->after('total_amount')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('promo_code_id');
            }
            if (!Schema::hasColumn('orders', 'shipping_method')) {
                $table->string('shipping_method')->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('orders', 'shipping_cost')) {
                $table->decimal('shipping_cost', 10, 2)->default(0)->after('shipping_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Si la table 'orders' n'existe pas, on ne fait rien
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_cost')) {
                $table->dropColumn('shipping_cost');
            }
            if (Schema::hasColumn('orders', 'shipping_method')) {
                $table->dropColumn('shipping_method');
            }
            if (Schema::hasColumn('orders', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
            if (Schema::hasColumn('orders', 'promo_code_id')) {
                $table->dropForeign(['promo_code_id']);
                $table->dropColumn('promo_code_id');
            }
        });
    }
};


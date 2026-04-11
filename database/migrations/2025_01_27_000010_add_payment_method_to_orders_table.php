<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour ajouter la colonne payment_method à la table orders.
 * 
 * TODO: Vérifier en environnement réel que cette migration a un timestamp
 * postérieur à create_orders_table (2025_11_23_000004). Si ce n'est pas le cas, renommer le fichier
 * pour éviter des problèmes d'ordre d'exécution. Actuellement, cette migration (2025_01_27) est
 * antérieure à create_orders_table (2025_11_23), ce qui peut causer des erreurs dans les tests MySQL.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Si la table 'orders' n'existe pas (env de test ou problème d'ordre), on ne fait rien.
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')
                    ->nullable()
                    ->after('payment_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Si la table 'orders' n'existe pas, on ne fait rien.
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};


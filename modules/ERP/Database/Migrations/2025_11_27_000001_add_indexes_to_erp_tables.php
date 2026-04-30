<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les index manquants pour améliorer les performances
     */
    public function up(): void
    {
        // Index sur created_at pour les mouvements de stock (filtres par date)
        Schema::table('erp_stock_movements', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['type', 'created_at']);
        });

        // Index sur purchase_date pour les achats (filtres par mois/année)
        Schema::table('erp_purchases', function (Blueprint $table) {
            $table->index('purchase_date');
            $table->index(['status', 'purchase_date']);
        });

        // Index sur created_at pour les stocks
        Schema::table('erp_stocks', function (Blueprint $table) {
            $table->index('created_at');
        });

        // Index sur created_at pour les fournisseurs
        Schema::table('erp_suppliers', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('is_active');
        });

        // Index sur created_at pour les matières premières
        Schema::table('erp_raw_materials', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_stock_movements', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['type', 'created_at']);
        });

        Schema::table('erp_purchases', function (Blueprint $table) {
            $table->dropIndex(['purchase_date']);
            $table->dropIndex(['status', 'purchase_date']);
        });

        Schema::table('erp_stocks', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('erp_suppliers', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('erp_raw_materials', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};


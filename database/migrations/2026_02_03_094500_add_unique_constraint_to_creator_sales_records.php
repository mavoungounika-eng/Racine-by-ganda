<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GOVERNANCE MIGRATION — INVARIANT I5
 * 
 * Enforce: 1 commande créateur = 1 et un seul CreatorSaleRecord
 * Toute tentative de doublon = violation d'invariant
 * 
 * @see SAAS_PUR_INVARIANTS.md
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('creator_sales_records', function (Blueprint $table) {
            $table->unique('order_id', 'creator_sales_records_order_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creator_sales_records', function (Blueprint $table) {
            $table->dropUnique('creator_sales_records_order_id_unique');
        });
    }
};

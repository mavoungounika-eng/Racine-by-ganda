<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * GOVERNANCE MIGRATION — INVARIANT C4
 * 
 * Enforce: CreatorSaleRecord.creator_id must never be NULL
 * This is an analytics table ONLY for creator orders.
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
        // Guard applicatif déjà en place dans CreatorSaleRecord::booted()
        // Cette migration ajoute la contrainte DB comme filet de sécurité
        
        // Note: SQLite ne supporte pas ALTER TABLE ADD CONSTRAINT CHECK
        // En production MySQL/PostgreSQL, utiliser la contrainte CHECK native
        
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE creator_sales_records ADD CONSTRAINT chk_creator_id_not_null CHECK (creator_id IS NOT NULL)');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE creator_sales_records ADD CONSTRAINT chk_creator_id_not_null CHECK (creator_id IS NOT NULL)');
        } else {
            // SQLite: pas de CHECK constraint dynamique, le guard applicatif suffit
            // Le test unitaire vérifie le comportement
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE creator_sales_records DROP CONSTRAINT chk_creator_id_not_null');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE creator_sales_records DROP CONSTRAINT chk_creator_id_not_null');
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Ajouter contrainte UNIQUE sur accounting_entries
 * 
 * OBJECTIF: Garantir au niveau DB qu'aucun doublon (reference_type, reference_id) ne peut exister.
 * C'est le VERROU FINAL contre les doubles écritures comptables.
 * 
 * PRÉ-REQUIS: Exécuter scripts/detect_accounting_duplicates.sql et nettoyer les doublons existants.
 * 
 * @see scripts/detect_accounting_duplicates.sql
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. VÉRIFICATION PRÉ-MIGRATION: Aucun doublon ne doit exister
        $duplicates = DB::table('accounting_entries')
            ->select('reference_type', 'reference_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('reference_type')
            ->whereNotNull('reference_id')
            ->whereNull('deleted_at')
            ->groupBy('reference_type', 'reference_id')
            ->having('cnt', '>', 1)
            ->count();

        if ($duplicates > 0) {
            throw new \RuntimeException(
                "MIGRATION BLOQUÉE: {$duplicates} groupe(s) de doublons détecté(s). " .
                "Exécuter scripts/detect_accounting_duplicates.sql et nettoyer avant migration."
            );
        }

        Log::info('Migration accounting_entries UNIQUE constraint: Aucun doublon détecté, procédure en cours...');

        // 2. Ajouter la contrainte UNIQUE (sauf SQLite qui ne supporte pas ALTER ADD CONSTRAINT)
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('accounting_entries', function (Blueprint $table) {
                // Index UNIQUE partiel: seulement sur les entrées non soft-deleted
                // Note: MySQL ne supporte pas les index partiels, on utilise un index complet
                $table->unique(
                    ['reference_type', 'reference_id'],
                    'uq_accounting_entries_reference'
                );
            });

            Log::info('Migration accounting_entries UNIQUE constraint: Contrainte ajoutée avec succès.');
        } else {
            // Pour SQLite (tests), on crée un trigger de validation
            DB::unprepared('
                CREATE TRIGGER IF NOT EXISTS trg_accounting_entries_unique_reference
                BEFORE INSERT ON accounting_entries
                FOR EACH ROW
                WHEN NEW.reference_type IS NOT NULL AND NEW.reference_id IS NOT NULL
                BEGIN
                    SELECT RAISE(ABORT, "UNIQUE constraint failed: accounting_entries.reference_type, accounting_entries.reference_id")
                    WHERE EXISTS (
                        SELECT 1 FROM accounting_entries 
                        WHERE reference_type = NEW.reference_type 
                          AND reference_id = NEW.reference_id
                          AND deleted_at IS NULL
                    );
                END;
            ');

            Log::info('Migration accounting_entries UNIQUE constraint: Trigger SQLite créé.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('accounting_entries', function (Blueprint $table) {
                $table->dropUnique('uq_accounting_entries_reference');
            });
        } else {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_accounting_entries_unique_reference;');
        }
    }
};

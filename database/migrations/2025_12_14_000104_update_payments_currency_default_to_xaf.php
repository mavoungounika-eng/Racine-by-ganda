<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier que la table existe
        if (!Schema::hasTable('payments')) {
            return;
        }

        // Modifier le default de la colonne currency de XOF à XAF
        // Note: SQLite ne supporte pas ALTER COLUMN, on utilise une approche compatible
        if (DB::getDriverName() === 'sqlite') {
            // SQLite : on ne peut pas modifier le default directement
            // Les nouvelles insertions utiliseront le default du modèle/application (XAF)
            // Les données existantes restent inchangées
            return;
        }

        // MySQL/PostgreSQL : modifier le default
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency')->default('XAF')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency')->default('XOF')->change();
        });
    }
};

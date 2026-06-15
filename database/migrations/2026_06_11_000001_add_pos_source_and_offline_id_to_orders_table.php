<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * POS Connect (Electron / Sanctum) :
     * - source     : canal d'origine de la commande ('pos' pour les ventes caisse, null = web)
     * - offline_id : identifiant client généré hors ligne, garantit l'idempotence du sync
     *                (unique par créateur — deux créateurs peuvent générer le même UUID local)
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 20)->nullable()->index()->after('payment_method');
            $table->string('offline_id', 64)->nullable()->after('source');
            $table->unique(['creator_id', 'offline_id'], 'uq_orders_creator_offline_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('uq_orders_creator_offline_id');
            $table->dropIndex(['source']);
            $table->dropColumn(['offline_id', 'source']);
        });
    }
};

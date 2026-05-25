<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Montant original au moment de la commande — jamais modifié après création
            // Permet d'afficher le montant de référence même si des items sont annulés
            $table->decimal('original_total', 10, 2)->nullable()->after('total_amount');
            // Raison d'annulation : 'global' (commande entière) ou 'partial' (items supprimés avant)
            $table->string('cancellation_type')->nullable()->after('original_total');
        });

        // Populer original_total depuis total_amount pour les commandes existantes
        DB::statement('UPDATE orders SET original_total = total_amount WHERE original_total IS NULL');
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['original_total', 'cancellation_type']);
        });
    }
};

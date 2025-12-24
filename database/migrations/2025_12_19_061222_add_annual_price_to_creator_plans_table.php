<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * V2.1 : Ajout du prix annuel pour les abonnements annuels
     */
    public function up(): void
    {
        Schema::table('creator_plans', function (Blueprint $table) {
            $table->decimal('annual_price', 10, 2)->nullable()->after('price')->comment('Prix annuel du plan (si disponible)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creator_plans', function (Blueprint $table) {
            $table->dropColumn('annual_price');
        });
    }
};

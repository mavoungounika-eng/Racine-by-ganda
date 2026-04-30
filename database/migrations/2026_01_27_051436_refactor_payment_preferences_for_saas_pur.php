<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. SUPPRESSION DU LEGACY MARKETPLACE
        // Sur SQLite (Tests), le dropColumn avec index est instable.
        // On ne le fait que si on n'est pas sur SQLite ou via une commande brute si besoin.
        if (config('database.default') !== 'sqlite') {
            Schema::table('payment_preferences', function (Blueprint $table) {
                $table->dropColumn([
                    'payout_schedule',
                    'minimum_payout_threshold',
                    'mobile_money_verified',
                ]);
            });
        }

        Schema::table('payment_preferences', function (Blueprint $table) {

            // 2. AJOUT DES CHAMPS SAAS PUR (Passerelles Directes)
            // Note: Les clés sont stockées en texte, mais encryptées/décryptées via Eloquent Casts
            $table->text('stripe_secret_key')->nullable();
            $table->text('stripe_publishable_key')->nullable();
            
            $table->string('momo_provider')->nullable(); // orange, mtn, airtel, etc.
            $table->text('momo_api_key')->nullable();
            
            $table->string('payment_connection_status')->default('not_connected'); // connected / not_connected
            $table->timestamp('last_connection_test_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_secret_key',
                'stripe_publishable_key',
                'momo_provider',
                'momo_api_key',
                'payment_connection_status',
                'last_connection_test_at',
            ]);

            $table->string('payout_schedule')->default('monthly');
            $table->integer('minimum_payout_threshold')->default(0);
            $table->boolean('mobile_money_verified')->default(false);
        });
    }
};

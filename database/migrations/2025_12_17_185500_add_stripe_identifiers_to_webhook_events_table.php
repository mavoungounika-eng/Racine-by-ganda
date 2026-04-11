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
        Schema::table('stripe_webhook_events', function (Blueprint $table) {
            // Vérifier si les colonnes existent déjà avant de les ajouter (idempotence)
            if (!Schema::hasColumn('stripe_webhook_events', 'checkout_session_id')) {
                $table->string('checkout_session_id')->nullable()->after('event_type');
            }
            if (!Schema::hasColumn('stripe_webhook_events', 'payment_intent_id')) {
                $table->string('payment_intent_id')->nullable()->after('checkout_session_id');
            }
        });

        // Ajouter les index seulement si les colonnes existent
        Schema::table('stripe_webhook_events', function (Blueprint $table) {
            if (Schema::hasColumn('stripe_webhook_events', 'checkout_session_id')) {
                try {
                    $table->index('checkout_session_id');
                } catch (\Exception $e) {
                    // Index existe déjà, ignorer
                }
            }
            if (Schema::hasColumn('stripe_webhook_events', 'payment_intent_id')) {
                try {
                    $table->index('payment_intent_id');
                } catch (\Exception $e) {
                    // Index existe déjà, ignorer
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stripe_webhook_events', function (Blueprint $table) {
            $table->dropIndex(['checkout_session_id']);
            $table->dropIndex(['payment_intent_id']);
            $table->dropColumn(['checkout_session_id', 'payment_intent_id']);
        });
    }
};

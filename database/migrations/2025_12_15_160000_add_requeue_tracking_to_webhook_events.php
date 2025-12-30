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
        // Stripe webhook events
        Schema::table('stripe_webhook_events', function (Blueprint $table) {
            $table->unsignedInteger('requeue_count')->default(0)->after('dispatched_at');
            $table->timestamp('last_requeue_at')->nullable()->after('requeue_count');
            $table->index('requeue_count');
            $table->index('last_requeue_at');
        });

        // Monetbil callback events
        Schema::table('monetbil_callback_events', function (Blueprint $table) {
            $table->unsignedInteger('requeue_count')->default(0)->after('dispatched_at');
            $table->timestamp('last_requeue_at')->nullable()->after('requeue_count');
            $table->index('requeue_count');
            $table->index('last_requeue_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite nécessite de supprimer les indexes avant les colonnes
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite : drop index par nom explicite
            Schema::table('stripe_webhook_events', function (Blueprint $table) {
                try {
                    $table->dropIndex('stripe_webhook_events_requeue_count_index');
                } catch (\Exception $e) {
                    // Index peut ne pas exister
                }
                try {
                    $table->dropIndex('stripe_webhook_events_last_requeue_at_index');
                } catch (\Exception $e) {
                    // Index peut ne pas exister
                }
                $table->dropColumn(['requeue_count', 'last_requeue_at']);
            });

            Schema::table('monetbil_callback_events', function (Blueprint $table) {
                try {
                    $table->dropIndex('monetbil_callback_events_requeue_count_index');
                } catch (\Exception $e) {
                    // Index peut ne pas exister
                }
                try {
                    $table->dropIndex('monetbil_callback_events_last_requeue_at_index');
                } catch (\Exception $e) {
                    // Index peut ne pas exister
                }
                $table->dropColumn(['requeue_count', 'last_requeue_at']);
            });
        } else {
            // MySQL/Postgres : drop index standard
            Schema::table('stripe_webhook_events', function (Blueprint $table) {
                $table->dropIndex(['requeue_count']);
                $table->dropIndex(['last_requeue_at']);
                $table->dropColumn(['requeue_count', 'last_requeue_at']);
            });

            Schema::table('monetbil_callback_events', function (Blueprint $table) {
                $table->dropIndex(['requeue_count']);
                $table->dropIndex(['last_requeue_at']);
                $table->dropColumn(['requeue_count', 'last_requeue_at']);
            });
        }
    }
};





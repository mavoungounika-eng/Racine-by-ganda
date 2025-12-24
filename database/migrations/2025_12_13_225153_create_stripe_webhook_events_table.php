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
        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique(); // Stripe event ID (evt_...)
            $table->string('event_type'); // checkout.session.completed, payment_intent.succeeded, etc.
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->string('status')->default('received'); // received, processed, ignored, failed
            $table->timestamp('processed_at')->nullable();
            $table->string('payload_hash')->nullable(); // Hash du payload pour vérification optionnelle
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('payment_id');
            $table->index('event_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
    }
};

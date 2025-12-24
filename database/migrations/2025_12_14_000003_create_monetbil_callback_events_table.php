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
        Schema::create('monetbil_callback_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique(); // Hash stable pour idempotence
            $table->string('payment_ref')->nullable(); // Référence paiement
            $table->string('transaction_id')->nullable(); // Transaction ID Monetbil
            $table->string('transaction_uuid')->nullable(); // Transaction UUID Monetbil
            $table->string('event_type')->nullable(); // Type d'événement
            $table->string('status')->default('received'); // received, processed, ignored, failed
            $table->json('payload'); // Payload brut (sera redacted en UI)
            $table->text('error')->nullable(); // Message d'erreur si échec
            $table->timestamp('received_at')->nullable(); // Date de réception
            $table->timestamp('processed_at')->nullable(); // Date de traitement
            $table->timestamps();

            // Indexes pour améliorer les performances
            $table->index('event_key');
            $table->index('status');
            $table->index('received_at');
            $table->index('transaction_id');
            $table->index('payment_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monetbil_callback_events');
    }
};





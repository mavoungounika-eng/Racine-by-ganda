<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Phase 3 : Table pour le monitoring du funnel d'achat
 * 
 * Cette table enregistre les événements clés du tunnel d'achat pour permettre
 * l'analyse des conversions et l'identification des points d'abandon.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('funnel_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // 'product_added_to_cart', 'checkout_started', 'order_placed', 'payment_completed', 'payment_failed'
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->json('metadata')->nullable(); // Données additionnelles (montant, méthode paiement, etc.)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            // Index pour les requêtes d'analyse
            $table->index('event_type');
            $table->index('user_id');
            $table->index('order_id');
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funnel_events');
    }
};

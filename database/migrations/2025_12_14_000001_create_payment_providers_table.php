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
        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // stripe, monetbil
            $table->string('name'); // Stripe, Monetbil
            $table->boolean('is_enabled')->default(true);
            $table->integer('priority')->default(0); // Ordre de priorité
            $table->string('currency', 3)->default('XAF'); // XAF par défaut
            $table->string('health_status')->default('ok'); // ok, degraded, down
            $table->timestamp('last_health_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->string('last_event_status')->nullable(); // ok, failed
            $table->json('meta')->nullable(); // Métadonnées non sensibles
            $table->timestamps();

            // Indexes pour améliorer les performances
            $table->index('code');
            $table->index('is_enabled');
            $table->index('health_status');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_providers');
    }
};





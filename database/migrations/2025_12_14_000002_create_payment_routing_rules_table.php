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
        Schema::create('payment_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('channel'); // card, mobile_money, bank_transfer
            $table->string('currency')->nullable();
            $table->string('country')->nullable();
            $table->foreignId('primary_provider_id')
                ->constrained('payment_providers')
                ->onDelete('restrict'); // FK bigint vers payment_providers.id
            $table->foreignId('fallback_provider_id')
                ->nullable()
                ->constrained('payment_providers')
                ->onDelete('set null'); // FK bigint nullable
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100); // Ordre d'évaluation
            $table->timestamps();

            // Indexes pour améliorer les performances
            $table->index('channel');
            $table->index('currency');
            $table->index('country');
            $table->index('is_active');
            $table->index('priority');
            // Index composite pour recherche rapide (nom court pour éviter erreur MySQL)
            $table->index(['channel', 'currency', 'country', 'is_active', 'priority'], 'idx_routing_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_routing_rules');
    }
};





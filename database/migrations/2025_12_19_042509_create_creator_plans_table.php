<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     * 
     * Crée la table des plans d'abonnement créateur.
     * Plans disponibles: free, official, premium
     */
    public function up(): void
    {
        Schema::create('creator_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Code unique du plan (free, official, premium)');
            $table->string('name')->comment('Nom affiché du plan');
            $table->decimal('price', 10, 2)->default(0)->comment('Prix du plan');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly')->comment('Cycle de facturation');
            $table->boolean('is_active')->default(true)->comment('Plan actif et disponible');
            $table->text('description')->nullable()->comment('Description du plan');
            $table->json('features')->nullable()->comment('Liste des features marketing');
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_plans');
    }
};

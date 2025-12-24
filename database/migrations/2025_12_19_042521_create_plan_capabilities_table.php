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
     * Crée la table de mapping Plan → Capability.
     * Chaque plan a plusieurs capabilities avec leurs valeurs.
     */
    public function up(): void
    {
        Schema::create('plan_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_plan_id')
                ->constrained('creator_plans')
                ->onDelete('cascade')
                ->comment('Référence au plan');
            $table->string('capability_key', 100)->comment('Clé de la capability (ex: can_add_products)');
            $table->json('value')->nullable()->comment('Valeur de la capability (bool, int, string, json)');
            $table->timestamps();
            
            // Index et contraintes
            $table->index('creator_plan_id');
            $table->index('capability_key');
            $table->unique(['creator_plan_id', 'capability_key'], 'plan_capability_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_capabilities');
    }
};

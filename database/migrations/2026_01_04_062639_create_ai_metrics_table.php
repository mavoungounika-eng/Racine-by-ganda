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
        Schema::create('ai_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // product_score, creator_score, churn_risk, etc.
            $table->string('entity_type'); // product, creator, user, order
            $table->unsignedBigInteger('entity_id');
            $table->decimal('value', 10, 2); // Valeur de la métrique
            $table->json('metadata')->nullable(); // Données additionnelles
            $table->date('calculated_for_date'); // Date de référence
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id', 'calculated_for_date']);
            $table->index(['metric_type', 'calculated_for_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_metrics');
    }
};

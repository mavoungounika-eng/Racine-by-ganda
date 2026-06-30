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
        Schema::create('ai_calculation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('module'); // Module qui a effectué le calcul
            $table->string('calculation_type'); // Type de calcul
            $table->json('input_data'); // Données d'entrée
            $table->json('output_data')->nullable(); // Résultat
            $table->decimal('calculation_time', 8, 3); // Temps d'exécution (secondes)
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['module', 'calculated_at']);
            $table->index('success');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_calculation_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Table pour tracker les événements d'abonnement (analytics).
     * Permet de calculer MRR, churn, conversion, etc.
     */
    public function up(): void
    {
        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Créateur concerné');
            $table->string('event', 50)->comment('Type d\'événement: created, upgraded, downgraded, canceled, renewed');
            $table->foreignId('from_plan_id')
                ->nullable()
                ->constrained('creator_plans')
                ->onDelete('set null')
                ->comment('Plan précédent (si changement)');
            $table->foreignId('to_plan_id')
                ->nullable()
                ->constrained('creator_plans')
                ->onDelete('set null')
                ->comment('Plan suivant (si changement)');
            $table->decimal('amount', 10, 2)->nullable()->comment('Montant de l\'abonnement (pour MRR)');
            $table->timestamp('occurred_at')->useCurrent()->comment('Date/heure de l\'événement');
            $table->json('metadata')->nullable()->comment('Métadonnées supplémentaires');
            $table->timestamps();
            
            // Index pour analytics
            $table->index('creator_id');
            $table->index('event');
            $table->index('occurred_at');
            $table->index(['event', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_events');
    }
};

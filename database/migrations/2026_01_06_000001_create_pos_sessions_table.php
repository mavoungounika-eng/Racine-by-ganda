<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * POS Sessions - Session de caisse obligatoire
     * 
     * INVARIANTS:
     * - Une machine ne peut avoir qu'UNE session 'open' à la fois
     * - opening_cash obligatoire à l'ouverture
     * - closing_cash obligatoire pour passer à 'closed'
     */
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('machine_id')->index();
            $table->foreignId('opened_by')->constrained('users');
            $table->timestamp('opened_at');
            $table->decimal('opening_cash', 15, 2)->default(0.00);
            $table->enum('status', ['open', 'closing', 'closed'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index pour recherche rapide
            $table->index('status');
            $table->index('opened_at');
            $table->index(['machine_id', 'status']);
            
            // Contrainte: une seule session ouverte par machine
            // Note: MySQL ne supporte pas les index partiels, on gère via l'application
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};

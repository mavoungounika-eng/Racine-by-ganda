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
        // Note: On n'ajoute pas de colonne "blocked" séparée
        // On utilise le status existant avec une nouvelle valeur "blocked"
        // Les migrations Laravel ne peuvent pas modifier les enums facilement,
        // donc on laisse le status VARCHAR accepter "blocked"
        
        // Aucune modification de schéma nécessaire
        // Le status "blocked" sera géré au niveau applicatif
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Aucune modification de schéma nécessaire
    }
};





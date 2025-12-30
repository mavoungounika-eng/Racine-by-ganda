<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajout des champs pour le système d'authentification multi-rôle :
     * - role : enum('super_admin', 'admin', 'staff', 'createur', 'client')
     * - staff_role : string nullable pour les rôles spécifiques du staff
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Champ role : définit le rôle principal de l'utilisateur
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['super_admin', 'admin', 'staff', 'createur', 'client'])
                    ->default('client')
                    ->after('role_id')
                    ->comment('Rôle principal de l\'utilisateur dans le système');
            }
            
            // Champ staff_role : pour les rôles spécifiques du personnel
            // Ex: 'vendeur', 'caissier', 'gestionnaire_stock', 'comptable', etc.
            if (!Schema::hasColumn('users', 'staff_role')) {
                $table->string('staff_role')
                    ->nullable()
                    ->after('role')
                    ->comment('Rôle spécifique pour les utilisateurs de type staff');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'staff_role']);
        });
    }
};

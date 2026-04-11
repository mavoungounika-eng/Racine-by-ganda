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
        Schema::table('users', function (Blueprint $table) {
            // S'assurer que role_id est unsignedBigInteger nullable
            // (déjà fait dans la migration précédente, mais on vérifie)
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('email');
            }
        });

        // Nettoyer les role_id invalides avant d'ajouter la contrainte
        $validRoleIds = \DB::table('roles')->pluck('id')->toArray();
        if (!empty($validRoleIds)) {
            \DB::table('users')
                ->whereNotNull('role_id')
                ->whereNotIn('role_id', $validRoleIds)
                ->update(['role_id' => null]);
        }

        // Ajouter la contrainte de clé étrangère
        Schema::table('users', function (Blueprint $table) {
            // Vérifier si la contrainte n'existe pas déjà
            $hasForeignKey = false;
            $driver = \DB::getDriverName();
            
            if ($driver === 'pgsql') {
                $hasForeignKey = !empty(\DB::select("
                    SELECT tc.constraint_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu
                      ON tc.constraint_name = kcu.constraint_name
                    WHERE tc.constraint_type = 'FOREIGN KEY'
                      AND tc.table_name = 'users'
                      AND kcu.column_name = 'role_id'
                "));
            } elseif ($driver === 'mysql') {
                $hasForeignKey = !empty(\DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'users' 
                    AND COLUMN_NAME = 'role_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                "));
            }

            if (!$hasForeignKey) {
                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            // Note: On ne supprime pas la colonne role_id car elle existe déjà
            // et pourrait être utilisée ailleurs
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute les colonnes pour l'authentification à deux facteurs (2FA)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Secret Google Authenticator (crypté)
            $table->text('two_factor_secret')->nullable()->after('password');
            
            // Codes de récupération (cryptés, JSON)
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            
            // Date d'activation du 2FA
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            
            // 2FA obligatoire pour ce compte (true pour admin/super_admin)
            $table->boolean('two_factor_required')->default(false)->after('two_factor_confirmed_at');
            
            // Dernier appareil de confiance (optionnel)
            $table->string('trusted_device_token')->nullable()->after('two_factor_required');
            $table->timestamp('trusted_device_expires_at')->nullable()->after('trusted_device_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'two_factor_required',
                'trusted_device_token',
                'trusted_device_expires_at',
            ]);
        });
    }
};


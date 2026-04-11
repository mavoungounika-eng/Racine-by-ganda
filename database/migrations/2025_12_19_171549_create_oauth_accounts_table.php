<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration : Création de la table oauth_accounts
 * 
 * Table pivot pour gérer les comptes OAuth multi-providers (Google, Apple, Facebook)
 * sans dupliquer les colonnes dans la table users.
 * 
 * Module Social Auth v2 - Indépendant du module Google Auth v1
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oauth_accounts', function (Blueprint $table) {
            $table->id();
            
            // Relation vers users
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            // Provider OAuth (google, apple, facebook)
            $table->string('provider', 50);
            
            // ID unique du provider (Google ID, Apple Subject, Facebook ID)
            $table->string('provider_user_id', 255);
            
            // Email du provider (peut être null pour Apple si masqué)
            $table->string('provider_email', 255)->nullable();
            
            // Nom du provider
            $table->string('provider_name', 255)->nullable();
            
            // Tokens OAuth (optionnels, pour futures intégrations API)
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            
            // Compte OAuth principal (un seul par utilisateur)
            $table->boolean('is_primary')->default(false);
            
            // Métadonnées supplémentaires (avatar, locale, etc.)
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Index et contraintes
            // Unicité : un même provider_user_id ne peut être lié qu'à un seul utilisateur
            $table->unique(['provider', 'provider_user_id'], 'unique_provider_user');
            
            // Index pour optimiser les recherches
            $table->index('user_id');
            $table->index('provider');
            $table->index('provider_user_id');
            
            // Note : La contrainte unique_user_primary (un seul is_primary = true par user)
            // n'est pas supportée nativement par MySQL < 8.0
            // Gérée au niveau applicatif dans SocialAuthService
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_accounts');
    }
};

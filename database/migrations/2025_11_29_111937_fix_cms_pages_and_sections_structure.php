<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cette migration corrige la structure des tables cms_pages et cms_sections
     * pour qu'elles correspondent au schéma attendu par le CMS universel (Phase 1 & 2).
     * 
     * Elle est idempotente : peut être exécutée plusieurs fois sans erreur.
     */
    public function up(): void
    {
        // ============================================
        // CORRECTION DE LA TABLE cms_pages
        // ============================================
        if (Schema::hasTable('cms_pages')) {
            Schema::table('cms_pages', function (Blueprint $table) {
                // Colonne 'type' (string, nullable)
                if (!Schema::hasColumn('cms_pages', 'type')) {
                    $table->string('type')->nullable()->after('slug');
                }

                // Colonne 'template' (string, nullable)
                if (!Schema::hasColumn('cms_pages', 'template')) {
                    $table->string('template')->nullable()->after('type');
                }

                // Colonne 'seo_title' (string, nullable)
                // Si 'meta_title' existe (ancien schéma), on crée quand même 'seo_title'
                // Les données pourront être migrées manuellement si nécessaire
                if (!Schema::hasColumn('cms_pages', 'seo_title')) {
                    $table->string('seo_title')->nullable()->after('template');
                }

                // Colonne 'seo_description' (text, nullable)
                // Si 'meta_description' existe (ancien schéma), on crée quand même 'seo_description'
                // Les données pourront être migrées manuellement si nécessaire
                if (!Schema::hasColumn('cms_pages', 'seo_description')) {
                    $table->text('seo_description')->nullable()->after('seo_title');
                }

                // Colonne 'is_published' (boolean, default true)
                // Vérifier si 'status' existe (ancien schéma) et créer 'is_published' en conséquence
                if (!Schema::hasColumn('cms_pages', 'is_published')) {
                    $table->boolean('is_published')->default(true)->after('seo_description');
                    
                    // Si 'status' existe, migrer les données
                    if (Schema::hasColumn('cms_pages', 'status')) {
                        // On laisse la colonne status pour l'instant, on ne la supprime pas
                        // La migration des données se fera via un script séparé si nécessaire
                    }
                }

                // S'assurer que 'slug' est unique (index unique)
                // Note: Laravel ne permet pas de vérifier facilement si un index existe
                // On laisse la contrainte unique être gérée par la migration originale
            });
        }

        // ============================================
        // CORRECTION DE LA TABLE cms_sections
        // ============================================
        if (Schema::hasTable('cms_sections')) {
            Schema::table('cms_sections', function (Blueprint $table) {
                // Colonne 'page_slug' (string, index)
                if (!Schema::hasColumn('cms_sections', 'page_slug')) {
                    $table->string('page_slug')->after('id');
                    $table->index('page_slug');
                }

                // Colonne 'key' (string)
                if (!Schema::hasColumn('cms_sections', 'key')) {
                    $table->string('key')->after('page_slug');
                }

                // Colonne 'type' (string, default 'text')
                if (!Schema::hasColumn('cms_sections', 'type')) {
                    $table->string('type')->default('text')->after('key');
                }

                // Colonne 'data' (json, nullable)
                if (!Schema::hasColumn('cms_sections', 'data')) {
                    $table->json('data')->nullable()->after('type');
                }

                // Colonne 'is_active' (boolean, default true)
                if (!Schema::hasColumn('cms_sections', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('data');
                }

                // Colonne 'order' (integer, default 0)
                if (!Schema::hasColumn('cms_sections', 'order')) {
                    $table->integer('order')->default(0)->after('is_active');
                }

                // Index composite sur (page_slug, key) pour garantir l'unicité
                // Note: On ne peut pas facilement vérifier si un index existe, donc on laisse
                // la migration originale gérer cela
            });
        }
    }

    /**
     * Reverse the migrations.
     * 
     * ATTENTION: Cette méthode ne supprime PAS les colonnes ajoutées
     * pour éviter de perdre des données. Si vous voulez vraiment revenir en arrière,
     * vous devrez le faire manuellement.
     */
    public function down(): void
    {
        // On ne fait rien dans down() pour éviter de perdre des données
        // Si vous voulez vraiment supprimer les colonnes, faites-le manuellement
    }
};

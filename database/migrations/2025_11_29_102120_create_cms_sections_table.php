<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Création de la table cms_sections pour les sections de contenu par page.
     */
    public function up(): void
    {
        // Vérifier si la table existe déjà
        if (Schema::hasTable('cms_sections')) {
            return;
        }

        Schema::create('cms_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page_slug'); // clé logique vers cms_pages.slug
            $table->string('key'); // identifiant logique du bloc (ex: 'hero', 'intro', 'body', 'banner_top')
            $table->string('type')->default('text'); // 'text', 'richtext', 'banner', 'cta', etc.
            $table->json('data')->nullable(); // contenu du bloc (titres, textes, images, boutons...)
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('page_slug');
            $table->index(['page_slug', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_sections');
    }
};

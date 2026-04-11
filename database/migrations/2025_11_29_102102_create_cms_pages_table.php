<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Création de la table cms_pages pour le système CMS universel.
     * Si la table existe déjà (via le module CMS), on vérifie et on adapte.
     */
    public function up(): void
    {
        // Vérifier si la table existe déjà (module CMS)
        if (Schema::hasTable('cms_pages')) {
            // Si elle existe, vérifier si elle a déjà les champs requis
            if (!Schema::hasColumn('cms_pages', 'type')) {
                Schema::table('cms_pages', function (Blueprint $table) {
                    $table->string('type')->nullable()->after('slug');
                    $table->boolean('is_published')->default(true)->after('status');
                });
            }
            return;
        }

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('type')->nullable(); // 'hybrid' ou 'content'
            $table->string('template')->nullable(); // nom du template Blade (ex: 'home', 'shop', 'about')
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};

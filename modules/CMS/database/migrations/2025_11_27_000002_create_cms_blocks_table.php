<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('identifier')->unique(); // hero_home, features_home, etc.
            $table->string('type')->default('html'); // html, json, image, gallery
            $table->string('zone')->default('content'); // header, content, footer, sidebar
            
            // Content
            $table->longText('content')->nullable(); // HTML ou JSON selon le type
            $table->json('settings')->nullable(); // Paramètres additionnels
            
            // Localisation
            $table->string('page_slug')->nullable(); // Si lié à une page spécifique
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            
            // Tracking
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_blocks');
    }
};


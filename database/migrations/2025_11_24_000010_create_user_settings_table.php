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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Mode d'affichage
            $table->enum('display_mode', ['light', 'dark', 'auto'])->default('dark');
            
            // Palette d'accent
            $table->enum('accent_palette', ['orange', 'yellow', 'gold', 'red'])->default('orange');
            
            // Intensité des animations
            $table->enum('animation_intensity', ['none', 'soft', 'standard', 'luxury'])->default('standard');
            
            // Style visuel selon le genre
            $table->enum('visual_style', ['female', 'male', 'neutral'])->default('neutral');
            
            // Contraste & luminosité
            $table->enum('contrast_level', ['normal', 'bright', 'dark'])->default('normal');
            
            // Filtre Golden Light
            $table->boolean('golden_light_filter')->default(false);
            
            $table->timestamps();
            
            // Index pour performance
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};

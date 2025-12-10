<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_banners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position')->default('home_hero'); // home_hero, home_promo, shop_top, etc.
            
            // Contenu
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('image');
            $table->string('image_mobile')->nullable();
            
            // Call to Action
            $table->string('cta_text')->nullable();
            $table->string('cta_link')->nullable();
            $table->string('cta_style')->default('primary'); // primary, secondary, outline
            
            // Planification
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            
            // Stats
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_banners');
    }
};


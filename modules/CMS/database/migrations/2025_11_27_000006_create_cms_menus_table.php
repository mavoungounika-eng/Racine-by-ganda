<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->unique(); // header, footer, sidebar
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        Schema::create('cms_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('cms_menus')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('cms_menu_items')->onDelete('cascade');
            
            $table->string('title');
            $table->string('url')->nullable();
            $table->string('route_name')->nullable();
            $table->json('route_params')->nullable();
            $table->string('icon')->nullable();
            $table->string('target')->default('_self'); // _self, _blank
            $table->string('css_class')->nullable();
            
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_menu_items');
        Schema::dropIfExists('cms_menus');
    }
};


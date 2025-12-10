<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Événements
        if (!Schema::hasTable('cms_events')) {
            Schema::create('cms_events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->longText('content')->nullable();
                $table->string('featured_image')->nullable();
                $table->string('location')->nullable();
                $table->string('address')->nullable();
                $table->datetime('start_date');
                $table->datetime('end_date')->nullable();
                $table->enum('type', ['fashion_show', 'exhibition', 'workshop', 'sale', 'meeting', 'other'])->default('other');
                $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
                $table->integer('capacity')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->boolean('is_free')->default(false);
                $table->boolean('registration_required')->default(false);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        // Portfolio / Réalisations
        if (!Schema::hasTable('cms_portfolio')) {
            Schema::create('cms_portfolio', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->longText('content')->nullable();
                $table->string('featured_image')->nullable();
                $table->json('gallery')->nullable();
                $table->string('category')->nullable();
                $table->string('client')->nullable();
                $table->date('project_date')->nullable();
                $table->json('tags')->nullable();
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // Albums photos
        if (!Schema::hasTable('cms_albums')) {
            Schema::create('cms_albums', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('cover_image')->nullable();
                $table->json('photos')->nullable();
                $table->string('category')->nullable();
                $table->date('album_date')->nullable();
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->integer('order')->default(0);
                $table->boolean('is_featured')->default(false);
                $table->timestamps();
            });
        }

        // Paramètres du site
        if (!Schema::hasTable('cms_settings')) {
            Schema::create('cms_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('text');
                $table->string('group')->default('general');
                $table->string('label')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_settings');
        Schema::dropIfExists('cms_albums');
        Schema::dropIfExists('cms_portfolio');
        Schema::dropIfExists('cms_events');
    }
};


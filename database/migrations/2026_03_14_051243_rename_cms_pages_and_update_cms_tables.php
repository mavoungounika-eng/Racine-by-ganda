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
        // 1. Rename cms_pages to pages if it exists
        if (Schema::hasTable('cms_pages')) {
            Schema::rename('cms_pages', 'pages');
        } elseif (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content')->nullable();
                $table->timestamps();
            });
        }

        // 2. Add/Update columns in pages table
        Schema::table('pages', function (Blueprint $table) {
            if (!Schema::hasColumn('pages', 'content')) {
                $table->longText('content')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('pages', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('content');
            }
            if (!Schema::hasColumn('pages', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('pages', 'status')) {
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->after('meta_description');
            }
            if (!Schema::hasColumn('pages', 'template')) {
                $table->enum('template', ['default', 'full_width', 'sidebar'])->default('default')->after('status');
            }
            if (!Schema::hasColumn('pages', 'show_in_footer')) {
                $table->boolean('show_in_footer')->default(false)->after('template');
            }
            if (!Schema::hasColumn('pages', 'show_in_header')) {
                $table->boolean('show_in_header')->default(false)->after('show_in_footer');
            }
            if (!Schema::hasColumn('pages', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('show_in_header');
            }
            if (!Schema::hasColumn('pages', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('sort_order');
            }
            if (!Schema::hasColumn('pages', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('pages', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('pages', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Drop cms_sections if it exists as we transition to Pages and ContentBlocks
        // BUT the user asked for 301 redirects if old routes exist, 
        // and we might need the data temporarily.
        // Actually, the user's instructions for 1D creates content_blocks which replaces section logic.
        // I will keep cms_sections for now but it's effectively deprecated.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pages')) {
            Schema::rename('pages', 'cms_pages');
        }
    }
};

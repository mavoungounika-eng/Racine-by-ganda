<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        Schema::create('cms_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('cms_faq_categories')->onDelete('set null');
            $table->string('question');
            $table->text('answer');
            $table->integer('order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('helpful_yes')->default(0);
            $table->unsignedInteger('helpful_no')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_faqs');
        Schema::dropIfExists('cms_faq_categories');
    }
};


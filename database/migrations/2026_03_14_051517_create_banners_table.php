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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_path');
            $table->string('mobile_image_path')->nullable();
            $table->string('link_url')->nullable();
            $table->string('link_text')->nullable();
            $table->enum('link_target', ['_self', '_blank'])->default('_self');
            $table->enum('position', ['homepage_hero', 'homepage_promo', 'category_top', 'sidebar'])->default('homepage_hero');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->unsignedInteger('impressions_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};

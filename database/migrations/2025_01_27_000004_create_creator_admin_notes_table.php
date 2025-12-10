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
        Schema::create('creator_admin_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_profile_id')->constrained('creator_profiles')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('note');
            $table->json('tags')->nullable(); // Tags pour catégorisation
            $table->boolean('is_important')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour améliorer les performances
            $table->index('creator_profile_id');
            $table->index('created_by');
            $table->index('is_important');
            $table->index('is_pinned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_admin_notes');
    }
};


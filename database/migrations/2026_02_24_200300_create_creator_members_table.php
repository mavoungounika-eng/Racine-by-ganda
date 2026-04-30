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
        Schema::create('creator_members', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('creator_profile_id')->constrained()->onDelete('cascade');
            
            // Rôles au sein de l'organisation Creator
            // 'owner' (accès total + facturation), 'admin' (gestion membres), 'editor' (produits), 'viewer' (lecture seule)
            $blueprint->string('role')->default('viewer');
            
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamp('joined_at')->useCurrent();
            
            $blueprint->unique(['user_id', 'creator_profile_id']);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_members');
    }
};

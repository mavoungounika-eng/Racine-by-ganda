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
        Schema::create('creator_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_profile_id')->constrained('creator_profiles')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Admin qui a fait l'action
            $table->string('action'); // verified, unverified, status_changed, document_verified, checklist_completed, etc.
            $table->string('action_label'); // Label lisible
            $table->text('description')->nullable();
            $table->json('old_values')->nullable(); // Valeurs avant changement
            $table->json('new_values')->nullable(); // Valeurs après changement
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('creator_profile_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_activity_logs');
    }
};


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
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            
            // Conversation
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            
            // Participant
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Rôle dans la conversation
            $table->string('role')->default('participant'); // sender, recipient, admin, participant
            
            // Dernière lecture
            $table->timestamp('last_read_at')->nullable();
            
            // Nombre de messages non lus
            $table->integer('unread_count')->default(0);
            
            // Archive
            $table->boolean('is_archived')->default(false);
            
            // Notifications
            $table->boolean('notifications_enabled')->default(true);
            
            $table->timestamps();
            
            // Index et contraintes
            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'is_archived']);
            $table->index(['conversation_id', 'last_read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};

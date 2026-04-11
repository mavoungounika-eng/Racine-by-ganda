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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            
            // Conversation
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            
            // Expéditeur
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Contenu
            $table->text('content');
            
            // Type de message
            $table->string('type')->default('text'); // text, system, attachment
            
            // Statut de lecture (pour chaque participant)
            $table->json('read_by')->nullable(); // [user_id => timestamp]
            
            // Message édité
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            
            // Message supprimé (soft delete)
            $table->softDeletes();
            
            $table->timestamps();
            
            // Index pour performances
            $table->index(['conversation_id', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

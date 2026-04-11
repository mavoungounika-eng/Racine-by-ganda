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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Type de notification
            $table->string('type')->default('info'); // info, success, warning, danger, order, stock, system
            
            // Contenu
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable(); // Emoji ou classe d'icône
            
            // Action (optionnel)
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            
            // Données additionnelles (JSON)
            $table->json('data')->nullable();
            
            // Statut
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            $table->timestamps();
            
            // Index pour les requêtes fréquentes
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};


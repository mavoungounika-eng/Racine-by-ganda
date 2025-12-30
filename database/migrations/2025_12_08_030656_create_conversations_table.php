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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            
            // Type de conversation
            $table->string('type')->default('direct'); // direct, order_thread, product_thread
            
            // Sujet de la conversation
            $table->string('subject')->nullable();
            
            // Liens vers commande ou produit (optionnel)
            $table->foreignId('related_order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->foreignId('related_product_id')->nullable()->constrained('products')->onDelete('cascade');
            
            // Créateur de la conversation
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Dernier message (pour tri rapide)
            $table->timestamp('last_message_at')->nullable();
            
            // Statut
            $table->boolean('is_archived')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour performances
            $table->index(['type', 'created_at']);
            $table->index('last_message_at');
            $table->index('related_order_id');
            $table->index('related_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};

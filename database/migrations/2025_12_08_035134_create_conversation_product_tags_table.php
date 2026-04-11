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
        Schema::create('conversation_product_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('tagged_by')->constrained('users')->onDelete('cascade');
            $table->text('note')->nullable(); // Note optionnelle sur le tag
            $table->timestamps();
            
            $table->unique(['conversation_id', 'product_id']); // Un produit ne peut être tagué qu'une fois par conversation
            $table->index('conversation_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_product_tags');
    }
};

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
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // recommendation
            $table->enum('priority', ['high', 'medium', 'low']);
            $table->string('category'); // product, creator, stock, sales
            $table->string('title');
            $table->text('description');
            $table->text('suggested_action');
            $table->json('data'); // Données de support
            $table->string('created_by_module'); // Module source
            $table->enum('status', ['pending', 'accepted', 'rejected', 'executed'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'priority']);
            $table->index('created_by_module');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_recommendations');
    }
};

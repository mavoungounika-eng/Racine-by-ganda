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
        Schema::create('creator_validation_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_profile_id')->constrained('creator_profiles')->onDelete('cascade');
            $table->string('item_key'); // identity_document, registration_certificate, bank_statement, portfolio, etc.
            $table->string('item_label'); // Label lisible
            $table->boolean('is_required')->default(true);
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('order')->default(0); // Ordre d'affichage
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('creator_profile_id');
            $table->index('item_key');
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_validation_checklists');
    }
};


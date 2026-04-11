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
        Schema::create('creator_validation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_profile_id')->constrained('creator_profiles')->onDelete('cascade');
            $table->string('step_key'); // document_review, identity_verification, business_verification, final_approval
            $table->string('step_label'); // Label lisible
            $table->integer('order')->default(0); // Ordre d'exécution
            $table->enum('status', ['pending', 'in_progress', 'approved', 'rejected'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            // Index pour améliorer les performances
            $table->index('creator_profile_id');
            $table->index('step_key');
            $table->index('status');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_validation_steps');
    }
};


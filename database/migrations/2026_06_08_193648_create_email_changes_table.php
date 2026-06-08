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
        Schema::create('email_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('old_email');
            $table->string('new_email');
            $table->string('old_email_token', 64)->unique();
            $table->string('new_email_token', 64)->unique();
            $table->timestamp('old_email_verified_at')->nullable();
            $table->timestamp('new_email_verified_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            // Indexes pour performances
            $table->index(['user_id', 'expires_at']);
            $table->index('old_email_token');
            $table->index('new_email_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_changes');
    }
};

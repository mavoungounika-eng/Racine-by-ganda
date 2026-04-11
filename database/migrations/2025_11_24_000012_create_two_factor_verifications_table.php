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
        Schema::create('two_factor_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Session info
            $table->string('session_token');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            
            // Expiration
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            
            // Attempts
            $table->integer('attempts')->default(0);
            
            // Timestamp
            $table->timestamp('created_at')->useCurrent();
            
            // Index
            $table->index('session_token');
            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_verifications');
    }
};

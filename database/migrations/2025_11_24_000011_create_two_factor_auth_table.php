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
        Schema::create('two_factor_auth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // TOTP Secret (chiffré)
            $table->text('secret');
            
            // Recovery codes (chiffrés)
            $table->json('recovery_codes');
            
            // Status
            $table->boolean('enabled')->default(false);
            $table->timestamp('enabled_at')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Index
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_auth');
    }
};

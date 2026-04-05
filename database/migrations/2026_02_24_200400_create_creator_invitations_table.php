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
        Schema::create('creator_invitations', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('creator_profile_id')->constrained()->onDelete('cascade');
            $blueprint->string('email')->index();
            $blueprint->string('role')->default('viewer');
            $blueprint->string('token', 64)->unique();
            $blueprint->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $blueprint->enum('status', ['pending', 'accepted', 'expired'])->default('pending');
            $blueprint->timestamp('expires_at');
            $blueprint->timestamps();
            
            $blueprint->unique(['creator_profile_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_invitations');
    }
};

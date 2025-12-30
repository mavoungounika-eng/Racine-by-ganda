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
        Schema::create('pos_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('machine_id')->unique();
            $table->string('name');
            $table->text('machine_secret'); // 🔴 OBLIGATOIRE - Clé HMAC unique (chiffrée)
            $table->enum('status', ['active', 'blocked', 'pending', 'maintenance'])->default('pending');
            $table->timestamp('last_sync_at')->nullable();
            $table->string('version')->nullable(); // Version app POS
            $table->json('metadata')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index(['machine_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_devices');
    }
};

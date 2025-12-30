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
        Schema::create('pos_synced_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('machine_id', 36)->index();
            $table->uuid('event_uuid')->index();
            $table->string('event_type', 100);
            $table->integer('version')->default(1); // 🔴 OBLIGATOIRE - Versionnage
            $table->json('payload');
            $table->string('signature', 64)->nullable(); // 🔴 OBLIGATOIRE - HMAC-SHA256
            $table->timestamp('occurred_at');
            $table->timestamp('synced_at');
            $table->timestamps();
            
            // Clé d'idempotence - CRITIQUE pour éviter doublons
            $table->unique(['machine_id', 'event_uuid'], 'idempotence_key');
            $table->index('version');
            $table->index(['machine_id', 'synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_synced_events');
    }
};

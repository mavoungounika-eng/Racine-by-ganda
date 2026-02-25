<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX 1 — POS Offline Queue persistée en DB
 * Remplace le stockage volatile en Cache (Redis) pour éviter la perte de ventes en cas de redémarrage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_offline_queue', function (Blueprint $table) {
            $table->id();
            $table->string('machine_id', 36)->index();
            $table->json('sale_data');
            $table->enum('status', ['pending', 'synced', 'failed'])->default('pending')->index();
            $table->timestamp('queued_at');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_offline_queue');
    }
};

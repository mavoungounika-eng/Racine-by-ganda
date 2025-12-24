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
        Schema::create('payment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade'); // Supprimer les logs si user supprimé
            $table->string('action'); // provider.toggle, provider.update, reprocess, refund
            $table->string('target_type'); // PaymentProvider, PaymentTransaction, StripeWebhookEvent, MonetbilCallbackEvent
            $table->unsignedBigInteger('target_id')->nullable(); // ID de la cible
            $table->json('diff')->nullable(); // Diff avant/après (non sensible)
            $table->text('reason')->nullable(); // Motif (obligatoire pour reprocess/refund)
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes pour améliorer les performances
            $table->index('action');
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_audit_logs');
    }
};





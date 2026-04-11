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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('monetbil'); // monetbil, stripe, etc.
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_ref')->unique(); // Référence unique de la commande
            $table->string('item_ref')->nullable(); // Référence optionnelle de l'item
            $table->string('transaction_id')->nullable()->unique(); // Transaction ID Monetbil
            $table->string('transaction_uuid')->nullable(); // Transaction UUID Monetbil
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('XAF');
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled'])->default('pending');
            $table->string('operator')->nullable(); // Opérateur Mobile Money (MTN, Orange, etc.)
            $table->string('phone')->nullable(); // Numéro de téléphone
            $table->decimal('fee', 10, 2)->nullable(); // Frais de transaction
            $table->json('raw_payload')->nullable(); // Payload brut de la notification
            $table->timestamp('notified_at')->nullable(); // Date de notification
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('payment_ref');
            $table->index('transaction_id');
            $table->index('order_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};

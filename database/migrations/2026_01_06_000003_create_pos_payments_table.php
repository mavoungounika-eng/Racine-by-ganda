<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * POS Payments - Paiements terrain (faits, pas vérité comptable)
     * 
     * RÈGLES CRITIQUES:
     * - CASH: status = 'pending' jusqu'à clôture session
     * - CARD: status = 'pending' jusqu'à callback/confirmation TPE
     * - MOBILE: status = 'pending' jusqu'à callback Monetbil
     */
    public function up(): void
    {
        Schema::create('pos_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('pos_sales');
            $table->enum('method', ['cash', 'card', 'mobile_money']);
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->string('external_reference')->nullable(); // TPE receipt, Monetbil txn_id
            $table->string('provider')->nullable(); // stripe, monetbil, cash
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Index pour performance
            $table->index('pos_sale_id');
            $table->index('status');
            $table->index('method');
            $table->index(['method', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_payments');
    }
};

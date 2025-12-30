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
        Schema::create('order_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('vendor_type', ['brand', 'creator'])->default('creator');
            
            // Montants
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(15.00)->comment('Taux de commission en %');
            $table->decimal('commission_amount', 10, 2)->default(0)->comment('Montant de la commission');
            $table->decimal('vendor_payout', 10, 2)->default(0)->comment('Montant à verser au vendeur');
            
            // Statuts
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payout_status', ['pending', 'processing', 'paid', 'failed'])->default('pending');
            
            // Dates
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('payout_at')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['order_id', 'vendor_id']);
            $table->index('status');
            $table->index('payout_status');
            $table->index(['vendor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_vendors');
    }
};

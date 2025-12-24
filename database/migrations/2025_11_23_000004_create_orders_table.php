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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending'); // pending, paid, shipped, completed, cancelled
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->decimal('total_amount', 10, 2);
            
            // Colonnes promo code et shipping (ajoutées directement pour éviter problème d'ordre de migration)
            // Voir ticket RBG-P0-002 : ces colonnes sont aussi ajoutées par migration 2025_01_27_000009
            // mais cette migration s'exécute avant create_orders_table à cause des timestamps
            $table->foreignId('promo_code_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('shipping_method')->nullable();
            $table->decimal('shipping_cost', 10, 2)->default(0);
            
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->string('customer_address');
            
            // Colonne pour la méthode de paiement
            $table->string('payment_method')->nullable();
            
            // Colonnes ajoutées par d'autres migrations (pour cohérence complète)
            // qr_token et order_number seront ajoutés par migrations ultérieures si nécessaire
            // address_id sera ajouté par migration ultérieure si nécessaire
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

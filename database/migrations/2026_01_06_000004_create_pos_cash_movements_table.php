<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * POS Cash Movements - Mouvements de caisse auditable
     * 
     * Types:
     * - opening: À l'ouverture session
     * - closing: À la clôture session
     * - sale: À chaque vente cash (pending)
     * - refund: À chaque remboursement
     * - adjustment: Écarts expliqués
     */
    public function up(): void
    {
        Schema::create('pos_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('pos_sessions');
            $table->enum('type', ['opening', 'sale', 'refund', 'adjustment', 'closing']);
            $table->decimal('amount', 15, 2);
            $table->enum('direction', ['in', 'out']);
            $table->string('reason', 500)->nullable();
            $table->foreignId('pos_sale_id')->nullable()->constrained('pos_sales');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            
            // Index pour audit et reporting
            $table->index('session_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['session_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_cash_movements');
    }
};

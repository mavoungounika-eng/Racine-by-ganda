<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * POS Sales - Ventes POS liées à une session
     * 
     * INVARIANTS:
     * - session_id obligatoire (pas de vente sans session)
     * - Session doit être 'open' pour créer une vente
     * - uuid pour idempotence côté client
     */
    public function up(): void
    {
        Schema::create('pos_sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Idempotence key
            $table->foreignId('order_id')->constrained('orders');
            $table->uuid('machine_id')->index();
            $table->foreignId('session_id')->constrained('pos_sessions');
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_method', ['cash', 'card', 'mobile_money', 'mixed']);
            $table->enum('status', ['pending', 'finalized', 'cancelled'])->default('pending');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Index pour performance
            $table->index('session_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['machine_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sales');
    }
};

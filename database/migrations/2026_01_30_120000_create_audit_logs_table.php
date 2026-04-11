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
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                
                // Action type (refund_created, stock_adjusted, payment_method_changed, webhook_retried)
                $table->string('action');
                
                // Entity type (Order, Product, Payment, etc.)
                $table->string('entity_type');
                
                // Entity ID (order_id, product_id, etc.)
                $table->unsignedBigInteger('entity_id');
                
                // User who performed the action (null if system)
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                
                // Request IP address
                $table->ipAddress('ip_address')->nullable();
                
                // Request User-Agent
                $table->text('user_agent')->nullable();
                
                // Additional metadata (before/after state, reason, etc.)
                $table->json('metadata')->nullable();
                
                $table->timestamps();
                
                // Indexes for efficient querying
                $table->index(['entity_type', 'entity_id']);
                $table->index('created_at');
                $table->index('user_id');
                $table->index('action');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

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
        if (!Schema::hasTable('webhook_failures')) {
            Schema::create('webhook_failures', function (Blueprint $table) {
                $table->id();
                
                // Provider (stripe, monetbil, etc.)
                $table->string('provider');
                
                // Event type (charge.refunded, success, etc.)
                $table->string('event_type');
                
                // External webhook ID for deduplication (event_id for Stripe, event_key for Monetbil)
                $table->string('external_id')->unique()->index();
                
                // Full webhook payload
                $table->json('payload');
                
                // Error message on failure
                $table->text('error_message')->nullable();
                
                // Retry tracking
                $table->integer('retry_count')->default(0);
                $table->timestamp('last_retry_at')->nullable();
                
                // Status: pending, processed, failed, dead_letter
                $table->string('status')->default('pending')->index();
                
                // Signature for verification (Stripe-Signature or Monetbil signature)
                $table->text('signature')->nullable();
                
                // Additional metadata
                $table->json('metadata')->nullable();
                
                $table->timestamps();
                
                // Indexes for efficient querying
                $table->index(['provider', 'status', 'created_at']);
                $table->index(['status', 'retry_count']);
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_failures');
    }
};

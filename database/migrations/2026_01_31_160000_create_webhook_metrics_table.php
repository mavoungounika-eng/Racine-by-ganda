<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Webhook Metrics Table
     * 
     * Tracks real-time performance metrics for webhooks:
     * - Response times (latency)
     * - Throughput (events processed)
     * - Error rates
     * - Status codes
     * - Provider performance
     */
    public function up(): void
    {
        Schema::create('webhook_metrics', function (Blueprint $table) {
            $table->id();
            
            // Webhook context
            $table->string('provider')->index();  // stripe, monetbil, etc
            $table->string('event_type')->index();  // charge.succeeded, payment.completed, etc
            $table->string('webhook_id')->nullable()->index();  // Reference to specific webhook event
            
            // Performance metrics
            $table->integer('response_time_ms')->unsigned();  // Milliseconds
            $table->integer('retry_count')->unsigned()->default(0);
            $table->string('status_code')->nullable();  // HTTP status or processing status
            
            // Processing details
            $table->string('handler')->nullable();  // Class/method that handled it
            $table->boolean('success')->default(true);
            $table->string('error_message')->nullable();
            
            // Timing information
            $table->dateTime('received_at')->nullable()->useCurrent();  // Defaults to current timestamp
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            
            // Circuit breaker state at time of processing
            $table->string('circuit_state')->nullable();  // closed, open, half_open
            
            // Additional context
            $table->json('tags')->nullable();  // For flexible filtering
            $table->json('context')->nullable();
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['provider', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['success', 'created_at']);
            $table->index(['status_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_metrics');
    }
};

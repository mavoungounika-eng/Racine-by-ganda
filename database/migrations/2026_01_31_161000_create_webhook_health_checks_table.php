<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Webhook Health Checks Table
     * 
     * Tracks periodic health checks for webhook endpoints:
     * - Provider availability
     * - Response time SLAs
     * - Error rate thresholds
     * - Last successful delivery
     */
    public function up(): void
    {
        Schema::create('webhook_health_checks', function (Blueprint $table) {
            $table->id();
            
            // Provider information
            $table->string('provider')->unique()->index();  // stripe, monetbil, etc
            $table->string('endpoint_url')->nullable();  // URL of webhook receiver
            
            // Health metrics
            $table->boolean('is_healthy')->default(true);
            $table->integer('success_rate')->unsigned()->default(100);  // 0-100 percentage
            $table->integer('uptime_percentage')->unsigned()->default(100);  // 0-100
            
            // Performance SLA
            $table->integer('avg_response_time_ms')->unsigned()->default(0);
            $table->integer('p95_response_time_ms')->unsigned()->nullable();
            $table->integer('p99_response_time_ms')->unsigned()->nullable();
            
            // Circuit breaker state
            $table->string('circuit_state')->default('closed');  // closed, open, half_open
            $table->integer('failures_count')->unsigned()->default(0);
            $table->integer('successes_count')->unsigned()->default(0);
            
            // Time windows
            $table->integer('events_last_hour')->unsigned()->default(0);
            $table->integer('events_last_24h')->unsigned()->default(0);
            $table->integer('errors_last_hour')->unsigned()->default(0);
            $table->integer('errors_last_24h')->unsigned()->default(0);
            
            // Last check details
            $table->dateTime('last_checked_at')->nullable();
            $table->dateTime('last_success_at')->nullable();
            $table->dateTime('last_failure_at')->nullable();
            $table->string('last_error_message')->nullable();
            
            // Alerts
            $table->boolean('alert_on_high_latency')->default(true);
            $table->boolean('alert_on_circuit_open')->default(true);
            $table->integer('latency_threshold_ms')->default(5000);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_health_checks');
    }
};

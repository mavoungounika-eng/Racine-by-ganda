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
        Schema::create('circuit_breakers', function (Blueprint $table) {
            $table->id();
            
            // Provider: stripe, monetbil, paypal, etc.
            $table->string('provider')->index();
            
            // State: closed (normal), open (failing), half_open (testing)
            $table->enum('state', ['closed', 'open', 'half_open'])->default('closed')->index();
            
            // Failure tracking
            $table->integer('failure_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('max_failures')->default(5); // Threshold to open circuit
            
            // Last state change timestamp
            $table->timestamp('opened_at')->nullable()->index();
            $table->timestamp('half_opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            // Last error message
            $table->text('last_error')->nullable();
            
            // Metadata: retry delays, backoff info
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Unique: only one circuit breaker per provider
            $table->unique('provider');
            
            // Indexes for common queries
            $table->index(['state', 'opened_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circuit_breakers');
    }
};

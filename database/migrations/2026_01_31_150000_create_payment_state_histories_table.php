<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_state_histories', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->index();
            $table->string('payment_type')->default('payment');
            $table->enum('from_state', ['pending', 'processing', 'completed', 'failed', 'refunded', 'expired'])->nullable();
            $table->enum('to_state', ['pending', 'processing', 'completed', 'failed', 'refunded', 'expired'])->index();
            $table->string('trigger')->nullable();
            $table->json('metadata')->nullable();
            $table->text('reason')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('validation_error')->nullable();
            $table->timestamps();
            
            $table->index(['payment_id', 'to_state']);
            $table->index(['to_state', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_state_histories');
    }
};

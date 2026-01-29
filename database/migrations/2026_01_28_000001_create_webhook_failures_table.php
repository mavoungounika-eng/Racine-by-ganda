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
        Schema::create('webhook_failures', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // stripe, monetbil, paypal
            $table->string('event_type');
            $table->longText('payload'); // JSON payload
            $table->text('error_message');
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamps();

            // Indexes pour recherche rapide
            $table->index(['provider', 'created_at']);
            $table->index(['retry_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_failures');
    }
};

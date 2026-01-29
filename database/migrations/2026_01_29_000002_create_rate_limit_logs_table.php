<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_limit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();           // "throttle:30,1:192.168.1.1"
            $table->string('endpoint')->index();      // "/checkout", "/login", "/2fa/verify"
            $table->string('ip_address', 45)->index(); // IPv4 or IPv6
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->integer('limit');                 // Rate limit threshold
            $table->integer('window_seconds')->default(60);
            $table->integer('requests_in_window')->default(0);
            $table->boolean('was_blocked')->default(false)->index();
            $table->timestamps();

            // Composite indexes for analytics
            $table->index(['endpoint', 'was_blocked', 'created_at']);
            $table->index(['user_id', 'was_blocked', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limit_logs');
    }
};

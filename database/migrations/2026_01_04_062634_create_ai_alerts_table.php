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
        Schema::create('ai_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // alert
            $table->enum('severity', ['critical', 'warning', 'info']);
            $table->string('category'); // threshold, anomaly, performance
            $table->string('title');
            $table->text('message');
            $table->json('data');
            $table->string('triggered_by_module');
            $table->boolean('is_read')->default(false);
            $table->foreignId('read_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('triggered_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['severity', 'is_read']);
            $table->index('triggered_by_module');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_alerts');
    }
};

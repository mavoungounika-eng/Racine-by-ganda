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
        // Table ai_usage_logs
        if (!Schema::hasTable('ai_usage_logs')) {
            Schema::create('ai_usage_logs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()
                    ->constrained()->nullOnDelete();
                $t->enum('feature', [
                    'description','price_suggestion',
                    'sales_analysis','chat','crm_insight',
                    'stock_anomaly','segmentation','admin_summary'
                ]);
                $t->string('model', 50)->default('gpt-4o');
                $t->unsignedInteger('prompt_tokens')->default(0);
                $t->unsignedInteger('completion_tokens')->default(0);
                $t->unsignedInteger('total_tokens')->default(0);
                $t->decimal('cost_usd', 10, 6)->default(0);
                $t->boolean('response_cached')->default(false);
                $t->nullableMorphs('reference');
                $t->timestamp('created_at')->useCurrent();
            });
        }

        // Table ai_conversations
        if (!Schema::hasTable('ai_conversations')) {
            Schema::create('ai_conversations', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->string('title')->nullable();
                $t->enum('context', ['sales','products','general'])
                    ->default('general');
                $t->json('messages')->nullable();
                $t->timestamp('last_message_at')->nullable();
                $t->unsignedInteger('tokens_used')->default(0);
                $t->timestamps();
                $t->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_usage_logs');
    }
};

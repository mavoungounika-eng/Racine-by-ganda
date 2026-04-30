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
        Schema::table('products', function (Blueprint $t) {
            $t->text('ai_description')->nullable()
                ->after('description');
            $t->decimal('ai_price_suggestion', 10, 2)
                ->nullable()->after('price');
            $t->timestamp('ai_last_analyzed_at')
                ->nullable()->after('ai_price_suggestion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->dropColumn(['ai_description', 'ai_price_suggestion', 'ai_last_analyzed_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creator_plans', function (Blueprint $table) {
            $table->integer('products_limit')->default(80)->after('annual_price')
                ->comment('-1 = illimité');
            $table->boolean('has_pos')->default(false)->after('products_limit');
            $table->integer('trial_days')->default(30)->after('has_pos');
            $table->decimal('quarterly_price', 10, 2)->nullable()->after('annual_price')
                ->comment('Prix trimestriel XAF');
        });
    }

    public function down(): void
    {
        Schema::table('creator_plans', function (Blueprint $table) {
            $table->dropColumn(['products_limit', 'has_pos', 'trial_days', 'quarterly_price']);
        });
    }
};

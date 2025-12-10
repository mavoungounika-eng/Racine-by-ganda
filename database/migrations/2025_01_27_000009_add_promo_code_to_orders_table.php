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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'promo_code_id')) {
                $table->foreignId('promo_code_id')->nullable()->after('total_amount')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('promo_code_id');
            }
            if (!Schema::hasColumn('orders', 'shipping_method')) {
                $table->string('shipping_method')->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('orders', 'shipping_cost')) {
                $table->decimal('shipping_cost', 10, 2)->default(0)->after('shipping_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_cost')) {
                $table->dropColumn('shipping_cost');
            }
            if (Schema::hasColumn('orders', 'shipping_method')) {
                $table->dropColumn('shipping_method');
            }
            if (Schema::hasColumn('orders', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
            if (Schema::hasColumn('orders', 'promo_code_id')) {
                $table->dropForeign(['promo_code_id']);
                $table->dropColumn('promo_code_id');
            }
        });
    }
};


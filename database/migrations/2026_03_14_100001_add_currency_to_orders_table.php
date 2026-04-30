<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('XAF')
                ->after('total_amount');
            $table->decimal('amount_eur', 10, 2)->nullable()
                ->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['currency', 'amount_eur']);
        });
    }
};

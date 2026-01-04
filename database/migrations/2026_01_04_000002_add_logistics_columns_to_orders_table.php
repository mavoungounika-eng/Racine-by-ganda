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
            $table->timestamp('expected_delivery_date')->nullable()->after('status');
            $table->timestamp('prepared_at')->nullable()->after('updated_at');
            $table->timestamp('shipped_at')->nullable()->after('prepared_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['expected_delivery_date', 'prepared_at', 'shipped_at']);
        });
    }
};

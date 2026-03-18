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
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->after('uuid');
            $table->unique(['session_id', 'idempotency_key'], 'uq_pos_sales_session_idempotency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropUnique('uq_pos_sales_session_idempotency');
            $table->dropColumn('idempotency_key');
        });
    }
};

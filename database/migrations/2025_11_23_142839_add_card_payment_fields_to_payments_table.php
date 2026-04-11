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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'channel')) {
                $table->string('channel')->default('card')->after('provider');
            }
            if (!Schema::hasColumn('payments', 'customer_phone')) {
                $table->string('customer_phone')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('payments', 'external_reference')) {
                $table->string('external_reference')->nullable()->after('provider_payment_id');
            }
            if (!Schema::hasColumn('payments', 'metadata')) {
                $table->json('metadata')->nullable()->after('payload');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('payments', 'external_reference')) {
                $table->dropColumn('external_reference');
            }
            if (Schema::hasColumn('payments', 'customer_phone')) {
                $table->dropColumn('customer_phone');
            }
            if (Schema::hasColumn('payments', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};

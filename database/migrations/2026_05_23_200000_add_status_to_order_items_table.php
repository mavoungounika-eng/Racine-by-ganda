<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('status', 50)->default('active')->after('quantity');
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->unsignedBigInteger('previous_cancellation_id')->nullable()->after('cancelled_at');
            $table->foreign('previous_cancellation_id')
                  ->references('id')
                  ->on('order_items')
                  ->nullOnDelete();
        });

        // Backfill existing rows
        DB::table('order_items')->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['previous_cancellation_id']);
            $table->dropColumn(['status', 'cancelled_at', 'previous_cancellation_id']);
        });
    }
};

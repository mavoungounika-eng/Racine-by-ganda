<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // pos_sales: add refund columns
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('cancellation_reason');
            $table->unsignedBigInteger('refunded_by')->nullable()->after('refunded_at');
            $table->decimal('refund_amount', 15, 2)->nullable()->after('refunded_by');
            $table->text('refund_reason')->nullable()->after('refund_amount');

            $table->foreign('refunded_by')->references('id')->on('users')->nullOnDelete();
        });

        // pos_payments: add refund external reference
        Schema::table('pos_payments', function (Blueprint $table) {
            $table->string('refund_external_reference', 255)->nullable()->after('metadata');
        });

        // Update status enums to include 'refunded'
        // For SQLite (testing), enum changes are not needed since SQLite doesn't enforce enums
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE pos_sales MODIFY COLUMN status ENUM('pending', 'finalized', 'cancelled', 'refunded') DEFAULT 'pending'");
            DB::statement("ALTER TABLE pos_payments MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'refunded') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropColumn(['refunded_at', 'refunded_by', 'refund_amount', 'refund_reason']);
        });

        Schema::table('pos_payments', function (Blueprint $table) {
            $table->dropColumn('refund_external_reference');
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE pos_sales MODIFY COLUMN status ENUM('pending', 'finalized', 'cancelled') DEFAULT 'pending'");
            DB::statement("ALTER TABLE pos_payments MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending'");
        }
    }
};

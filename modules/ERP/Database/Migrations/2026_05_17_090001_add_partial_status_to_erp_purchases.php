<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL only — SQLite ignores ALTER TABLE on enums
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE erp_purchases MODIFY status ENUM('draft','ordered','received','partial','cancelled') DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Move partial back to ordered before removing the value
            DB::statement("UPDATE erp_purchases SET status = 'ordered' WHERE status = 'partial'");
            DB::statement("ALTER TABLE erp_purchases MODIFY status ENUM('draft','ordered','received','cancelled') DEFAULT 'draft'");
        }
    }
};

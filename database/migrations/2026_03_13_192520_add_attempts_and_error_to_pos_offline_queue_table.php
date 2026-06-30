<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_offline_queue', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_offline_queue', 'attempts')) {
                $table->unsignedInteger('attempts')->default(0)->after('synced_at');
            }
            if (!Schema::hasColumn('pos_offline_queue', 'error_message')) {
                $table->text('error_message')->nullable()->after('attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_offline_queue', function (Blueprint $table) {
            if (Schema::hasColumn('pos_offline_queue', 'error_message')) {
                $table->dropColumn('error_message');
            }
            if (Schema::hasColumn('pos_offline_queue', 'attempts')) {
                $table->dropColumn('attempts');
            }
        });
    }
};

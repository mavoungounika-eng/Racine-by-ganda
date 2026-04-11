<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webhook_failures') && !Schema::hasColumn('webhook_failures', 'status')) {
            Schema::table('webhook_failures', function (Blueprint $table) {
                $table->string('status')->default('pending')->index()->after('last_retry_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('webhook_failures', 'status')) {
            Schema::table('webhook_failures', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};

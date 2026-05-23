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
        Schema::table('carts', function (Blueprint $table) {
            $table->unsignedTinyInteger('reminder_count')->default(0)->after('user_id');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('reminder_count');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['reminder_count', 'last_reminder_sent_at']);
        });
    }
};

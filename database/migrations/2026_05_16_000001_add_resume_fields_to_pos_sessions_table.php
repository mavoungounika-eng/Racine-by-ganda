<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->string('machine_name')->nullable()->after('machine_id');
            $table->json('panier_snapshot')->nullable()->after('notes');
            $table->decimal('total_ventes', 15, 2)->default(0)->after('panier_snapshot');
            $table->unsignedInteger('nombre_tickets')->default(0)->after('total_ventes');
            $table->timestamp('resumed_at')->nullable()->after('nombre_tickets');
            $table->foreignId('resumed_by')->nullable()->constrained('users')->after('resumed_at');
            $table->timestamp('last_activity_at')->nullable()->after('resumed_by');
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropForeign(['resumed_by']);
            $table->dropIndex(['last_activity_at']);
            $table->dropColumn([
                'machine_name', 'panier_snapshot', 'total_ventes',
                'nombre_tickets', 'resumed_at', 'resumed_by', 'last_activity_at',
            ]);
        });
    }
};

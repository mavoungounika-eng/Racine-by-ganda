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
        Schema::create('pos_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('machine_id')->index();
            $table->uuid('event_uuid')->nullable();
            $table->string('action'); // sync_success, sync_rejected, sync_rejected_fraud, conflict_detected
            $table->text('details')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['machine_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sync_logs');
    }
};

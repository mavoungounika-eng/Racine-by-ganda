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
        Schema::create('pos_operator_audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Qui
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Quoi
            $table->string('action'); // SESSION_OPEN, SESSION_CLOSE, SALE_CREATED, etc
            $table->foreignId('pos_session_id')->nullable()->constrained('pos_sessions')->onDelete('cascade');
            
            // Changements
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('notes')->nullable();
            
            // Où
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            // Quand
            $table->dateTime('timestamp')->useCurrent();
            
            // Indexes pour requêtes rapides
            $table->index(['user_id', 'timestamp']);
            $table->index(['pos_session_id', 'timestamp']);
            $table->index(['action', 'timestamp']);
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_operator_audit_logs');
    }
};

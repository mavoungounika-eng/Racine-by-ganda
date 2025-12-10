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
        Schema::table('users', function (Blueprint $table) {
            $table->string('professional_email')->nullable()->after('email');
            $table->boolean('professional_email_verified')->default(false)->after('professional_email');
            $table->timestamp('professional_email_verified_at')->nullable()->after('professional_email_verified');
            $table->json('email_preferences')->nullable()->after('professional_email_verified_at');
            $table->boolean('email_notifications_enabled')->default(true)->after('email_preferences');
            $table->boolean('email_messaging_enabled')->default(false)->after('email_notifications_enabled');
            
            $table->index('professional_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['professional_email']);
            $table->dropColumn([
                'professional_email',
                'professional_email_verified',
                'professional_email_verified_at',
                'email_preferences',
                'email_notifications_enabled',
                'email_messaging_enabled',
            ]);
        });
    }
};

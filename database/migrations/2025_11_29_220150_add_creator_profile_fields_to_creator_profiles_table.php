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
        Schema::table('creator_profiles', function (Blueprint $table) {
            // Champs manquants pour le module créateur complet
            if (!Schema::hasColumn('creator_profiles', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('creator_profiles', 'banner_path')) {
                $table->string('banner_path')->nullable()->after('logo_path');
            }
            if (!Schema::hasColumn('creator_profiles', 'location')) {
                $table->string('location')->nullable()->after('banner_path');
            }
            if (!Schema::hasColumn('creator_profiles', 'instagram_url')) {
                $table->string('instagram_url')->nullable()->after('instagram');
            }
            if (!Schema::hasColumn('creator_profiles', 'tiktok_url')) {
                $table->string('tiktok_url')->nullable()->after('instagram_url');
            }
            if (!Schema::hasColumn('creator_profiles', 'type')) {
                $table->string('type')->nullable()->after('tiktok_url');
            }
            if (!Schema::hasColumn('creator_profiles', 'legal_status')) {
                $table->string('legal_status')->nullable()->after('type');
            }
            if (!Schema::hasColumn('creator_profiles', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('legal_status');
            }
            if (!Schema::hasColumn('creator_profiles', 'payout_method')) {
                $table->enum('payout_method', ['bank', 'mobile_money', 'other'])->nullable()->after('registration_number');
            }
            if (!Schema::hasColumn('creator_profiles', 'payout_details')) {
                $table->text('payout_details')->nullable()->after('payout_method');
            }
            if (!Schema::hasColumn('creator_profiles', 'status')) {
                $table->enum('status', ['pending', 'active', 'suspended'])->default('pending')->after('payout_details');
                $table->index('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'banner_path',
                'location',
                'instagram_url',
                'tiktok_url',
                'type',
                'legal_status',
                'registration_number',
                'payout_method',
                'payout_details',
                'status',
            ]);
        });
    }
};

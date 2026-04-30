<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('creator_subscriptions', function (Blueprint $table) {
            $table->string('payment_provider')->nullable()->default('stripe')
                  ->comment('Provider de paiement: stripe, monetbil, mtn, orange, moov')
                  ->after('stripe_price_id');
        });
    }

    public function down(): void
    {
        Schema::table('creator_subscriptions', function (Blueprint $table) {
            $table->dropColumn('payment_provider');
        });
    }
};

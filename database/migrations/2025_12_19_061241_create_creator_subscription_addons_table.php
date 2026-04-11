<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * V2.2 : Table pivot pour lier les add-ons aux abonnements
     */
    public function up(): void
    {
        Schema::create('creator_subscription_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_subscription_id')
                ->constrained('creator_subscriptions')
                ->onDelete('cascade')
                ->comment('Abonnement concerné');
            $table->foreignId('creator_addon_id')
                ->constrained('creator_addons')
                ->onDelete('cascade')
                ->comment('Add-on activé');
            $table->timestamp('activated_at')->useCurrent()->comment('Date d\'activation');
            $table->timestamp('expires_at')->nullable()->comment('Date d\'expiration (pour add-ons temporaires)');
            $table->json('metadata')->nullable()->comment('Métadonnées supplémentaires');
            $table->timestamps();
            
            $table->unique(['creator_subscription_id', 'creator_addon_id'], 'sub_addon_unique');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_subscription_addons');
    }
};

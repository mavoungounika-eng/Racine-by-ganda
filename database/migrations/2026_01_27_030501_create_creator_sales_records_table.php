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
        Schema::create('creator_sales_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('creator_profiles')->onDelete('cascade');
            $table->decimal('gross_amount', 15, 2);
            $table->string('payment_method'); // Stripe Direct, MoMo Direct, etc.
            $table->string('status'); // completed, refunded, fulfilled
            $table->string('pickup_location')->nullable(); // Localisation POS pour remise
            $table->foreignId('fulfilled_by')->nullable()->constrained('users'); // Staff POS ayant remis le colis
            $table->timestamp('fulfilled_at')->nullable(); // Date de remise effective
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_sales_records');
    }
};

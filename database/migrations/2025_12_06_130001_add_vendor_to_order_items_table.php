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
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('product_id')
                ->constrained('users')->nullOnDelete()
                ->comment('Vendeur du produit (brand ou créateur)');
            
            $table->enum('vendor_type', ['brand', 'creator'])->after('vendor_id')
                ->nullable()
                ->comment('Type de vendeur');
            
            $table->index('vendor_id');
            $table->index(['order_id', 'vendor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['order_id', 'vendor_id']);
            $table->dropColumn(['vendor_id', 'vendor_type']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('product_type', ['brand', 'marketplace'])
                ->default('brand')
                ->after('user_id')
                ->comment('Type de produit: brand (RACINE BY GANDA) ou marketplace (créateur)');
            
            // Index pour améliorer les performances
            $table->index('product_type');
            $table->index(['product_type', 'is_active']);
        });

        // Mettre à jour les produits existants
        $this->updateExistingProducts();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['product_type']);
            $table->dropIndex(['product_type', 'is_active']);
            $table->dropColumn('product_type');
        });
    }

    /**
     * Mettre à jour les produits existants avec le bon product_type
     */
    private function updateExistingProducts(): void
    {
        // Récupérer l'ID de l'utilisateur brand
        $brandUser = DB::table('users')
            ->where('email', 'brand@racinebyganda.com')
            ->first();

        if ($brandUser) {
            // Produits de la marque GANDA (user_id = brand user)
            DB::table('products')
                ->where('user_id', $brandUser->id)
                ->update(['product_type' => 'brand']);

            // Produits créateurs (user_id != brand user et user_id NOT NULL)
            DB::table('products')
                ->where('user_id', '!=', $brandUser->id)
                ->whereNotNull('user_id')
                ->update(['product_type' => 'marketplace']);

            // Produits orphelins (user_id = NULL) → attribuer à brand
            DB::table('products')
                ->whereNull('user_id')
                ->update([
                    'user_id' => $brandUser->id,
                    'product_type' => 'brand'
                ]);
        } else {
            // Si pas d'utilisateur brand, tous les produits avec user_id NULL restent brand
            // Les autres sont marketplace
            DB::table('products')
                ->whereNotNull('user_id')
                ->update(['product_type' => 'marketplace']);
        }
    }
};

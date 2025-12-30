<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductCodeService;
use Illuminate\Console\Command;

class GenerateProductCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:generate-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère des SKU et code-barres pour tous les produits qui n\'en ont pas encore';

    /**
     * Execute the console command.
     */
    public function handle(ProductCodeService $productCodeService): int
    {
        // Trouver les produits sans ErpProductDetail
        $productsWithoutCodes = Product::whereDoesntHave('erpDetails')->get();
        $count = $productsWithoutCodes->count();

        if ($count === 0) {
            $this->info('Aucun produit à mettre à jour.');
            return self::SUCCESS;
        }

        $this->info("Génération de codes pour {$count} produit(s)...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($productsWithoutCodes as $product) {
            try {
                $productCodeService->createOrUpdateProductDetails($product->id);
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Erreur pour le produit #{$product->id}: " . $e->getMessage());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ {$count} produit(s) mis à jour avec succès.");

        return self::SUCCESS;
    }
}

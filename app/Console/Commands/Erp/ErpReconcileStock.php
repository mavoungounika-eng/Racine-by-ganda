<?php

namespace App\Console\Commands\Erp;

use App\Models\Product;
use Modules\ERP\Models\ErpStockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ErpReconcileStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:reconcile-stock {--fix : Tentative de correction automatique du stock physique}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie la cohérence entre le stock physique et les mouvements ERP (audit de drift)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $products = Product::all();
        $this->info("Analyse de " . $products->count() . " produits...");
        
        $drifts = 0;
        $results = [];

        foreach ($products as $product) {
            // Calculer le stock théorique à partir des mouvements
            $totalIn = ErpStockMovement::where('stockable_id', $product->id)
                ->where('stockable_type', Product::class)
                ->where('type', 'in')
                ->sum('quantity');

            $totalOut = ErpStockMovement::where('stockable_id', $product->id)
                ->where('stockable_type', Product::class)
                ->where('type', 'out')
                ->sum('quantity');

            $expectedStock = $totalIn - $totalOut;
            $actualStock = $product->stock;

            if ((float)$expectedStock !== (float)$actualStock) {
                $drifts++;
                $diff = $actualStock - $expectedStock;
                
                $results[] = [
                    'id' => $product->id,
                    'sku' => $product->sku ?? $product->id,
                    'title' => substr($product->title, 0, 20),
                    'expected' => $expectedStock,
                    'actual' => $actualStock,
                    'diff' => $diff,
                ];
            }
        }

        if ($drifts > 0) {
            $this->warn("⚠️  $drifts anomalies de stock détectées !");
            $this->table(['ID', 'SKU', 'Titre', 'Théorique', 'Réel', 'Diff'], $results);

            if ($this->option('fix')) {
                if ($this->confirm('Voulez-vous synchroniser le stock physique sur le stock théorique calculé ?')) {
                    foreach ($results as $res) {
                        Product::where('id', $res['id'])->update(['stock' => $res['expected']]);
                    }
                    $this->info("✅ Correction terminée.");
                }
            } else {
                $this->info("Utilisez --fix pour synchroniser le stock physique.");
            }
        } else {
            $this->info("✅ Aucun drift détecté. Les stocks sont 100% cohérents avec les mouvements.");
        }

        return $drifts > 0 ? 1 : 0;
    }
}

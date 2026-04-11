<?php

namespace Modules\POSSync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\POSSync\Events\SaleFinalized;

class ProcessPosSale implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $payload
    ) {}

    /**
     * Traiter l'événement PosSaleCreated
     */
    public function handle(): void
    {
        Log::info('Processing POS sale', ['payload' => $this->payload]);

        // Validation métier complète
        $this->validateSale($this->payload);
        
        // Émettre SaleFinalized si validation OK
        event(new SaleFinalized($this->payload));
        
        Log::info('POS sale finalized', ['sale_id' => $this->payload['sale_id'] ?? 'unknown']);
    }

    /**
     * Validation métier de la vente POS
     */
    private function validateSale(array $payload): void
    {
        // 1. Vérifier que les produits existent
        $productIds = collect($payload['items'] ?? [])->pluck('product_id')->unique()->toArray();
        $existingProducts = \App\Models\Product::whereIn('id', $productIds)->pluck('id')->toArray();
        if (count($existingProducts) !== count($productIds)) {
            $missing = array_diff($productIds, $existingProducts);
            throw new \InvalidArgumentException('Produits inexistants: ' . implode(', ', $missing));
        }

        // 2. Vérifier que les prix sont cohérents
        $totalCalculated = 0;
        foreach ($payload['items'] ?? [] as $item) {
            $product = \App\Models\Product::find($item['product_id']);
            $expectedPrice = $product->price;
            $actualPrice = $item['price'] ?? $expectedPrice; // Utilise prix fourni ou DB
            
            if (abs($actualPrice - $expectedPrice) > 0.01) { // Tolérance 1 centime
                throw new \InvalidArgumentException(
                    "Prix incohérent pour produit {$item['product_id']}: attendu {$expectedPrice}, reçu {$actualPrice}"
                );
            }
            
            $totalCalculated += $actualPrice * ($item['quantity'] ?? 1);
        }

        // 3. Vérifier que le paiement est complet
        $totalAmount = $payload['total_amount'] ?? 0;
        if (abs($totalCalculated - $totalAmount) > 0.01) {
            throw new \InvalidArgumentException(
                "Total incohérent: calculé {$totalCalculated}, reçu {$totalAmount}"
            );
        }

        Log::info('POS sale validation passed', [
            'sale_id' => $payload['sale_id'] ?? 'unknown',
            'total_calculated' => $totalCalculated,
            'total_amount' => $totalAmount
        ]);
    }

    /**
     * Gérer l'échec du job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPosSale job failed', [
            'payload' => $this->payload,
            'error' => $exception->getMessage()
        ]);
    }
}

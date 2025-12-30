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

        // TODO: Validation métier complète
        // 1. Vérifier que les produits existent
        // 2. Vérifier que les prix sont cohérents
        // 3. Vérifier que le paiement est complet
        
        // Pour l'instant, émettre directement SaleFinalized
        // (La logique métier complète sera ajoutée dans les sprints suivants)
        
        event(new SaleFinalized($this->payload));
        
        Log::info('POS sale finalized', ['sale_id' => $this->payload['sale_id'] ?? 'unknown']);
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

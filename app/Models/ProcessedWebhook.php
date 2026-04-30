<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * Ce modèle garde une trace éternelle (ou longue) des webhooks déjà traités
 * pour garantir le "Exactly-Once delivery" (Deduplication robuste).
 */
class ProcessedWebhook extends Model
{
    use Prunable;

    protected $fillable = [
        'provider',
        'external_id',
    ];

    /**
     * Purge automatisée des très vieux logs de webhooks via commande console.
     * Conserve l'historique pendant 60 jours pour éviter de repasser un paiement très en retard.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(60));
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CreatorSaleRecord;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des événements de commande créateurs (SaaS).
 * 
 * Gère le cycle de vie analytique de la commande, notamment la remise au POS.
 */
class CreatorOrderEventService
{
    /**
     * Enregistre l'événement de remise de commande via le POS (Analytical Fulfillment).
     * Appelée par le POS lors de la validation physique.
     */
    public function recordFulfilledByPos(Order $order, int $staffId, string $location = 'Boutique Physique RACINE'): CreatorSaleRecord
    {
        // RACINE n'encaisse rien, on valide juste la remise physique.
        
        $record = CreatorSaleRecord::updateOrCreate(
            ['order_id' => $order->id],
            [
                'creator_id' => $order->creator_id,
                'gross_amount' => $order->total_amount,
                'payment_method' => $order->payment_method ?? 'unknown',
                'status' => 'fulfilled',
                'pickup_location' => $location,
                'fulfilled_by' => $staffId,
                'fulfilled_at' => now(),
            ]
        );

        Log::info("POS SaaS: Commande #{$order->id} remise au client à {$location} par Staff #{$staffId}");

        return $record;
    }
}

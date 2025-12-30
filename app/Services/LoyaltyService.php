<?php

namespace App\Services;

use App\Models\Order;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyTransaction;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Attribuer des points après un paiement réussi.
     */
    public function awardPointsForOrder(Order $order): void
    {
        if (!$order->user_id || $order->payment_status !== 'paid') {
            return;
        }

        // 1% du montant en points (1 FCFA = 1 point)
        $points = (int) ($order->total_amount * 0.01);

        if ($points <= 0) {
            return;
        }

        DB::transaction(function () use ($order, $points) {
            // Créer ou mettre à jour les points de l'utilisateur
            $loyaltyPoint = LoyaltyPoint::firstOrCreate(
                ['user_id' => $order->user_id],
                ['points' => 0, 'total_earned' => 0, 'total_spent' => 0, 'tier' => 'bronze']
            );

            $loyaltyPoint->increment('points', $points);
            $loyaltyPoint->increment('total_earned', $points);
            $loyaltyPoint->updateTier();

            // Créer la transaction
            LoyaltyTransaction::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'points' => $points,
                'type' => 'earned',
                'description' => "Points gagnés pour la commande #{$order->id}",
            ]);
        });
    }

    /**
     * Utiliser des points pour une réduction.
     */
    public function usePoints(int $userId, int $points, ?int $orderId = null): bool
    {
        $loyaltyPoint = LoyaltyPoint::where('user_id', $userId)->first();

        if (!$loyaltyPoint || $loyaltyPoint->points < $points) {
            return false;
        }

        DB::transaction(function () use ($loyaltyPoint, $points, $orderId, $userId) {
            $loyaltyPoint->decrement('points', $points);
            $loyaltyPoint->increment('total_spent', $points);

            LoyaltyTransaction::create([
                'user_id' => $userId,
                'order_id' => $orderId,
                'points' => -$points,
                'type' => 'spent',
                'description' => "Points utilisés" . ($orderId ? " pour la commande #{$orderId}" : ''),
            ]);
        });

        return true;
    }

    /**
     * Obtenir les points disponibles d'un utilisateur.
     */
    public function getAvailablePoints(int $userId): int
    {
        $loyaltyPoint = LoyaltyPoint::where('user_id', $userId)->first();
        return $loyaltyPoint ? $loyaltyPoint->points : 0;
    }
}


<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\ERP\Services\StockService;

/**
 * Job pour nettoyer les commandes abandonnées (payment_status='pending' depuis trop longtemps)
 * 
 * CRITÈRES DE NETTOYAGE :
 * - cash_on_delivery : > 7 jours (le client peut encore payer à la livraison)
 * - card : > 24 heures (webhook Stripe devrait arriver rapidement)
 * - mobile_money : > 48 heures (géré aussi par CleanupPendingMobileMoneyPayments mais on nettoie la commande ici)
 * 
 * ACTIONS :
 * - Marque la commande comme 'cancelled'
 * - Si le stock a été décrémenté (cash_on_delivery), réintègre le stock
 * - Log les actions pour traçabilité
 */
class CleanupAbandonedOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Délais en heures/jours selon la méthode de paiement
     */
    protected const CASH_ON_DELIVERY_TIMEOUT_DAYS = 7;
    protected const CARD_TIMEOUT_HOURS = 24;
    protected const MOBILE_MONEY_TIMEOUT_HOURS = 48;

    /**
     * Exécuter le job
     */
    public function handle(): void
    {
        $stats = [
            'cash_on_delivery' => 0,
            'card' => 0,
            'mobile_money' => 0,
            'total' => 0,
        ];

        // 1) Nettoyer les commandes cash_on_delivery abandonnées (> 7 jours)
        $cashThreshold = now()->subDays(self::CASH_ON_DELIVERY_TIMEOUT_DAYS);
        $cashOrders = Order::where('payment_method', 'cash_on_delivery')
            ->where('payment_status', 'pending')
            ->where('status', 'pending')
            ->where('created_at', '<', $cashThreshold)
            ->get();

        foreach ($cashOrders as $order) {
            try {
                $this->cancelAbandonedOrder($order, 'cash_on_delivery');
                $stats['cash_on_delivery']++;
                $stats['total']++;
            } catch (\Exception $e) {
                Log::error('Error cleaning up abandoned cash on delivery order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2) Nettoyer les commandes card abandonnées (> 24 heures)
        $cardThreshold = now()->subHours(self::CARD_TIMEOUT_HOURS);
        $cardOrders = Order::where('payment_method', 'card')
            ->where('payment_status', 'pending')
            ->where('status', 'pending')
            ->where('created_at', '<', $cardThreshold)
            ->get();

        foreach ($cardOrders as $order) {
            try {
                $this->cancelAbandonedOrder($order, 'card');
                $stats['card']++;
                $stats['total']++;
            } catch (\Exception $e) {
                Log::error('Error cleaning up abandoned card order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3) Nettoyer les commandes mobile_money abandonnées (> 48 heures)
        $mobileMoneyThreshold = now()->subHours(self::MOBILE_MONEY_TIMEOUT_HOURS);
        $mobileMoneyOrders = Order::where('payment_method', 'mobile_money')
            ->where('payment_status', 'pending')
            ->where('status', 'pending')
            ->where('created_at', '<', $mobileMoneyThreshold)
            ->get();

        foreach ($mobileMoneyOrders as $order) {
            try {
                $this->cancelAbandonedOrder($order, 'mobile_money');
                $stats['mobile_money']++;
                $stats['total']++;
            } catch (\Exception $e) {
                Log::error('Error cleaning up abandoned mobile money order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log récapitulatif
        if ($stats['total'] > 0) {
            Log::info('CleanupAbandonedOrders completed', [
                'stats' => $stats,
                'cash_on_delivery_timeout' => self::CASH_ON_DELIVERY_TIMEOUT_DAYS . ' days',
                'card_timeout' => self::CARD_TIMEOUT_HOURS . ' hours',
                'mobile_money_timeout' => self::MOBILE_MONEY_TIMEOUT_HOURS . ' hours',
            ]);
        }
    }

    /**
     * Annuler une commande abandonnée et réintégrer le stock si nécessaire
     * 
     * @param Order $order
     * @param string $paymentMethod
     * @return void
     */
    protected function cancelAbandonedOrder(Order $order, string $paymentMethod): void
    {
        // Charger les relations nécessaires
        $order->load('items');

        // Pour cash_on_delivery, le stock a été décrémenté à la création
        // Il faut le réintégrer lors de l'annulation
        if ($paymentMethod === 'cash_on_delivery') {
            try {
                $stockService = app(StockService::class);
                $stockService->restockFromOrder($order);
                Log::info('Stock restored for abandoned cash on delivery order', [
                    'order_id' => $order->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to restore stock for abandoned cash on delivery order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                // On continue quand même pour annuler la commande
            }
        }

        // Marquer la commande comme annulée
        $order->update([
            'status' => 'cancelled',
            // On garde payment_status='pending' pour traçabilité
            // Mais on peut ajouter un champ metadata si besoin
        ]);

        Log::info('Abandoned order cancelled', [
            'order_id' => $order->id,
            'payment_method' => $paymentMethod,
            'created_at' => $order->created_at,
            'age_days' => $order->created_at->diffInDays(now()),
        ]);
    }
}


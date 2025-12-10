<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusUpdateMail;
use App\Services\DashboardCacheService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    protected NotificationService $notificationService;
    protected DashboardCacheService $cacheService;

    public function __construct(NotificationService $notificationService, DashboardCacheService $cacheService)
    {
        $this->notificationService = $notificationService;
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Order "created" event.
     * 
     * LOGIQUE DÉCRÉMENT STOCK :
     * - Pour cash_on_delivery : Décrémente le stock immédiatement à la création de la commande
     *   (car le paiement se fera à la livraison, donc payment_status restera 'pending')
     * - Pour card/mobile_money : Le stock sera décrémenté dans handlePaymentStatusChange()
     *   quand payment_status passera à 'paid' (via webhook ou callback)
     */
    public function created(Order $order): void
    {
        // DÉCRÉMENTER LE STOCK IMMÉDIATEMENT POUR CASH ON DELIVERY
        // Car le paiement se fera à la livraison, donc payment_status restera 'pending'
        // et le stock ne serait jamais décrémenté dans handlePaymentStatusChange()
        if ($order->payment_method === 'cash_on_delivery') {
            try {
                $stockService = app(\Modules\ERP\Services\StockService::class);
                $stockService->decrementFromOrder($order);
                \Log::info("Stock decremented immediately for cash on delivery Order #{$order->id}");
            } catch (\Throwable $e) {
                \Log::error('Stock decrement failed for cash on delivery order', [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // On continue même si décrément échoue (notification, email, etc.)
            }
        }

        // Envoyer email de confirmation
        if ($order->customer_email) {
            try {
                Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
            } catch (\Exception $e) {
                \Log::error('Failed to send order confirmation email', [
                    'order_id' => $order->id,
                    'email' => $order->customer_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notifier le client
        if ($order->user_id) {
            $this->notificationService->order(
                $order->user_id,
                'Commande confirmée !',
                "Votre commande #{$order->id} a été confirmée. Nous la préparons avec soin.",
                $order->id
            );
        }

        // Notifier l'équipe (staff & admin)
        $this->notificationService->broadcastToTeam(
            'Nouvelle commande !',
            "Commande #{$order->id} - " . number_format($order->total_amount, 0, ',', ' ') . " FCFA",
            'order'
        );

        // Invalider le cache du dashboard
        $this->cacheService->clearAfterOrder();
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Vérifier si le statut a changé
        if ($order->isDirty('status')) {
            $this->handleStatusChange($order);
        }

        // Vérifier si le statut de paiement a changé
        if ($order->isDirty('payment_status')) {
            $this->handlePaymentStatusChange($order);
        }

        // Invalider le cache si statut ou paiement a changé
        if ($order->isDirty('status') || $order->isDirty('payment_status')) {
            $this->cacheService->clearAfterOrder();
        }
    }

    /**
     * Gérer le changement de statut de commande
     */
    protected function handleStatusChange(Order $order): void
    {
        if (!$order->customer_email) return;

        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;

        // Réintégrer le stock si la commande est annulée APRÈS paiement
        if ($order->status === 'cancelled' && $order->payment_status === 'paid') {
            $stockService = app(\Modules\ERP\Services\StockService::class);
            $stockService->restockFromOrder($order);
        }

        // Envoyer email de mise à jour de statut
        if ($oldStatus !== $newStatus && in_array($newStatus, ['processing', 'shipped', 'completed', 'cancelled'])) {
            try {
                Mail::to($order->customer_email)->send(new OrderStatusUpdateMail($order, $oldStatus, $newStatus));
            } catch (\Exception $e) {
                \Log::error('Failed to send order status update email', [
                    'order_id' => $order->id,
                    'email' => $order->customer_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $statusMessages = [
            'processing' => [
                'title' => 'Commande en préparation',
                'message' => "Votre commande #{$order->id} est en cours de préparation.",
            ],
            'shipped' => [
                'title' => 'Commande expédiée ! 🚚',
                'message' => "Votre commande #{$order->id} a été expédiée. Elle arrivera bientôt !",
            ],
            'completed' => [
                'title' => 'Commande livrée ! ✅',
                'message' => "Votre commande #{$order->id} a été livrée. Merci pour votre confiance !",
            ],
            'cancelled' => [
                'title' => 'Commande annulée',
                'message' => "Votre commande #{$order->id} a été annulée. Contactez-nous si besoin.",
            ],
        ];

        $status = $order->status;
        
        if (isset($statusMessages[$status]) && $order->user_id) {
            $this->notificationService->order(
                $order->user_id,
                $statusMessages[$status]['title'],
                $statusMessages[$status]['message'],
                $order->id
            );
        }
    }

    /**
     * Gérer le changement de statut de paiement
     * 
     * LOGIQUE DÉCRÉMENT STOCK :
     * - Pour card/mobile_money : Décrémente le stock quand payment_status passe à 'paid'
     *   (via webhook Stripe ou callback Mobile Money)
     * - Pour cash_on_delivery : Le stock a déjà été décrémenté dans created()
     *   (protection double décrément via StockService)
     */
    protected function handlePaymentStatusChange(Order $order): void
    {
        if (!$order->user_id) return;

        if ($order->payment_status === 'paid') {
            // Décrémenter le stock pour les paiements card/mobile_money
            // Pour cash_on_delivery, le stock a déjà été décrémenté dans created()
            // StockService vérifie automatiquement si un mouvement existe déjà (protection double décrément)
            try {
                $stockService = app(\Modules\ERP\Services\StockService::class);
                $stockService->decrementFromOrder($order);
            } catch (\Throwable $e) {
                \Log::error('Stock decrement failed for order', [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'payment_method' => $order->payment_method,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // TODO: Notifier l'admin ou mettre un flag sur la commande pour investigation
                // Pour l'instant, on continue le processus (points fidélité, notification) même si décrément échoue
            }

            // Attribuer des points de fidélité
            try {
                $loyaltyService = app(\App\Services\LoyaltyService::class);
                $loyaltyService->awardPointsForOrder($order);
            } catch (\Throwable $e) {
                \Log::error('Loyalty points award failed for order', [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'error' => $e->getMessage(),
                ]);
                // On continue même si attribution points échoue
            }

            $this->notificationService->success(
                $order->user_id,
                'Paiement reçu !',
                "Le paiement de votre commande #{$order->id} a été confirmé. Merci !"
            );

            // Invalider le cache après paiement
            $this->cacheService->clearAfterPayment();
        } elseif ($order->payment_status === 'failed') {
            $this->notificationService->danger(
                $order->user_id,
                'Échec du paiement',
                "Le paiement de votre commande #{$order->id} a échoué. Veuillez réessayer."
            );
        }
    }
}


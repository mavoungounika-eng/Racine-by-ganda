<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusUpdateMail;
use App\Services\DashboardCacheService;
use App\Services\NotificationService;
use App\Services\StockReservationService;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    protected NotificationService $notificationService;
    protected DashboardCacheService $cacheService;
    protected StockReservationService $stockReservationService;

    public function __construct(
        NotificationService $notificationService,
        DashboardCacheService $cacheService,
        StockReservationService $stockReservationService
    ) {
        $this->notificationService = $notificationService;
        $this->cacheService = $cacheService;
        $this->stockReservationService = $stockReservationService;
    }

    /**
     * Handle the Order "created" event.
     * 
     * ✅ CORRECTION 5 (Option B) : LOGIQUE DÉCRÉMENT STOCK UNIFIÉE
     * - Pour TOUS les types de paiement : Décrémente le stock immédiatement à la création
     * - Si paiement échoue : Rollback stock via webhook/callback
     * - Si paiement réussit : Stock déjà décrémenté (pas de double décrément grâce à protection)
     */
    public function created(Order $order): void
    {
        // ✅ CORRECTION 5 : DÉCRÉMENTER LE STOCK IMMÉDIATEMENT POUR TOUS LES TYPES DE PAIEMENT
        // Stratégie : Décrément immédiat + rollback si paiement échoue
        try {
            // S'assurer que les items sont chargés avant décrément
            if (!$order->relationLoaded('items')) {
                $order->load('items');
            }
            $stockService = app(\Modules\ERP\Services\StockService::class);
            $stockService->decrementFromOrder($order);
            \Log::info("Stock decremented immediately for Order #{$order->id} (payment_method: {$order->payment_method})");
        } catch (\Throwable $e) {
            \Log::error('Stock decrement failed for order', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'payment_method' => $order->payment_method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // On continue même si décrément échoue (notification, email, etc.)
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

        // ✅ CORRECTION 7 : Ignorer si Order est déjà dans un état terminal
        // (protection contre modification d'état terminal)
        if ($order->isTerminal() && $oldStatus !== $newStatus) {
            \Log::warning('OrderObserver: Attempt to change status of terminal order', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
            return;
        }

        // Réintégrer le stock si la commande est annulée APRÈS paiement
        if ($order->status === 'cancelled' && $order->payment_status === 'paid') {
            $stockService = app(\Modules\ERP\Services\StockService::class);
            $stockService->restockFromOrder($order);
            
            // ✅ Libérer la réservation stock
            $this->releaseStockReservation($order);
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
     * ✅ CORRECTION 5 (Option B) : LOGIQUE STOCK UNIFIÉE
     * - Le stock a déjà été décrémenté à la création (created())
     * - Si paiement échoue : Rollback géré par webhook/callback
     * - Si paiement réussit : Aucune action stock nécessaire (déjà décrémenté)
     */
    protected function handlePaymentStatusChange(Order $order): void
    {
        if (!$order->user_id) return;

        // ✅ CORRECTION 7 : Ignorer si Order est dans un état terminal
        if ($order->isTerminal()) {
            \Log::info('OrderObserver: Order in terminal state, skipping payment status change', [
                'order_id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
            ]);
            return;
        }

        if ($order->payment_status === 'paid') {
            // ✅ CORRECTION 5 : Le stock a déjà été décrémenté à la création
            // StockService vérifie automatiquement si un mouvement existe déjà (protection double décrément)
            // ✅ CONFIRMER LA RÉSERVATION (décrémenter stock réel)
            $this->confirmStockReservation($order);

            // ✅ SPRINT 5-6: Dispatch événement pour comptabilité
            event(new \Modules\Accounting\Events\PaymentRecorded($order));

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
            );
        }
    }

    /**
     * Confirmer la réservation stock (paiement confirmé)
     */
    protected function confirmStockReservation(Order $order): void
    {
        try {
            $items = $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ];
            })->toArray();

            $this->stockReservationService->confirm($items);
            
            \Log::info('Stock reservation confirmed', [
                'order_id' => $order->id,
                'items_count' => count($items),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to confirm stock reservation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Libérer la réservation stock (annulation)
     */
    protected function releaseStockReservation(Order $order): void
    {
        try {
            $items = $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ];
            })->toArray();

            $this->stockReservationService->release($items);
            
            \Log::info('Stock reservation released', [
                'order_id' => $order->id,
                'items_count' => count($items),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to release stock reservation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

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
    public function __construct(
        NotificationService $notificationService,
        DashboardCacheService $cacheService
    ) {
        $this->notificationService = $notificationService;
        $this->cacheService = $cacheService;
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
        // ✅ RBG-P0-01 : DÉCRÉMENTER LE STOCK IMMÉDIATEMENT POUR TOUTES LES TYPES DE PAIEMENT
        // On n'enveloppe plus dans un try-catch permissif pour permettre le rollback de la transaction
        // si le stock est devenu insuffisant entre la validation et la création.
        
        // S'assurer que les items sont chargés avant décrément
        if (!$order->relationLoaded('items')) {
            $order->load('items');
        }
        
        $stockService = app(\Modules\ERP\Services\StockService::class);
        $stockService->decrementFromOrder($order);
        
        \Log::info("Stock decremented immediately for Order #{$order->id} (payment_method: {$order->payment_method})");

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
            
            // ✅ RBG-P0-01 : Le stock est réintégré via restockFromOrder au-dessus
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
            // ✅ RBG-P0-01 : Le stock a déjà été décrémenté à la création (created())

            // ✅ GOVERNANCE C3: Skip PaymentRecorded for creator orders (SaaS Pur)
            // Creator orders go directly to creator's payment gateway - no RACINE accounting
            if ($order->creator_id !== null) {
                \Log::info('OrderObserver: Skipping PaymentRecorded for creator order (SaaS Pur)', [
                    'order_id' => $order->id,
                    'creator_id' => $order->creator_id,
                ]);
                // Continue to loyalty points and notifications, but skip accounting event
            } else {
                // ✅ POS AUDIT-READY: Skip PaymentRecorded for POS orders
                // POS orders have user_id = null and create their own Intents via listeners
                $isPosOrder = is_null($order->user_id) && \App\Models\PosSale::where('order_id', $order->id)->exists();
                
                if (!$isPosOrder) {
                    // ✅ SPRINT 5-6: Dispatch événement pour comptabilité (Brand orders only)
                    event(new \Modules\Accounting\Events\PaymentRecorded($order));
                } else {
                    \Log::info('OrderObserver: Skipping PaymentRecorded for POS order (handled by POS listeners)', [
                        'order_id' => $order->id,
                    ]);
                }
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
            );
        }
    }

}

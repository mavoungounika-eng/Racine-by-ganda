<?php

namespace App\Services\Pos;

use App\Models\Order;
use App\Models\PosSession;
use App\Models\PosSale;
use App\Models\PosPayment;
use App\Models\PosCashMovement;
use App\Models\Product;
use App\Events\PosCardPaymentConfirmed;
use App\Events\PosMobilePaymentConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PosSaleService - Création et gestion des ventes POS
 * 
 * INVARIANTS:
 * - Pas de vente sans session ouverte
 * - Cash reste 'pending' jusqu'à clôture session
 * - Card/Mobile reste 'pending' jusqu'à confirmation externe
 * - POS ne déclenche JAMAIS PaymentRecorded directement
 */
class PosSaleService
{
    public function __construct(
        protected PosSessionService $sessionService
    ) {}

    /**
     * Créer une vente POS
     * 
     * @param string $machineId
     * @param array $items Items de la vente [{product_id, quantity, price}]
     * @param string $paymentMethod cash|card|mobile_money
     * @param int $userId
     * @param array $options Options supplémentaires (customer_name, customer_phone, etc.)
     * @return PosSale
     * @throws \Exception Si pas de session ouverte
     */
    public function createSale(
        string $machineId,
        array $items,
        string $paymentMethod,
        int $userId,
        array $options = []
    ): PosSale {
        return DB::transaction(function () use ($machineId, $items, $paymentMethod, $userId, $options) {
            // 1. Vérifier session ouverte (INVARIANT)
            $session = $this->sessionService->requireOpenSession($machineId);

            // 2. Calculer total et valider items
            $total = 0;
            $orderItems = [];
            
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $price = $item['price'] ?? $product->price;
                $subtotal = $price * $quantity;
                
                // Vérifier stock
                if ($product->stock < $quantity) {
                    throw new \Exception("Stock insuffisant pour {$product->title}");
                }
                
                $total += $subtotal;
                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            // 3. Créer la commande (user_id = null pour POS)
            $order = Order::create([
                'user_id' => null, // POS = pas de user
                'status' => 'pending',
                'payment_status' => 'pending', // JAMAIS 'paid' à la création
                'payment_method' => $paymentMethod,
                'total_amount' => $total,
                'customer_name' => $options['customer_name'] ?? 'Client boutique',
                'customer_email' => $options['customer_email'] ?? 'pos@racine.local', // Default for POS
                'customer_phone' => $options['customer_phone'] ?? null,
                'customer_address' => 'Boutique physique',
            ]);

            // 4. Créer les items de commande
            foreach ($orderItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            // 5. Créer la vente POS
            $sale = PosSale::create([
                'uuid' => Str::uuid()->toString(),
                'order_id' => $order->id,
                'machine_id' => $machineId,
                'session_id' => $session->id,
                'total_amount' => $total,
                'payment_method' => $paymentMethod,
                'status' => PosSale::STATUS_PENDING,
                'created_by' => $userId,
            ]);

            // 6. Créer le paiement POS (TOUJOURS pending)
            $payment = PosPayment::create([
                'pos_sale_id' => $sale->id,
                'method' => $paymentMethod,
                'amount' => $total,
                'status' => PosPayment::STATUS_PENDING, // JAMAIS confirmed à la création
                'provider' => $this->getProviderForMethod($paymentMethod),
            ]);

            // 7. Si cash, créer mouvement (mais reste pending)
            if ($paymentMethod === 'cash') {
                PosCashMovement::createSale($sale, $total, $userId);
            }

            Log::info('POS sale created', [
                'sale_id' => $sale->id,
                'order_id' => $order->id,
                'session_id' => $session->id,
                'payment_method' => $paymentMethod,
                'total' => $total,
            ]);

            return $sale->load(['order', 'payments', 'session']);
        });
    }

    /**
     * Confirmer un paiement carte (après validation TPE)
     * 
     * @param PosPayment $payment
     * @param int $userId
     * @param string|null $transactionId
     * @param string|null $receiptNumber
     * @return PosPayment
     */
    public function confirmCardPayment(PosPayment $payment, int $userId, ?string $transactionId = null, ?string $receiptNumber = null): PosPayment
    {
        if ($payment->method !== PosPayment::METHOD_CARD) {
            throw new \Exception("Ce paiement n'est pas un paiement carte");
        }

        if (!$payment->isPending()) {
            throw new \Exception("Ce paiement n'est pas en attente de confirmation");
        }

        return DB::transaction(function () use ($payment, $userId, $transactionId, $receiptNumber) {
            $payment->confirm($userId, $transactionId ?? $receiptNumber);
            
            $payment->update([
                'metadata' => array_merge($payment->metadata ?? [], [
                    'transaction_id' => $transactionId,
                    'receipt_number' => $receiptNumber,
                    'confirmed_at' => now()->toIso8601String(),
                ]),
            ]);

            // Vérifier si tous les paiements sont confirmés
            $this->checkAndFinalizeSale($payment->sale);

            // Dispatcher event pour Intent finance
            event(new PosCardPaymentConfirmed($payment));

            Log::info('POS card payment confirmed', [
                'payment_id' => $payment->id,
                'sale_id' => $payment->pos_sale_id,
                'confirmed_by' => $userId,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Confirmer un paiement mobile (via callback Monetbil)
     * 
     * @param PosPayment $payment
     * @param string $transactionId
     * @param array $callbackData
     * @return PosPayment
     */
    public function confirmMobilePayment(PosPayment $payment, string $transactionId, array $callbackData = []): PosPayment
    {
        if ($payment->method !== PosPayment::METHOD_MOBILE) {
            throw new \Exception("Ce paiement n'est pas un paiement mobile");
        }

        if (!$payment->isPending()) {
            throw new \Exception("Ce paiement n'est pas en attente de confirmation");
        }

        return DB::transaction(function () use ($payment, $transactionId, $callbackData) {
            $payment->update([
                'status' => PosPayment::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'external_reference' => $transactionId,
                'metadata' => array_merge($payment->metadata ?? [], $callbackData),
            ]);

            // Vérifier si tous les paiements sont confirmés
            $this->checkAndFinalizeSale($payment->sale);

            // Dispatcher event pour Intent finance
            event(new PosMobilePaymentConfirmed($payment));

            Log::info('POS mobile payment confirmed', [
                'payment_id' => $payment->id,
                'sale_id' => $payment->pos_sale_id,
                'transaction_id' => $transactionId,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Vérifier et finaliser la vente si tous paiements confirmés
     */
    protected function checkAndFinalizeSale(PosSale $sale): void
    {
        $sale->load('payments');
        
        // Pour le cash, on ne finalise qu'à la clôture de session
        if ($sale->payment_method === 'cash') {
            return;
        }

        // Vérifier que tous les paiements sont confirmés
        $allConfirmed = $sale->payments->every(fn($p) => $p->isConfirmed());
        
        if ($allConfirmed && $sale->isPending()) {
            $sale->finalize();

            // Mettre à jour la commande liée
            $sale->order->update([
                'payment_status' => 'paid',
                'status' => 'completed',
            ]);

            Log::info('POS sale finalized', [
                'sale_id' => $sale->id,
                'order_id' => $sale->order_id,
            ]);
        }
    }

    /**
     * Annuler une vente POS
     */
    public function cancelSale(PosSale $sale, int $userId, string $reason): PosSale
    {
        if (!$sale->isPending()) {
            throw new \Exception("Seules les ventes pending peuvent être annulées");
        }

        return DB::transaction(function () use ($sale, $userId, $reason) {
            // Annuler la vente
            $sale->cancel($userId, $reason);

            // Annuler les paiements
            foreach ($sale->payments as $payment) {
                if ($payment->isPending()) {
                    $payment->cancel();
                }
            }

            // Annuler la commande
            $sale->order->update([
                'status' => 'cancelled',
            ]);

            Log::info('POS sale cancelled', [
                'sale_id' => $sale->id,
                'cancelled_by' => $userId,
                'reason' => $reason,
            ]);

            return $sale->fresh();
        });
    }

    /**
     * Obtenir le provider selon la méthode de paiement
     */
    protected function getProviderForMethod(string $method): string
    {
        return match ($method) {
            'cash' => 'cash',
            'card' => 'stripe',
            'mobile_money' => 'monetbil',
            default => 'unknown',
        };
    }
}

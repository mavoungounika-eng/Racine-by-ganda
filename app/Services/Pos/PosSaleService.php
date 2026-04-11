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
use App\Events\StockDecremented;
use App\Events\StockLowAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Traits\AuditsPosOperations;

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
    use AuditsPosOperations;

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
     * @param string|null $idempotencyKey Clé d'idempotence (optionnelle)
     * @return PosSale
     * @throws \Exception Si pas de session ouverte
     */
    public function createSale(
        string $machineId,
        array $items,
        string $paymentMethod,
        int $userId,
        array $options = [],
        ?string $idempotencyKey = null
    ): PosSale {
        return DB::transaction(function () use ($machineId, $items, $paymentMethod, $userId, $options, $idempotencyKey) {
            // 1. Vérifier session ouverte (INVARIANT)
            $session = $this->sessionService->requireOpenSession($machineId);

            if ($idempotencyKey !== null) {
                $existing = PosSale::where('session_id', $session->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
                if ($existing) {
                    return $existing->load(['order', 'payments', 'session']);
                }
            }

            // 2. Calculer total et valider items
            $total = 0;
            $orderItems = [];
            
            foreach ($items as $item) {
                // ✅ HARDENING : Lock produit avant validation de stock pour éviter race condition
                $product = Product::where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity = $item['quantity'];
                $price = $item['price'] ?? $product->price;
                $subtotal = $price * $quantity;
                
                // Vérifier que le produit appartient à RACINE (Modèle SaaS Pur)
                if (!$product->isBrand()) {
                    throw new \Exception("Seuls les produits de la marque RACINE peuvent être vendus via le POS. Le produit {$product->title} appartient à un créateur.");
                }

                // Vérifier stock (Verrouillé)
                if (empty($options['force_stock']) && $product->stock < $quantity) {
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

            // 4. Créer la vente POS
            $sale = PosSale::create([
                'uuid' => $options['uuid'] ?? Str::uuid()->toString(),
                'idempotency_key' => $idempotencyKey,
                'order_id' => $order->id,
                'customer_id' => $options['customer_id'] ?? null,
                'machine_id' => $machineId,
                'session_id' => $session->id,
                'total_amount' => $total,
                'payment_method' => $paymentMethod,
                'status' => PosSale::STATUS_PENDING,
                'created_by' => $userId,
            ]);

            // 5. Créer les items de commande
            foreach ($orderItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            // ── 5b. Décrément stock POS (synchrone, dans la transaction) ──
            foreach ($orderItems as $orderItem) {
                /** @var Product $product */
                $product = $orderItem['product'];

                // Respecter track_stock
                if (isset($product->track_stock) && !$product->track_stock) {
                    continue;
                }

                $stockBefore = $product->stock;
                $product->decrement('stock', $orderItem['quantity']);
                $stockAfter = $product->fresh()->stock;

                try {
                    event(new StockDecremented(
                        product_id:   $product->id,
                        qty_removed:  $orderItem['quantity'],
                        stock_before: $stockBefore,
                        stock_after:  $stockAfter,
                        source:       'pos_sale',
                        reference_id: $sale->id,
                    ));

                    $threshold = $product->low_stock_threshold ?? config('erp.low_stock_threshold', 5);
                    if ($stockAfter <= $threshold) {
                        event(new StockLowAlert(
                            product_id:    $product->id,
                            product_name:  $product->title,
                            current_stock: $stockAfter,
                            threshold:     $threshold,
                            source:        'pos_sale',
                        ));
                    }
                } catch (\Throwable $e) {
                    // Jamais bloquer une vente à cause d'une notification
                    Log::error("PosSaleService: stock event failed for product #{$product->id}: " . $e->getMessage());
                }
            }

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

            self::logPosAction(\App\Models\PosOperatorAuditLog::ACTION_SALE_CREATED, [
                'sale_id' => $sale->id,
                'session_id' => $session->id,
                'total_amount' => $total,
                'payment_method' => $paymentMethod,
                'items_count' => count($items),
                'notes' => 'Vente créée via service',
            ], $userId);

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

            // Attribuer points de fidélité
            try {
                if ($sale->customer_id) {
                    app(\App\Services\Crm\LoyaltyService::class)->awardPointsForPosSale($sale);
                }
            } catch (\Throwable $e) {
                Log::error("PosSaleService: Loyalty awarding failed for sale #{$sale->id}: " . $e->getMessage());
            }
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

            // Restaurer le stock
            $this->restoreStockForSale($sale);

            // Annuler la commande
            $sale->order->update([
                'status' => 'cancelled',
            ]);

            self::logPosAction(\App\Models\PosOperatorAuditLog::ACTION_SALE_CANCELLED, [
                'sale_id' => $sale->id,
                'session_id' => $sale->session_id,
                'reason' => $reason,
                'notes' => 'Vente annulée via service',
            ], $userId);

            Log::info('POS sale cancelled', [
                'sale_id' => $sale->id,
                'cancelled_by' => $userId,
                'reason' => $reason,
            ]);

            return $sale->fresh();
        });
    }

    /**
     * Restaurer le stock pour une vente annulée ou remboursée
     */
    protected function restoreStockForSale(PosSale $sale): void
    {
        $order = $sale->order->load('items.product');

        try {
            app(\Modules\ERP\Services\StockService::class)->restockFromOrder($order);
        } catch (\Throwable $e) {
            // Fallback: increment manuel si StockService indisponible
            foreach ($order->items as $item) {
                if ($item->product && (!isset($item->product->track_stock) || $item->product->track_stock)) {
                    $item->product->increment('stock', $item->quantity);
                }
            }
            Log::warning("PosSaleService: StockService fallback used for sale #{$sale->id}: " . $e->getMessage());
        }
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

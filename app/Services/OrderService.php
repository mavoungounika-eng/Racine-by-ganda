<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Exceptions\OrderException;
use App\Exceptions\StockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\IdempotencyKey;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Services\Cart\DatabaseCartService;
use App\Services\Cart\SessionCartService;
use App\Services\StockReservationService;
use App\Services\StockValidationService;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des commandes
 * 
 * Responsable de la création de commandes depuis le checkout frontend.
 * Gère la logique métier : calculs, validation, création commande et items.
 * 
 * FONCTIONNALITÉS :
 * - Validation du stock avec verrouillage (via StockValidationService)
 * - Calcul des montants (sous-total, livraison, total)
 * - Création de commande et items dans une transaction
 * - Vidage du panier après création réussie
 * - Émission d'événement OrderPlaced pour analytics
 * 
 * SÉCURITÉ :
 * - Utilise des transactions DB pour atomicité
 * - Validation stock avec lockForUpdate() pour éviter race conditions
 * 
 * @package App\Services
 */
class OrderService
{
    protected StockValidationService $stockValidationService;
    protected StockReservationService $stockReservationService;

    public function __construct(
        StockValidationService $stockValidationService,
        StockReservationService $stockReservationService
    ) {
        $this->stockValidationService = $stockValidationService;
        $this->stockReservationService = $stockReservationService;
    }

    /**
     * Créer une commande depuis le panier et les données du formulaire
     * 
     * Cette méthode centralise toute la logique de création de commande :
     * - Validation du stock
     * - Calcul des montants
     * - Création de la commande et des items
     * - Vidage du panier
     * 
     * @param array $formData Données du formulaire (full_name, email, phone, address, etc.)
     * @param Collection $cartItems Items du panier
     * @param int $userId ID de l'utilisateur
     * @return Order Commande créée avec ses relations chargées
     * @throws OrderException Si un produit n'existe plus
     * @throws StockException Si le stock est insuffisant
     * @throws \Throwable En cas d'erreur lors de la création
     */
    public function createOrderFromCart(array $formData, Collection $cartItems, int $userId, ?string $idempotencyKey = null, ?string $checkoutToken = null, ?int $promoCodeId = null, float $promoDiscount = 0, bool $promoFreeShipping = false): Order
    {
        if ($cartItems->isEmpty()) {
            throw new OrderException(
                'Panier vide',
                400,
                'Votre panier est vide.'
            );
        }

        $idempotencyKey = $idempotencyKey ?: ($checkoutToken ? "checkout:{$userId}:{$checkoutToken}" : null);
        if ($idempotencyKey) {
            try {
                IdempotencyKey::create([
                    'key' => $idempotencyKey,
                    'status' => 'processing',
                ]);
            } catch (QueryException $e) {
                $existing = IdempotencyKey::where('key', $idempotencyKey)->first();
                if ($existing && $existing->status === 'completed' && $existing->response) {
                    $payload = json_decode($existing->response, true);
                    if (is_array($payload) && isset($payload['order_id'])) {
                        $order = Order::find($payload['order_id']);
                        if ($order) {
                            Log::info('OrderService: Idempotent replay detected, returning existing order', [
                                'order_id' => $order->id,
                                'user_id' => $userId,
                            ]);
                            return $order;
                        }
                    }
                }

                throw new OrderException(
                    'Commande en cours',
                    409,
                    'Votre commande est déjà en cours de traitement. Veuillez patienter.'
                );
            }
        }

        // ✅ FINAL HARDENING - Idempotence : Vérifier commande existante pour ce checkout_token
        // Si une commande existe déjà pour ce token (double soumission), retourner la commande existante
        if ($checkoutToken) {
            // Chercher une commande créée récemment (5 dernières minutes) avec le même user_id
            // et le même total_amount (approximation pour éviter double commande)
            $recentOrder = Order::where('user_id', $userId)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->where('total_amount', $this->calculateAmounts($cartItems, $formData['shipping_method'])['total'])
                ->where('payment_status', 'pending') // Uniquement commandes non payées
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($recentOrder) {
                // Vérifier que les items correspondent (même produits, mêmes quantités)
                $recentItems = $recentOrder->items()->get();
                if ($recentItems->count() === $cartItems->count()) {
                    $itemsMatch = true;
                    foreach ($cartItems as $cartItem) {
                        $recentItem = $recentItems->firstWhere('product_id', $cartItem->product_id);
                        if (!$recentItem || $recentItem->quantity !== $cartItem->quantity) {
                            $itemsMatch = false;
                            break;
                        }
                    }
                    
                    if ($itemsMatch) {
                        Log::info('OrderService: Duplicate order detected, returning existing order', [
                            'existing_order_id' => $recentOrder->id,
                            'user_id' => $userId,
                            'checkout_token_present' => !empty($checkoutToken),
                        ]);
                        return $recentOrder;
                    }
                }
            }
        }

        // 2) Calcul des montants (hors transaction, pas de DB)
        $amounts = $this->calculateAmounts($cartItems, $formData['shipping_method'], $promoDiscount, $promoFreeShipping);

        // 3) Création de la commande et des items dans une transaction
        // RBG-P0-020 : Validation stock + verrouillage dans la transaction pour anti-oversell
        try {
            $order = DB::transaction(function () use ($formData, $cartItems, $userId, $amounts, $promoCodeId) {
                // 1) Validation du stock avec verrouillage (dans la transaction pour lockForUpdate)
                try {
                    $stockValidation = $this->stockValidationService->validateStockForCart($cartItems);
                    $lockedProducts = $stockValidation['locked_products'];
                } catch (\Throwable $e) {
                    Log::error('OrderService: Stock validation failed', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);
                    throw $e;
                }

                // 2) Double vérification du code promo avec lock (anti race-condition)
                $lockedPromo = null;
                if ($promoCodeId) {
                    $lockedPromo = PromoCode::where('id', $promoCodeId)->lockForUpdate()->first();
                    if (!$lockedPromo || !$lockedPromo->canBeUsedBy($userId, $formData['email'] ?? null)) {
                        throw new OrderException(
                            'Code promo invalide ou expiré au moment de la commande',
                            422,
                            'Votre code promo n\'est plus valide. Veuillez recommencer sans code promo.'
                        );
                    }
                }

                // Générer order_number et qr_token avant création
                $orderNumberService = app(\App\Services\OrderNumberService::class);
                $orderNumber = $orderNumberService->generateOrderNumber();
                $qrToken = Order::generateUniqueQrToken();

                // Déterminer le creator_id (Propriétaire des produits)
                // On prend le user_id du premier produit car validateCartIntegrity garantit l'unicité du propriétaire
                $firstProduct = $lockedProducts->first();
                $creatorId = ($firstProduct && $firstProduct->product_type === 'marketplace')
                    ? $firstProduct->user_id
                    : null;

                // Créer la commande sans déclencher les observers (pour créer les items d'abord)
                $order = Order::withoutEvents(function () use ($formData, $userId, $amounts, $orderNumber, $qrToken, $creatorId, $promoCodeId) {
                    return Order::create([
                        'user_id' => $userId,
                        'creator_id' => $creatorId,
                        'customer_name' => $formData['full_name'],
                        'customer_email' => $formData['email'],
                        'customer_phone' => $formData['phone'],
                        'customer_address' => $this->formatAddress($formData),
                        'shipping_method' => $formData['shipping_method'],
                        'shipping_cost' => $amounts['shipping'],
                        'payment_method' => $formData['payment_method'],
                        'payment_status' => 'pending',
                        'status' => 'pending',
                        'total_amount' => $amounts['total'],
                        'discount_amount' => $amounts['discount'],
                        'promo_code_id' => $promoCodeId,
                        'order_number' => $orderNumber,
                        'qr_token' => $qrToken,
                    ]);
                });

                // Enregistrer l'usage du code promo et incrémenter le compteur
                if ($lockedPromo && $amounts['discount'] > 0) {
                    PromoCodeUsage::create([
                        'promo_code_id'   => $lockedPromo->id,
                        'user_id'         => $userId,
                        'order_id'        => $order->id,
                        'email'           => $formData['email'] ?? null,
                        'discount_amount' => $amounts['discount'],
                    ]);
                    $lockedPromo->increment('used_count');
                    Log::info('OrderService: Promo code usage recorded', [
                        'promo_code_id' => $lockedPromo->id,
                        'order_id'      => $order->id,
                        'discount'      => $amounts['discount'],
                    ]);
                }

                // 🧪 TEST ROLLBACK FORCÉ (Étape A4)
                if (config('app.env') === 'testing' && request()->header('X-Force-Rollback')) {
                    Log::warning('OrderService: Forcing transactional rollback for verification (Step A4)');
                    throw new \Exception('FORCED_ROLLBACK_TEST');
                }

                // Créer les items de commande
                $this->createOrderItems($order, $cartItems, $lockedProducts);
                
                // ✅ RBG-P0-01 : DÉCRÉMENT ATOMIQUE
                // Le décrément est maintenant géré uniquement par StockService via l'Observer (ci-dessous)
                // pour éviter le double décrément (Reservation + Stock decrement).
                
                // Charger les items et déclencher manuellement l'Observer created() avec les items disponibles
                $order->load('items');
                $observer = app(\App\Observers\OrderObserver::class);
                $observer->created($order);

                Log::info('Order created from cart', [
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'payment_method' => $formData['payment_method'],
                    'total_amount' => $amounts['total'],
                ]);

                // Phase 3 : Émettre l'event OrderPlaced pour le monitoring (après Observer)
                event(new OrderPlaced($order));

                return $order;
            });

            if ($idempotencyKey) {
                IdempotencyKey::where('key', $idempotencyKey)->update([
                    'status' => 'completed',
                    'response' => json_encode(['order_id' => $order->id]),
                ]);
            }

            return $order;
        } catch (\Throwable $e) {
            if ($idempotencyKey) {
                IdempotencyKey::where('key', $idempotencyKey)->delete();
            }
            throw $e;
        }
    }

    /**
     * Calculer les montants de la commande
     * 
     * @param Collection $cartItems Items du panier
     * @param string $shippingMethod Méthode de livraison (home_delivery, showroom_pickup)
     * @return array ['subtotal' => float, 'shipping' => float, 'total' => float]
     */
    public function calculateAmounts(Collection $cartItems, string $shippingMethod, float $promoDiscount = 0, bool $promoFreeShipping = false): array
    {
        // Calculer le sous-total
        $subtotal = $cartItems->sum(function ($item) {
            $price = is_object($item) ? $item->price : $item['price'];
            $qty = is_object($item) ? $item->quantity : $item['quantity'];
            return $price * $qty;
        });

        // Calculer les frais de livraison (free_shipping promo écrase la méthode)
        $shippingCost = (int) config("payments.shipping.{$shippingMethod}", config('payments.shipping.home_delivery', 2000));
        $shipping = ($promoFreeShipping || $shippingMethod === 'showroom_pickup') ? 0 : $shippingCost;

        // Appliquer la réduction promo sans descendre sous 0
        $discount = min($promoDiscount, $subtotal);

        // Total final
        $total = max(0, $subtotal - $discount + $shipping);

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $total,
        ];
    }

    /**
     * Formater l'adresse complète depuis les données du formulaire
     * 
     * @param array $formData Données du formulaire
     * @return string Adresse formatée
     */
    protected function formatAddress(array $formData): string
    {
        $parts = array_filter([
            $formData['address_line1'] ?? null,
            $formData['address_line2'] ?? null,
            $formData['postal_code'] ?? null,
            $formData['city'] ?? null,
            $formData['country'] ?? null,
        ]);
        return implode(', ', $parts);
    }

    /**
     * Créer les items de commande depuis le panier
     * 
     * @param Order $order Commande créée
     * @param Collection $cartItems Items du panier
     * @param Collection $lockedProducts Produits verrouillés (pour éviter de recharger)
     * @return void
     */
    protected function createOrderItems(Order $order, Collection $cartItems, Collection $lockedProducts): void
    {
        foreach ($cartItems as $item) {
            $productId = is_object($item) ? $item->product_id : $item['product_id'];
            $qty = is_object($item) ? $item->quantity : $item['quantity'];
            $price = is_object($item) ? $item->price : $item['price'];

            $product = $lockedProducts->get($productId);

            if (!$product) {
                throw new OrderException(
                    'Produit introuvable',
                    404,
                    'Un produit de votre panier n\'existe plus.'
                );
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'price' => $price,
                'quantity' => $qty,
            ]);
        }
    }
}

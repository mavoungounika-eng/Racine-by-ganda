<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Exceptions\OrderException;
use App\Exceptions\StockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cart\DatabaseCartService;
use App\Services\Cart\SessionCartService;
use App\Services\StockReservationService;
use App\Services\StockValidationService;
use Illuminate\Support\Collection;
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
    public function createOrderFromCart(array $formData, Collection $cartItems, int $userId, ?string $checkoutToken = null): Order
    {
        if ($cartItems->isEmpty()) {
            throw new OrderException(
                'Panier vide',
                400,
                'Votre panier est vide.'
            );
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
        $amounts = $this->calculateAmounts($cartItems, $formData['shipping_method']);

        // 3) Création de la commande et des items dans une transaction
        // RBG-P0-020 : Validation stock + verrouillage dans la transaction pour anti-oversell
        return DB::transaction(function () use ($formData, $cartItems, $userId, $amounts) {
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
            
            // Générer order_number et qr_token avant création
            $orderNumberService = app(\App\Services\OrderNumberService::class);
            $orderNumber = $orderNumberService->generateOrderNumber();
            $qrToken = Order::generateUniqueQrToken();
            
            // Créer la commande sans déclencher les observers (pour créer les items d'abord)
            $order = Order::withoutEvents(function () use ($formData, $userId, $amounts, $orderNumber, $qrToken) {
                return Order::create([
                    'user_id' => $userId,
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
                    'order_number' => $orderNumber,
                    'qr_token' => $qrToken,
                ]);
            });

            // Créer les items de commande
            $this->createOrderItems($order, $cartItems, $lockedProducts);
            
            // ✅ RÉSERVER LE STOCK (anti-survente)
            // Préparer les items pour réservation
            $itemsToReserve = $cartItems->map(function ($item) {
                return [
                    'product_id' => is_object($item) ? $item->product_id : $item['product_id'],
                    'quantity' => is_object($item) ? $item->quantity : $item['quantity'],
                ];
            })->toArray();
            
            try {
                $this->stockReservationService->reserve($itemsToReserve);
                Log::info('Stock reserved for order', [
                    'order_id' => $order->id,
                    'items_count' => count($itemsToReserve),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to reserve stock', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                throw new StockException(
                    'Échec réservation stock',
                    500,
                    'Impossible de réserver le stock. Veuillez réessayer.'
                );
            }
            
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
    }

    /**
     * Calculer les montants de la commande
     * 
     * @param Collection $cartItems Items du panier
     * @param string $shippingMethod Méthode de livraison (home_delivery, showroom_pickup)
     * @return array ['subtotal' => float, 'shipping' => float, 'total' => float]
     */
    public function calculateAmounts(Collection $cartItems, string $shippingMethod): array
    {
        // Calculer le sous-total
        $subtotal = $cartItems->sum(function ($item) {
            $price = is_object($item) ? $item->price : $item['price'];
            $qty = is_object($item) ? $item->quantity : $item['quantity'];
            return $price * $qty;
        });

        // Calculer les frais de livraison
        $shipping = $shippingMethod === 'home_delivery' ? 2000 : 0; // 2000 FCFA pour livraison à domicile

        // Total
        $total = $subtotal + $shipping;

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
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
        return $formData['address_line1'] . ', ' . $formData['city'] . ', ' . $formData['country'];
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


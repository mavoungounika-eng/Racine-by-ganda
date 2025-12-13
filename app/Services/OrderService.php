<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Exceptions\OrderException;
use App\Exceptions\StockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cart\DatabaseCartService;
use App\Services\Cart\SessionCartService;
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

    public function __construct(StockValidationService $stockValidationService)
    {
        $this->stockValidationService = $stockValidationService;
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
    public function createOrderFromCart(array $formData, Collection $cartItems, int $userId): Order
    {
        if ($cartItems->isEmpty()) {
            throw new OrderException(
                'Panier vide',
                400,
                'Votre panier est vide.'
            );
        }

        // 1) Validation du stock avec verrouillage
        $stockValidation = $this->stockValidationService->validateStockForCart($cartItems);
        $lockedProducts = $stockValidation['locked_products'];

        // 2) Calcul des montants
        $amounts = $this->calculateAmounts($cartItems, $formData['shipping_method']);

        // 3) Création de la commande et des items dans une transaction
        return DB::transaction(function () use ($formData, $cartItems, $userId, $lockedProducts, $amounts) {
            // Créer la commande
            $order = Order::create([
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
            ]);

            // Créer les items de commande
            $this->createOrderItems($order, $cartItems, $lockedProducts);

            Log::info('Order created from cart', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'payment_method' => $formData['payment_method'],
                'total_amount' => $amounts['total'],
            ]);

            // Phase 3 : Émettre l'event OrderPlaced pour le monitoring
            event(new OrderPlaced($order));

            return $order->load('items');
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


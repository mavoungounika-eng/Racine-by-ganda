<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\OrderNumberService;
use App\Services\Payments\CardPaymentService;
use App\Services\Payments\MobileMoneyPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Contrôleur pour le système POS (Point of Sale) - Boutique physique
 */
class PosController extends Controller
{
    /**
     * Afficher l'interface POS
     */
    public function index(): View
    {
        $this->authorize('viewAny', Order::class);
        
        return view('admin.pos.index');
    }

    /**
     * Rechercher un produit par code-barres, SKU ou ID
     */
    public function searchProduct(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);
        
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = trim($request->code);

        // Rechercher par code-barres
        $product = Product::whereHas('erpDetails', function ($query) use ($code) {
            $query->where('barcode', $code)
                  ->orWhere('sku', $code);
        })->with('erpDetails', 'category')->first();

        // Si pas trouvé, essayer par ID
        if (!$product && is_numeric($code)) {
            $product = Product::with('erpDetails', 'category')->find($code);
        }

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé',
            ], 404);
        }

        // Vérifier le stock
        if ($product->stock <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Produit en rupture de stock',
                'product' => $product,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
                'stock' => $product->stock,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'category' => $product->category?->name,
                'image' => $product->main_image ? asset('storage/' . $product->main_image) : null,
            ],
        ]);
    }

    /**
     * Créer une commande depuis le POS
     */
    public function createOrder(Request $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'payment_method' => 'required|in:cash,card,mobile_money',
            'mobile_money_provider' => 'required_if:payment_method,mobile_money|in:mtn_momo,airtel_money',
        ]);

        // Validation supplémentaire pour Mobile Money
        if ($request->payment_method === 'mobile_money' && !$request->customer_phone) {
            return response()->json([
                'success' => false,
                'message' => 'Le numéro de téléphone est requis pour le paiement Mobile Money',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Calculer le total
            $total = 0;
            $items = [];

            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $quantity = $itemData['quantity'];

                // Vérifier le stock
                if ($product->stock < $quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour {$product->title}. Disponible: {$product->stock}",
                    ], 400);
                }

                $subtotal = $product->price * $quantity;
                $total += $subtotal;

                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            // Déterminer le statut selon le moyen de paiement
            $paymentMethod = $request->payment_method;
            $paymentStatus = 'pending';
            $orderStatus = 'pending';
            
            // Pour cash, le paiement est immédiat
            if ($paymentMethod === 'cash') {
                $paymentStatus = 'paid';
                $orderStatus = 'completed';
            }

            // Créer la commande (sans user_id pour éviter que l'Observer ne décrémente en double)
            // L'Observer ne décrémente que si user_id existe, donc pour POS on gère manuellement
            $order = Order::create([
                'user_id' => null, // Pas de user_id pour les commandes POS (évite double décrémentation)
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'total_amount' => $total,
                'customer_name' => $request->customer_name ?? 'Client boutique',
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'customer_address' => 'Boutique physique',
            ]);

            // Créer les items de commande
            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            // Créer l'enregistrement Payment selon le moyen de paiement
            $payment = $this->createPayment($order, $paymentMethod, $request);

            // Décrémenter le stock et créer les mouvements selon le statut du paiement
            if ($payment->status === 'paid') {
                // Pour cash : décrémenter immédiatement
                foreach ($items as $item) {
                    // Décrémenter le stock
                    $item['product']->decrement('stock', $item['quantity']);

                    // Créer mouvement de stock avec raison "Vente en boutique"
                    \Modules\ERP\Models\ErpStockMovement::create([
                        'stockable_type' => Product::class,
                        'stockable_id' => $item['product']->id,
                        'type' => 'out',
                        'quantity' => $item['quantity'],
                        'reason' => 'Vente en boutique',
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'user_id' => Auth::id(),
                        'from_location' => 'Boutique',
                        'to_location' => 'Client',
                    ]);
                }

                // Actions post-paiement pour les espèces
                $this->handlePostPaymentActions($order, $payment);
            } else {
                // Pour card/mobile_money : le stock sera décrémenté quand le paiement sera confirmé
                // via l'OrderObserver ou via confirmCardPayment()
            }

            DB::commit();

            $response = [
                'success' => true,
                'message' => 'Commande créée avec succès',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->total_amount,
                    'items_count' => count($items),
                    'payment_status' => $order->payment_status,
                    'status' => $order->status,
                ],
            ];

            // Ajouter des informations spécifiques selon le moyen de paiement
            if ($paymentMethod === 'card') {
                $response['payment'] = [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'message' => 'Paiement par carte initié. Traitez la transaction sur le TPE.',
                ];
            } elseif ($paymentMethod === 'mobile_money') {
                $response['payment'] = [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'transaction_id' => $payment->external_reference,
                    'message' => 'Paiement Mobile Money initié. En attente de confirmation.',
                ];
            } else {
                $response['payment'] = [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'message' => 'Paiement en espèces confirmé.',
                ];
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les détails d'une commande
     */
    public function getOrder(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['items.product.erpDetails', 'user']);

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $order->total_amount,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'customer_name' => $order->customer_name,
                'created_at' => $order->created_at->format('d/m/Y H:i'),
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_title' => $item->product->title,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Créer un enregistrement Payment selon le moyen de paiement
     * 
     * @param Order $order
     * @param string $paymentMethod
     * @param Request $request
     * @return Payment
     */
    protected function createPayment(Order $order, string $paymentMethod, Request $request): Payment
    {
        $paymentData = [
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'currency' => config('app.currency', 'XAF'),
            'channel' => $paymentMethod,
        ];

        switch ($paymentMethod) {
            case 'cash':
                // Paiement en espèces : immédiatement payé
                $payment = Payment::create(array_merge($paymentData, [
                    'provider' => 'cash',
                    'status' => 'paid',
                    'paid_at' => now(),
                    'metadata' => [
                        'order_id' => $order->id,
                        'customer_name' => $order->customer_name,
                        'payment_location' => 'Boutique physique',
                        'processed_by' => Auth::id(),
                        'processed_at' => now()->toIso8601String(),
                    ],
                ]));

                Log::info('POS Cash payment created', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'amount' => $order->total_amount,
                ]);

                return $payment;

            case 'card':
                // Paiement par carte : créer Payment avec status 'initiated'
                // En boutique, on peut utiliser un TPE (Terminal de Paiement Électronique)
                // Pour l'instant, on marque comme 'pending' et l'admin peut confirmer manuellement
                // ou intégrer avec un TPE si disponible
                
                $cardPaymentService = app(CardPaymentService::class);
                
                try {
                    // Si on veut utiliser Stripe Checkout même en boutique (optionnel)
                    // Sinon, on crée juste un Payment en attente de confirmation TPE
                    $payment = Payment::create(array_merge($paymentData, [
                        'provider' => 'stripe',
                        'status' => 'pending',
                        'metadata' => [
                            'order_id' => $order->id,
                            'customer_name' => $order->customer_name,
                            'payment_location' => 'Boutique physique',
                            'payment_method' => 'TPE',
                            'processed_by' => Auth::id(),
                            'initiated_at' => now()->toIso8601String(),
                            'note' => 'Paiement par carte en boutique. À confirmer via TPE.',
                        ],
                    ]));

                    Log::info('POS Card payment created (pending TPE confirmation)', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'amount' => $order->total_amount,
                    ]);

                    return $payment;
                } catch (\Exception $e) {
                    Log::error('POS Card payment creation failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }

            case 'mobile_money':
                // Paiement Mobile Money : initier le paiement
                $phone = $request->customer_phone;
                $provider = $request->input('mobile_money_provider', 'mtn_momo'); // mtn_momo ou airtel_money
                
                if (!$phone) {
                    throw new \Exception('Numéro de téléphone requis pour le paiement Mobile Money');
                }

                $mobileMoneyService = app(MobileMoneyPaymentService::class);
                
                try {
                    $payment = $mobileMoneyService->initiatePayment($order, $phone, $provider);
                    
                    Log::info('POS Mobile Money payment initiated', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'provider' => $provider,
                        'phone' => $phone,
                    ]);

                    return $payment;
                } catch (\Exception $e) {
                    Log::error('POS Mobile Money payment initiation failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }

            default:
                throw new \Exception("Moyen de paiement non supporté: {$paymentMethod}");
        }
    }

    /**
     * Gérer les actions post-paiement (notifications, points de fidélité, etc.)
     * 
     * @param Order $order
     * @param Payment $payment
     * @return void
     */
    protected function handlePostPaymentActions(Order $order, Payment $payment): void
    {
        // 1. Envoyer email de confirmation si email fourni
        if ($order->customer_email) {
            try {
                \Mail::to($order->customer_email)->send(new \App\Mail\OrderConfirmationMail($order));
            } catch (\Exception $e) {
                Log::error('Failed to send POS order confirmation email', [
                    'order_id' => $order->id,
                    'email' => $order->customer_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Notifier l'équipe (staff & admin)
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->broadcastToTeam(
            'Nouvelle vente boutique !',
            "Commande {$order->order_number} - " . number_format($order->total_amount, 0, ',', ' ') . " FCFA",
            'order'
        );

        // 3. Attribuer des points de fidélité si client a un compte
        // Chercher le client par email ou téléphone
        if ($order->customer_email || $order->customer_phone) {
            $user = null;
            
            if ($order->customer_email) {
                $user = \App\Models\User::where('email', $order->customer_email)->first();
            }
            
            if (!$user && $order->customer_phone) {
                // Chercher par téléphone directement dans la table users
                // Normaliser le numéro pour la recherche
                $phone = preg_replace('/[^0-9+]/', '', $order->customer_phone);
                $user = \App\Models\User::where('phone', $phone)
                    ->orWhere('phone', 'like', '%' . substr($phone, -9) . '%') // Derniers 9 chiffres
                    ->first();
            }

            if ($user) {
                // Mettre à jour user_id de la commande pour la traçabilité
                $order->update(['user_id' => $user->id]);

                // Attribuer des points de fidélité
                try {
                    $loyaltyService = app(\App\Services\LoyaltyService::class);
                    $loyaltyService->awardPointsForOrder($order);

                    // Notifier le client
                    $notificationService->success(
                        $user->id,
                        'Paiement reçu !',
                        "Le paiement de votre commande {$order->order_number} a été confirmé. Merci !"
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to award loyalty points for POS order', [
                        'order_id' => $order->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('POS post-payment actions completed', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'payment_method' => $payment->channel,
        ]);
    }

    /**
     * Confirmer un paiement par carte (après validation TPE)
     */
    public function confirmCardPayment(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $request->validate([
            'transaction_id' => 'nullable|string',
            'receipt_number' => 'nullable|string',
        ]);

        $payment = $order->payments()
            ->where('channel', 'card')
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun paiement en attente trouvé pour cette commande',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'provider_payment_id' => $request->transaction_id,
                'external_reference' => $request->receipt_number ?? $request->transaction_id,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'confirmed_at' => now()->toIso8601String(),
                    'confirmed_by' => Auth::id(),
                    'transaction_id' => $request->transaction_id,
                    'receipt_number' => $request->receipt_number,
                ]),
            ]);

            $order->update([
                'payment_status' => 'paid',
                'status' => 'completed',
            ]);

            // Décrémenter le stock et créer les mouvements
            foreach ($order->items as $item) {
                $product = $item->product;

                if (!$product) {
                    continue;
                }

                // Décrémenter le stock
                $product->decrement('stock', $item->quantity);

                // Créer mouvement de stock avec raison "Vente en boutique"
                \Modules\ERP\Models\ErpStockMovement::create([
                    'stockable_type' => Product::class,
                    'stockable_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'reason' => 'Vente en boutique',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'user_id' => Auth::id(),
                    'from_location' => 'Boutique',
                    'to_location' => 'Client',
                ]);
            }

            // Actions post-paiement
            $this->handlePostPaymentActions($order, $payment);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paiement confirmé avec succès',
                'payment' => [
                    'id' => $payment->id,
                    'status' => $payment->status,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation: ' . $e->getMessage(),
            ], 500);
        }
    }
}


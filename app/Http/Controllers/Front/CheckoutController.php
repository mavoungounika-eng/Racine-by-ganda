<?php

namespace App\Http\Controllers\Front;

use App\Events\CheckoutStarted;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOrderRequest;
use App\Exceptions\OrderException;
use App\Exceptions\StockException;
use App\Models\Address;
use App\Models\Order;
use App\Services\Cart\DatabaseCartService;
use App\Services\Cart\SessionCartService;
use App\Services\OrderService;
use App\Services\StockValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    protected OrderService $orderService;
    protected StockValidationService $stockValidationService;

    public function __construct(OrderService $orderService, StockValidationService $stockValidationService)
    {
        $this->orderService = $orderService;
        $this->stockValidationService = $stockValidationService;
    }

    /**
     * Obtenir le service de panier approprié (DB ou Session)
     */
    protected function getCartService()
    {
        return Auth::check() ? new DatabaseCartService() : new SessionCartService();
    }

    /**
     * Affiche la page de checkout
     */
    public function index()
    {
        // ✅ Vérification d'authentification
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour finaliser votre commande.');
        }

        $user = Auth::user();
        
        // ✅ Vérification du rôle client
        if (!$user->isClient()) {
            return redirect()->route('frontend.home')
                ->with('error', 'Seuls les clients peuvent passer des commandes.');
        }
        
        // ✅ Vérification du statut utilisateur
        if ($user->status !== 'active') {
            return redirect()->route('frontend.home')
                ->with('error', 'Votre compte doit être actif pour passer une commande.');
        }

        $cartService = $this->getCartService();
        $items = $cartService->getItems();
        $subtotal = $cartService->total();
        $shipping_default = 2000; // 2000 FCFA par défaut pour livraison à domicile

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        // Phase 3 : Émettre l'event CheckoutStarted pour le monitoring
        event(new CheckoutStarted(Auth::id(), $items->count(), $subtotal));

        // Charger les adresses du client
        $addresses = Address::where('user_id', $user->id)->get();
        $defaultAddress = $addresses->where('is_default', true)->first() ?? $addresses->first();

        return view('checkout.index', compact('items', 'subtotal', 'shipping_default', 'addresses', 'defaultAddress', 'user'));
    }

    /**
     * Créer une commande depuis le checkout
     * 
     * Circuit propre selon spécifications :
     * - Crée la commande avec status='pending', payment_status='pending'
     * - DÉCRÉMENT STOCK :
     *   - cash_on_delivery : Décrémenté immédiatement dans OrderObserver@created
     *   - card/mobile_money : Décrémenté dans OrderObserver@handlePaymentStatusChange quand payment_status='paid'
     * - Redirige selon payment_method :
     *   - cash_on_delivery → checkout.success
     *   - card → checkout.card.pay
     *   - mobile_money → checkout.mm.form
     * 
     * La logique métier (validation stock, calculs, création) est déléguée à OrderService.
     */
    public function placeOrder(PlaceOrderRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Charger le panier
        $cartService = $this->getCartService();
        $items = $cartService->getItems();
        
        if ($items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Votre panier est vide.');
        }

        try {
            // Déléguer la création de commande au service
            $order = $this->orderService->createOrderFromCart($data, $items, $user->id);

            // Vider le panier après création réussie
            $cartService->clear();

        } catch (OrderException | StockException $e) {
            return back()->with('error', $e->getUserMessage())->withInput();
        } catch (\Throwable $e) {
            \Log::error('Erreur création commande checkout', [
                'user_id' => $user->id ?? null,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Une erreur est survenue lors de la création de la commande.')->withInput();
        }

        // Redirection selon le mode de paiement
        return $this->redirectToPayment($order, $data['payment_method']);
    }

    /**
     * Rediriger vers la page de paiement appropriée
     * 
     * @param Order $order Commande créée
     * @param string $paymentMethod Méthode de paiement choisie
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectToPayment(Order $order, string $paymentMethod)
    {
        switch ($paymentMethod) {
            case 'cash_on_delivery':
                return redirect()
                    ->route('checkout.success', $order)
                    ->with('success', 'Votre commande est enregistrée. Vous paierez à la livraison.');

            case 'card':
                return redirect()
                    ->route('checkout.card.pay', ['order_id' => $order->id]);

            case 'mobile_money':
                return redirect()
                    ->route('checkout.mobile-money.form', ['order' => $order->id]);

            default:
                return redirect()
                    ->route('checkout.success', $order)
                    ->with('success', 'Commande enregistrée.');
        }
    }

    /**
     * Page de succès après commande
     */
    public function success(Order $order)
    {
        // Utiliser OrderPolicy pour vérifier l'accès
        $this->authorize('view', $order);

        $order->load(['items.product', 'address']);

        return view('checkout.success', compact('order'));
    }

    /**
     * Page d'annulation de paiement
     */
    public function cancel(Order $order)
    {
        // Utiliser OrderPolicy pour vérifier l'accès
        $this->authorize('view', $order);

        // Récupérer le mode de paiement depuis la commande
        $paymentMethod = $order->payment_method ?? 'card';

        return view('checkout.cancel', compact('order', 'paymentMethod'));
    }

    /**
     * Vérifier le stock avant validation de commande (API endpoint)
     * 
     * Utilisé pour la validation en temps réel côté client avant soumission du formulaire.
     */
    public function verifyStock(Request $request)
    {
        $cartService = $this->getCartService();
        $items = $cartService->getItems();

        // Déléguer la vérification au service
        $result = $this->stockValidationService->checkStockIssues($items);

        return response()->json($result);
    }

    /**
     * Valider l'email en temps réel
     */
    public function validateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'errors' => $validator->errors()->all(),
            ]);
        }

        return response()->json([
            'valid' => true,
        ]);
    }

    /**
     * Valider le téléphone en temps réel
     */
    public function validatePhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]{8,20}$/',
        ], [
            'phone.regex' => 'Le format du téléphone est invalide. Format attendu : +242 06 XXX XX XX',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'errors' => $validator->errors()->all(),
            ]);
        }

        return response()->json([
            'valid' => true,
        ]);
    }

    /**
     * Appliquer un code promo
     */
    public function applyPromo(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'total' => 'required|numeric|min:0',
        ]);

        $code = strtoupper(trim($request->code));
        $total = floatval($request->total);

        $promoCode = \App\Models\PromoCode::findByCode($code);

        if (!$promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Code promo invalide.',
            ], 400);
        }

        if (!$promoCode->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce code promo n\'est plus valide ou a expiré.',
            ], 400);
        }

        $userId = Auth::id();
        $email = Auth::check() ? Auth::user()->email : $request->input('email');

        if (!$promoCode->canBeUsedBy($userId, $email)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà utilisé ce code promo le nombre maximum de fois autorisé.',
            ], 400);
        }

        if (!$promoCode->meetsMinimumAmount($total)) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant minimum de ' . number_format($promoCode->min_amount, 0, ',', ' ') . ' FCFA n\'est pas atteint.',
            ], 400);
        }

        $discount = $promoCode->calculateDiscount($total);
        $freeShipping = $promoCode->type === 'free_shipping';

        return response()->json([
            'success' => true,
            'message' => $freeShipping 
                ? 'Livraison gratuite appliquée !' 
                : 'Code promo appliqué ! Réduction de ' . number_format($discount, 0, ',', ' ') . ' FCFA',
            'promo_code' => [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
                'name' => $promoCode->name,
                'type' => $promoCode->type,
            ],
            'discount_amount' => $discount,
            'free_shipping' => $freeShipping,
        ]);
    }
}

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

        // ✅ Module 8 - Protection double soumission : Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);

        return view('checkout.index', compact('items', 'subtotal', 'shipping_default', 'addresses', 'defaultAddress', 'user', 'checkoutToken'));
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
        // Log d'entrée pour tracer le flux
        \Log::info('=== CHECKOUT PLACEORDER START ===', [
            'user_id' => $request->user()->id ?? null,
            'payment_method' => $request->input('payment_method'),
            'csrf_token_present' => $request->has('_token'),
            'session_token' => session()->token(),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
        ]);

        // ✅ Module 8 - Protection double soumission : Vérifier token unique
        $submittedToken = $request->input('_checkout_token');
        $sessionToken = session('checkout_token');

        if (!$sessionToken || $submittedToken !== $sessionToken) {
            \Log::warning('Checkout: Double submission attempt blocked', [
                'user_id' => $request->user()->id ?? null,
                'ip' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 100),
                'has_session_token' => !empty($sessionToken),
                'tokens_match' => $submittedToken === $sessionToken,
            ]);
            return back()
                ->with('error', 'Ce formulaire a déjà été soumis. Si votre commande a été créée, vérifiez vos commandes.')
                ->withInput();
        }

        $user = $request->user();
        $data = $request->validated();

        \Log::info('Checkout: Data validated', [
            'payment_method' => $data['payment_method'] ?? 'NOT SET',
            'full_name' => $data['full_name'] ?? 'NOT SET',
            'email' => $data['email'] ?? 'NOT SET',
        ]);

        // Charger le panier
        $cartService = $this->getCartService();
        $items = $cartService->getItems();
        
        \Log::info('Checkout: Cart loaded', [
            'items_count' => $items->count(),
            'cart_total' => $cartService->total(),
            'user_id' => $user->id,
        ]);
        
        if ($items->isEmpty()) {
            \Log::warning('Checkout: Cart is empty');
            return redirect()->route('cart.index')
                ->with('error', 'Votre panier est vide.');
        }

        // ✅ VÉRIFICATION CRITIQUE : Ownership du panier
        // S'assurer que le panier appartient bien à l'utilisateur connecté
        // Protection contre manipulation de session ou injection
        if ($cartService instanceof DatabaseCartService) {
            $cart = $cartService->getCart();
            if ($cart && $cart->user_id !== $user->id) {
                \Log::error('Checkout: Cart ownership violation', [
                    'user_id' => $user->id,
                    'cart_user_id' => $cart->user_id,
                    'cart_id' => $cart->id,
                    'ip' => $request->ip(),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 100),
                ]);
                abort(403, 'Accès refusé : ce panier ne vous appartient pas.');
            }
            
            // Vérification supplémentaire : s'assurer que tous les items du panier appartiennent à l'utilisateur
            foreach ($items as $item) {
                if ($item->cart_id && $item->cart) {
                    if ($item->cart->user_id !== $user->id) {
                        \Log::error('Checkout: Cart item ownership violation', [
                            'user_id' => $user->id,
                            'cart_user_id' => $item->cart->user_id,
                            'cart_id' => $item->cart_id,
                            'item_id' => $item->id,
                            'ip' => $request->ip(),
                            'user_agent' => substr($request->userAgent() ?? '', 0, 100),
                        ]);
                        abort(403, 'Accès refusé : un article de votre panier ne vous appartient pas.');
                    }
                }
            }
        }

        try {
            \Log::info('Checkout: Calling OrderService::createOrderFromCart', [
                'user_id' => $user->id,
                'payment_method' => $data['payment_method'],
                'items_count' => $items->count(),
            ]);

            // Déléguer la création de commande au service avec token pour idempotence
            $checkoutToken = $request->input('_checkout_token');
            $order = $this->orderService->createOrderFromCart($data, $items, $user->id, $checkoutToken);

            \Log::info('Checkout: Order created', [
                'order_id' => $order->id ?? 'NO ID',
                'payment_method' => $order->payment_method ?? 'NOT SET',
                'payment_status' => $order->payment_status ?? 'NOT SET',
                'status' => $order->status ?? 'NOT SET',
            ]);

            // Vérifier que l'order a bien un ID avant redirection
            if (!$order || !$order->id) {
                \Log::error('Checkout: Order created but has no ID', [
                    'user_id' => $user->id,
                    'payment_method' => $data['payment_method'] ?? 'unknown',
                    'order' => $order ? 'exists but no id' : 'null',
                ]);
                return redirect()->route('checkout.index')
                    ->with('error', 'Une erreur est survenue lors de la création de la commande. Veuillez réessayer.')
                    ->withInput();
            }

            // Vider le panier après création réussie
            $cartService->clear();
            \Log::info('Checkout: Cart cleared');

            // ✅ Module 8 - Protection double soumission : Supprimer token après utilisation
            session()->forget('checkout_token');

            \Log::info('Checkout: Calling redirectToPayment', [
                'order_id' => $order->id,
                'payment_method' => $data['payment_method'],
            ]);

            // Redirection dans le try pour catch les exceptions de redirection
            $redirect = $this->redirectToPayment($order, $data['payment_method']);
            
            \Log::info('Checkout: Redirect created successfully', [
                'target_url' => $redirect->getTargetUrl(),
                'session_has_success' => session()->has('success'),
                'session_success' => session('success'),
            ]);

            return $redirect;

        } catch (OrderException | StockException $e) {
            \Log::error('Checkout: OrderException or StockException', [
                'user_id' => $user->id,
                'payment_method' => $data['payment_method'] ?? 'unknown',
                'error' => $e->getMessage(),
                'user_message' => $e->getUserMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', $e->getUserMessage())
                ->withInput();
        } catch (\Throwable $e) {
            \Log::error('Checkout: Unexpected exception', [
                'user_id' => $user->id ?? null,
                'payment_method' => $data['payment_method'] ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Une erreur est survenue lors de la création de la commande. Veuillez réessayer ou nous contacter.')
                ->withInput();
        }
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
        \Log::info('=== REDIRECT TO PAYMENT ===', [
            'order_id' => $order->id ?? null,
            'payment_method' => $paymentMethod,
            'order_exists' => $order ? 'yes' : 'no',
            'order_has_id' => ($order && $order->id) ? 'yes' : 'no',
        ]);

        try {
            switch ($paymentMethod) {
                case 'cash_on_delivery':
                    if (!$order->id) {
                        \Log::error('Checkout: cash_on_delivery selected but order has no ID');
                        throw new \RuntimeException('Order has no ID');
                    }
                    
                    \Log::info('Checkout: Redirecting to success for cash_on_delivery', [
                        'order_id' => $order->id,
                        'order_payment_method' => $order->payment_method,
                        'order_payment_status' => $order->payment_status,
                    ]);
                    
                    $redirect = redirect()
                        ->route('checkout.success', ['order' => $order->id])
                        ->with('success', 'Votre commande est enregistrée. Vous paierez à la livraison.');
                    
                    \Log::info('Checkout: cash_on_delivery redirect created', [
                        'target_url' => $redirect->getTargetUrl(),
                        'session_will_have_success' => true,
                    ]);
                    
                    return $redirect;

                case 'card':
                    \Log::info('Checkout: Redirecting to card payment', [
                        'order_id' => $order->id,
                    ]);
                    return redirect()
                        ->route('checkout.card.pay', ['order_id' => $order->id]);

                case 'mobile_money':
                case 'monetbil':
                    \Log::info('Checkout: Redirecting to Monetbil payment', [
                        'order_id' => $order->id,
                        'payment_method' => $paymentMethod,
                    ]);
                    return redirect()
                        ->route('payment.monetbil.start', ['order' => $order->id]);

                default:
                    \Log::warning('Checkout: Unknown payment method, defaulting to success', [
                        'payment_method' => $paymentMethod,
                        'order_id' => $order->id ?? null,
                    ]);
                    return redirect()
                        ->route('checkout.success', ['order' => $order->id])
                        ->with('success', 'Commande enregistrée.');
            }
        } catch (\Throwable $e) {
            \Log::error('Checkout: Error in redirectToPayment', [
                'order_id' => $order->id ?? null,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Fallback: rediriger vers success même en cas d'erreur
            if ($order && $order->id) {
                \Log::info('Checkout: Using fallback redirect to success', [
                    'order_id' => $order->id,
                ]);
                return redirect()
                    ->route('checkout.success', ['order' => $order->id])
                    ->with('success', 'Votre commande a été enregistrée.');
            }
            
            // Si même le fallback échoue, retourner au checkout avec erreur
            \Log::error('Checkout: Fallback redirect also failed, returning to checkout', [
                'order' => $order ? 'exists but no id' : 'null',
            ]);
            return redirect()->route('checkout.index')
                ->with('error', 'Une erreur est survenue lors de la redirection. Votre commande a peut-être été créée. Vérifiez vos commandes.')
                ->withInput();
        }
    }

    /**
     * Page de succès après commande
     */
    public function success(Order $order)
    {
        // Log pour debug (peut être retiré après résolution du bug)
        \Log::info('Checkout success page accessed', [
            'order_id' => $order->id ?? null,
            'payment_method' => $order->payment_method ?? 'unknown',
            'session_has_success' => session()->has('success'),
            'session_success' => session('success'),
        ]);

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

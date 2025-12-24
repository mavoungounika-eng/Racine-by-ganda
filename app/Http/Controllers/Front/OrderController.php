<?php

namespace App\Http\Controllers\Front;

use App\Exceptions\OrderException;
use App\Exceptions\StockException;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Cart\DatabaseCartService;
use App\Services\Cart\SessionCartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated Cette classe est OBSOLÈTE et ne doit plus être utilisée.
 * 
 * Le tunnel de checkout a été refactorisé et migré vers CheckoutController.
 * 
 * ⚠️ IMPORTANT :
 * - Aucune route n'utilise ce contrôleur
 * - Les méthodes checkout(), placeOrder() et success() sont obsolètes
 * - Utiliser CheckoutController à la place
 * 
 * @see \App\Http\Controllers\Front\CheckoutController Le contrôleur officiel pour le checkout
 * 
 * Cette classe est conservée temporairement pour référence historique uniquement.
 * Elle sera supprimée dans une future version après vérification complète.
 * 
 * Date de dépréciation : 10 décembre 2025
 */
class OrderController extends Controller
{
    protected function getService()
    {
        return Auth::check() ? new DatabaseCartService() : new SessionCartService();
    }

    /**
     * @deprecated Ne plus utiliser. Tunnel checkout remplacé par CheckoutController@index().
     * 
     * Cette méthode est obsolète et n'est utilisée par aucune route.
     * Utiliser CheckoutController@index() à la place (route: checkout.index).
     * 
     * @see \App\Http\Controllers\Front\CheckoutController::index()
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function checkout()
    {
        // ✅ Vérification d'authentification (déjà garantie par middleware auth, mais double vérification)
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

        $service = $this->getService();
        $items = $service->getItems();
        $total = $service->total();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        // Charger les adresses du client
        $addresses = Address::where('user_id', $user->id)->get();
        $defaultAddress = $addresses->where('is_default', true)->first() ?? $addresses->first();

        // Générer token unique pour éviter double soumission
        $formToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $formToken]);

        return view('frontend.checkout.index', compact('items', 'total', 'addresses', 'defaultAddress', 'user', 'formToken'));
    }

    /**
     * @deprecated Ne plus utiliser. Tunnel checkout remplacé par CheckoutController@placeOrder().
     * 
     * Cette méthode est obsolète et n'est utilisée par aucune route.
     * Utiliser CheckoutController@placeOrder() à la place (route: checkout.place).
     * 
     * ⚠️ INCOMPATIBILITÉS :
     * - Utilise payment_method: 'cash' au lieu de 'cash_on_delivery'
     * - Redirection incompatible avec CheckoutController@success()
     * - Logique inline au lieu d'utiliser OrderService
     * 
     * @see \App\Http\Controllers\Front\CheckoutController::placeOrder()
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws OrderException Si la commande ne peut pas être créée
     * @throws StockException Si le stock est insuffisant
     */
    public function placeOrder(Request $request)
    {
        // ✅ FINAL HARDENING - Blocage définitif du chemin legacy
        // Cette méthode est OBSOLÈTE et ne doit JAMAIS être utilisée
        // Même si aucune route n'y pointe, bloquer explicitement pour éviter toute utilisation future
        \Log::channel('security')->warning('Legacy checkout blocked: OrderController::placeOrder() called', [
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 100),
            'user_id' => Auth::id(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
        
        // 410 Gone = Ressource définitivement supprimée, utiliser CheckoutController à la place
        abort(410, 'Cette méthode est obsolète. Veuillez utiliser le tunnel de checkout officiel.');

        // ✅ Protection anti-double soumission : Vérifier token unique
        $submittedToken = $request->input('_checkout_token');
        $sessionToken = session('checkout_token');

        if (!$sessionToken || $submittedToken !== $sessionToken) {
            // Token invalide ou déjà utilisé = double soumission probable
            return back()->with('error', 'Ce formulaire a déjà été soumis. Si votre commande a été créée, vérifiez vos commandes.')
                ->withInput();
        }

        // ✅ Vérification d'authentification (déjà garantie par middleware auth, mais double vérification)
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour passer une commande.');
        }

        $user = Auth::user();
        
        // ✅ Vérification du rôle client
        if (!$user->isClient()) {
            return back()->with('error', 'Seuls les clients peuvent passer des commandes.');
        }
        
        // ✅ Vérification du statut utilisateur
        if ($user->status !== 'active') {
            return back()->with('error', 'Votre compte doit être actif pour passer une commande.');
        }

        // Validation conditionnelle selon si une adresse est sélectionnée ou non
        $rules = [
            'address_id' => 'nullable|exists:addresses,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'payment_method' => 'required|in:card,mobile_money,cash',
        ];

        // Si aucune adresse n'est sélectionnée, valider les champs d'adresse
        // NOTE: Le checkout est réservé aux utilisateurs connectés (middleware auth requis)
        // Le support visiteur a été retiré pour simplifier et sécuriser le processus
        if (!$request->filled('address_id')) {
            // Utilisateur connecté (garanti par middleware auth) - valider les champs structurés
            if ($request->filled('new_address_line_1')) {
                $rules['new_address_first_name'] = 'required|string|max:255';
                $rules['new_address_last_name'] = 'required|string|max:255';
                $rules['new_address_line_1'] = 'required|string|max:255';
                $rules['new_address_city'] = 'required|string|max:255';
                $rules['new_address_country'] = 'required|string|max:100';
                $rules['new_address_phone'] = 'nullable|string|max:50';
                $rules['new_address_line_2'] = 'nullable|string|max:255';
                $rules['new_address_postal_code'] = 'nullable|string|max:20';
            } else {
                // Si aucune adresse et pas de nouvelle adresse, erreur
                return back()->with('error', 'Veuillez sélectionner une adresse ou en créer une nouvelle.');
            }
            $rules['save_new_address'] = 'boolean';
        } else {
            // Si address_id est fourni, vérifier qu'elle appartient à l'utilisateur
            $address = Address::where('id', $request->address_id)
                ->where('user_id', Auth::id())
                ->first();
            
            if (!$address) {
                return back()->with('error', 'Adresse non trouvée ou non autorisée.');
            }
        }

        $request->validate($rules);

        $service = $this->getService();
        $items = $service->getItems();
        $total = $service->total();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        // Vérification finale du stock avec verrouillage pour éviter race condition
        // Utilisateur connecté garanti
        $productsToLock = [];
        foreach ($items as $item) {
            $product = $item->product;
            $qty = $item->quantity;
            
            if (!$product) {
                throw new OrderException(
                    'Produit introuvable dans le panier.',
                    404,
                    'Un produit de votre panier n\'existe plus. Veuillez mettre à jour votre panier.'
                );
            }
            
            // Collecter les IDs pour verrouillage
            $productsToLock[] = $product->id;
        }
        
        // Verrouiller tous les produits pour éviter race condition
        $lockedProducts = Product::whereIn('id', $productsToLock)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        
        // Vérifier le stock avec les produits verrouillés
        foreach ($items as $item) {
            $product = $lockedProducts->get($item->product_id);
            $qty = $item->quantity;
            
            if (!$product) {
                throw new OrderException(
                    'Produit introuvable dans le panier.',
                    404,
                    'Un produit de votre panier n\'existe plus. Veuillez mettre à jour votre panier.'
                );
            }
            
            if ($product->stock < $qty) {
                throw new StockException(
                    "Stock insuffisant pour le produit {$product->id}",
                    400,
                    "Stock insuffisant pour le produit : {$product->title}. Stock disponible : {$product->stock}"
                );
            }
        }

        try {
            DB::beginTransaction();

            // Gestion de l'adresse
            $addressId = null;
            $customerName = $request->customer_name;
            $customerEmail = $request->customer_email;
            $customerPhone = $request->customer_phone;
            $customerAddress = $request->customer_address;

            // Si une adresse existante est sélectionnée (utilisateur connecté garanti)
            if ($request->filled('address_id')) {
                $address = Address::where('id', $request->address_id)
                    ->where('user_id', Auth::id())
                    ->firstOrFail();
                
                $addressId = $address->id;
                $customerName = $address->first_name . ' ' . $address->last_name;
                $customerPhone = $address->phone ?? $customerPhone;
                $customerAddress = $address->full_address;
            } 
            // Sinon, si une nouvelle adresse est fournie
            elseif ($request->filled('new_address_line_1')) {
                // Si l'utilisateur veut sauvegarder l'adresse, la créer dans la table
                if ($request->boolean('save_new_address')) {
                    $address = Address::create([
                        'user_id' => Auth::id(),
                        'type' => 'shipping',
                        'first_name' => $request->new_address_first_name,
                        'last_name' => $request->new_address_last_name,
                        'phone' => $request->new_address_phone ?? $customerPhone,
                        'address_line_1' => $request->new_address_line_1,
                        'address_line_2' => $request->new_address_line_2,
                        'city' => $request->new_address_city,
                        'postal_code' => $request->new_address_postal_code,
                        'country' => $request->new_address_country ?? 'Congo',
                        'is_default' => false, // Ne pas forcer comme défaut automatiquement
                    ]);
                    
                    $addressId = $address->id;
                    $customerName = $address->first_name . ' ' . $address->last_name;
                    $customerPhone = $address->phone ?? $customerPhone;
                    $customerAddress = $address->full_address;
                } else {
                    // Adresse non sauvegardée, utiliser les données du formulaire
                    $customerName = $request->new_address_first_name . ' ' . $request->new_address_last_name;
                    $customerPhone = $request->new_address_phone ?? $customerPhone;
                    $customerAddress = trim(
                        $request->new_address_line_1 . 
                        ($request->new_address_line_2 ? ', ' . $request->new_address_line_2 : '') . 
                        ', ' . $request->new_address_city . 
                        ($request->new_address_postal_code ? ' ' . $request->new_address_postal_code : '') . 
                        ', ' . ($request->new_address_country ?? 'Congo')
                    );
                }
            } else {
                // Aucune adresse fournie - ne devrait pas arriver grâce à la validation
                throw new OrderException(
                    'Adresse de livraison manquante.',
                    400,
                    'Veuillez fournir une adresse de livraison pour finaliser votre commande.'
                );
            }

            // Gestion code promo
            $promoCodeId = null;
            $discountAmount = 0;
            $shippingCost = floatval($request->input('shipping_cost', 5900));
            $shippingMethod = $request->input('shipping_method', 'standard');
            
            if ($request->filled('promo_code_id')) {
                $promoCode = \App\Models\PromoCode::find($request->promo_code_id);
                if ($promoCode && $promoCode->isValid() && $promoCode->meetsMinimumAmount($total)) {
                    $promoCodeId = $promoCode->id;
                    $discountAmount = $promoCode->calculateDiscount($total);
                    
                    // Si livraison gratuite, mettre shipping_cost à 0
                    if ($promoCode->type === 'free_shipping') {
                        $shippingCost = 0;
                    }
                }
            }
            
            // Calculer le total final
            $finalTotal = $total - $discountAmount + $shippingCost;

            // Création de la commande (user_id garanti non null car utilisateur authentifié)
            $order = Order::create([
                'user_id' => Auth::id(), // ✅ Garanti non null par middleware auth
                'address_id' => $addressId,
                'promo_code_id' => $promoCodeId,
                'discount_amount' => $discountAmount,
                'shipping_method' => $shippingMethod,
                'shipping_cost' => $shippingCost,
                'status' => 'pending',
                'payment_status' => 'pending',
                'total_amount' => $finalTotal,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'customer_address' => $customerAddress,
            ]);
            
            // Enregistrer l'utilisation du code promo
            if ($promoCodeId) {
                \App\Models\PromoCodeUsage::create([
                    'promo_code_id' => $promoCodeId,
                    'user_id' => Auth::id(),
                    'order_id' => $order->id,
                    'email' => $customerEmail,
                    'discount_amount' => $discountAmount,
                ]);
                
                // Incrémenter le compteur d'utilisation
                $promoCode = \App\Models\PromoCode::find($promoCodeId);
                if ($promoCode) {
                    $promoCode->increment('used_count');
                }
            }

            // Création des lignes de commande
            // Note: Le stock sera décrémenté dans OrderObserver lorsque le paiement sera confirmé
            foreach ($items as $item) {
                // Utiliser le produit verrouillé pour garantir cohérence
                $product = $lockedProducts->get($item->product_id);
                $qty = $item->quantity;
                $price = $item->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $price,
                ]);
            }

            // Vider le panier
            $service->clear();

            // Supprimer le token pour éviter réutilisation
            session()->forget('checkout_token');

            DB::commit();

            // ⚠️ IMPORTANT : Pour le paiement cash, marquer comme payé APRÈS commit
            // Cela garantit que l'Observer@updated() sera déclenché et décrémentera le stock
            if ($request->payment_method === 'cash') {
                $order->refresh(); // Recharger depuis DB pour avoir les valeurs à jour
                $order->update(['payment_status' => 'paid']);
                // Observer@updated() sera déclenché ici et décrémentera le stock
            }

            // Redirection selon le mode de paiement
            $paymentMethod = $request->payment_method;

            // Amélioration : Stocker order_id en session ET en query string pour meilleure récupération
            session(['order_id' => $order->id]);
            session(['order_number' => $order->order_number ?? $order->id]);

            if ($paymentMethod === 'card') {
                // Redirection vers le paiement par carte
                return redirect()->route('checkout.card.pay', ['order_id' => $order->id])
                    ->with('success', 'Commande créée ! Procédez au paiement.')
                    ->with('order_id', $order->id);
                
            } elseif ($paymentMethod === 'mobile_money') {
                // Redirection vers le formulaire Mobile Money
                return redirect()->route('checkout.mobile-money.form', $order)
                    ->with('success', 'Commande créée ! Procédez au paiement Mobile Money.')
                    ->with('order_id', $order->id);
                
            } else {
                // Paiement à la livraison - commande confirmée directement
                return redirect()->route('checkout.success', ['order_id' => $order->id])->with([
                    'success' => 'Commande passée avec succès ! Vous paierez à la livraison.',
                ])->with('order_id', $order->id);
            }

        } catch (OrderException | StockException $e) {
            DB::rollBack();
            return back()->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur création commande', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new OrderException(
                $e->getMessage(),
                500,
                'Une erreur est survenue lors de la création de votre commande. Veuillez réessayer ou contacter le support.'
            );
        }
    }

    /**
     * @deprecated Ne plus utiliser. Tunnel checkout remplacé par CheckoutController@success().
     * 
     * Cette méthode est obsolète et n'est utilisée par aucune route.
     * Utiliser CheckoutController@success() à la place (route: checkout.success).
     * 
     * ⚠️ INCOMPATIBILITÉS :
     * - N'utilise pas route model binding (récupère order_id manuellement)
     * - Logique de récupération complexe et fragile
     * 
     * @see \App\Http\Controllers\Front\CheckoutController::success()
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function success(Request $request)
    {
        // Amélioration : Récupération order_id avec plusieurs fallbacks
        $orderId = $request->input('order_id') 
            ?? $request->query('order_id')
            ?? $request->session()->get('order_id')
            ?? $request->session()->get('order_number'); // Fallback sur order_number
        
        // Si order_number, chercher par order_number
        if ($orderId && !is_numeric($orderId)) {
            $order = Order::where('order_number', $orderId)->first();
            if ($order) {
                $orderId = $order->id;
            }
        }
        
        if (!$orderId) {
            // Dernier fallback : Si utilisateur connecté, chercher sa dernière commande
            if (Auth::check()) {
                $order = Order::where('user_id', Auth::id())
                    ->orderBy('created_at', 'desc')
                    ->first();
                if ($order) {
                    return view('checkout.success', compact('order'));
                }
            }
            return redirect()->route('frontend.home')->with('error', 'Commande non trouvée.');
        }
        
        $order = Order::with(['items.product', 'address', 'promoCode'])
            ->where('id', $orderId)
            ->first();
        
        if (!$order) {
            return redirect()->route('frontend.home')->with('error', 'Commande non trouvée.');
        }
        
        // Vérifier que la commande appartient à l'utilisateur (si connecté)
        if (Auth::check()) {
            if ($order->user_id !== Auth::id()) {
                return redirect()->route('frontend.home')->with('error', 'Vous n\'avez pas accès à cette commande.');
            }
        }
        
        // Nettoyer la session après récupération
        $request->session()->forget(['order_id', 'order_number']);
        
        return view('checkout.success', compact('order'));
    }
}

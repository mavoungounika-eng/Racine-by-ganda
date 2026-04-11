<?php

namespace App\Http\Controllers\Front;

use App\Events\ProductAddedToCart;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart\DatabaseCartService;
use App\Services\Cart\SessionCartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartController extends Controller
{
    protected $cartService;

    public function __construct()
    {
        // On instancie le bon service selon l'état de connexion
        // Note: Dans un vrai projet, on utiliserait un ServiceProvider pour l'injection
    }

    protected function getService()
    {
        return Auth::check() ? new DatabaseCartService() : new SessionCartService();
    }

    public function index(): View
    {
        $service = $this->getService();
        $items = $service->getItems();
        $total = $service->total();

        return view('cart.index', compact('items', 'total'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Charger le produit avec vérifications
        $product = Product::where('id', $request->product_id)
            ->where('is_active', true)
            ->first();
        
        // Vérification produit existe et actif
        if (!$product) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce produit n\'est plus disponible.'
                ], 404);
            }
            return back()->with('error', 'Ce produit n\'est plus disponible.');
        }
        
        // Vérification stock
        if ($product->stock <= 0) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock épuisé. Ce produit n\'est plus disponible pour le moment.'
                ], 400);
            }
            return back()->with('error', 'Stock épuisé. Ce produit n\'est plus disponible pour le moment.');
        }
        
        // Vérification quantité <= stock
        if ($product->stock < $request->quantity) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuffisant. Il ne reste que ' . $product->stock . ' exemplaire(s) disponible(s).',
                    'available_stock' => $product->stock
                ], 400);
            }
            return back()->with('error', 'Stock insuffisant. Il ne reste que ' . $product->stock . ' exemplaire(s) disponible(s).');
        }
        
        // Limiter la quantité au stock disponible (sécurité supplémentaire)
        $quantity = min($request->quantity, $product->stock);

        $this->getService()->add($product, $quantity);
        $count = $this->getService()->count();

        // Phase 4 : Émettre l'event ProductAddedToCart pour le monitoring
        event(new ProductAddedToCart($product, Auth::id(), $quantity));

        // Si requête AJAX, retourner JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Produit ajouté au panier.',
                'count' => $count
            ]);
        }

        // Sinon, redirection normale
        $redirect = $request->input('redirect', $request->query('redirect', 'cart'));
        
        if ($redirect === 'back') {
            return back()->with('success', 'Produit ajouté au panier.');
        } elseif ($redirect === 'shop' || $redirect === 'boutique') {
            return redirect()->route('frontend.shop')->with('success', 'Produit ajouté au panier.');
        } else {
            return redirect()->route('cart.index')->with('success', 'Produit ajouté au panier.');
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Vérification stock
        if ($product->stock < $request->quantity) {
            return back()->with('error', 'Stock insuffisant pour la quantité demandée.');
        }

        $this->getService()->update($request->product_id, $request->quantity);

        return redirect()->route('cart.index')->with('success', 'Panier mis à jour.');
    }

    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $this->getService()->remove($request->product_id);

        return redirect()->route('cart.index')->with('success', 'Produit retiré du panier.');
    }

    /**
     * Retourner le nombre d'articles dans le panier (API)
     */
    public function count()
    {
        $service = $this->getService();
        $count = $service->count();
        
        return response()->json(['count' => $count]);
    }
}

<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WishlistController extends Controller
{
    /**
     * Affiche la liste des favoris de l'utilisateur
     */
    public function index(): View
    {
        $user = Auth::user();
        
        $wishlistItems = Wishlist::where('user_id', $user->id)
            ->with(['product.category'])
            ->latest()
            ->paginate(12);

        return view('profile.wishlist', compact('wishlistItems'));
    }

    /**
     * Ajouter un produit aux favoris (AJAX)
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = Auth::user();
        $productId = $request->product_id;

        // Vérifier si déjà en favoris
        $exists = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ce produit est déjà dans vos favoris',
            ], 400);
        }

        Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produit ajouté aux favoris',
        ]);
    }

    /**
     * Retirer un produit des favoris
     */
    public function remove(Request $request, ?int $id = null): JsonResponse|RedirectResponse
    {
        $productId = $request->input('product_id', $id);
        
        if (!$productId) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID produit manquant',
                ], 400);
            }
            return back()->with('error', 'ID produit manquant');
        }

        $user = Auth::user();

        $wishlistItem = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if (!$wishlistItem) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé dans vos favoris',
                ], 404);
            }
            return back()->with('error', 'Produit non trouvé dans vos favoris');
        }

        $wishlistItem->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Produit retiré des favoris',
            ]);
        }

        return back()->with('success', 'Produit retiré des favoris');
    }

    /**
     * Toggle favoris (ajouter/retirer)
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = Auth::user();
        $productId = $request->product_id;

        $wishlistItem = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($wishlistItem) {
            $wishlistItem->delete();
            $isInWishlist = false;
            $message = 'Produit retiré des favoris';
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);
            $isInWishlist = true;
            $message = 'Produit ajouté aux favoris';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_in_wishlist' => $isInWishlist,
        ]);
    }

    /**
     * Vider tous les favoris
     */
    public function clear(): RedirectResponse
    {
        $user = Auth::user();
        Wishlist::where('user_id', $user->id)->delete();

        return redirect()->route('profile.wishlist')
            ->with('success', 'Tous vos favoris ont été supprimés');
    }
}

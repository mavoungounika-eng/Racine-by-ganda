<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Affiche la liste des avis de l'utilisateur
     */
    public function index(): View
    {
        $user = Auth::user();
        
        $reviews = Review::where('user_id', $user->id)
            ->with(['product.category', 'order'])
            ->latest()
            ->paginate(15);

        return view('profile.reviews', compact('reviews'));
    }

    /**
     * Affiche le formulaire pour laisser un avis sur une commande
     */
    public function create(Order $order): View
    {
        // Utiliser OrderPolicy pour vérifier l'accès
        $this->authorize('view', $order);
        
        $user = Auth::user();

        // Charger les produits de la commande qui n'ont pas encore d'avis
        $order->load(['items.product']);
        
        $reviewableProducts = $order->items->map(function ($item) use ($user) {
            $product = $item->product;
            $hasReview = Review::where('product_id', $product->id)
                ->where('user_id', $user->id)
                ->exists();
            
            return [
                'product' => $product,
                'order_item' => $item,
                'has_review' => $hasReview,
            ];
        })->filter(function ($item) {
            return !$item['has_review'];
        });

        return view('profile.review-create', compact('order', 'reviewableProducts'));
    }

    /**
     * Enregistre un avis
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id' => 'nullable|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Vérifier si l'utilisateur a déjà laissé un avis pour ce produit
        $existingReview = Review::where('product_id', $validated['product_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($existingReview) {
            return back()->with('error', 'Vous avez déjà laissé un avis pour ce produit.');
        }

        // Vérifier que l'utilisateur a acheté le produit (si order_id fourni)
        if ($validated['order_id']) {
            $order = Order::find($validated['order_id']);
            // Utiliser OrderPolicy pour vérifier l'accès
            $this->authorize('view', $order);
            
            $hasProduct = $order->items()->where('product_id', $validated['product_id'])->exists();
            if (!$hasProduct) {
                return back()->with('error', 'Ce produit n\'est pas dans cette commande.');
            }
        }

        Review::create([
            'product_id' => $validated['product_id'],
            'user_id' => $user->id,
            'order_id' => $validated['order_id'] ?? null,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_verified_purchase' => !empty($validated['order_id']),
            'is_approved' => true, // Auto-approuver pour l'instant
        ]);

        return redirect()->route('profile.reviews')
            ->with('success', 'Votre avis a été enregistré. Merci !');
    }

    /**
     * Affiche le formulaire d'édition d'un avis
     */
    public function edit(Review $review): View
    {
        $user = Auth::user();
        
        // Vérifier que l'avis appartient à l'utilisateur
        if ($review->user_id !== $user->id) {
            abort(403, 'Vous n\'avez pas accès à cet avis.');
        }

        $review->load(['product', 'order']);

        return view('profile.review-edit', compact('review'));
    }

    /**
     * Met à jour un avis
     */
    public function update(Request $request, Review $review): RedirectResponse
    {
        $user = Auth::user();
        
        // Vérifier que l'avis appartient à l'utilisateur
        if ($review->user_id !== $user->id) {
            abort(403, 'Vous n\'avez pas accès à cet avis.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_approved' => false, // Réapprouver après modification
        ]);

        return redirect()->route('profile.reviews')
            ->with('success', 'Votre avis a été modifié. Il sera réexaminé avant publication.');
    }

    /**
     * Supprime un avis
     */
    public function destroy(Review $review): RedirectResponse
    {
        $user = Auth::user();
        
        // Vérifier que l'avis appartient à l'utilisateur
        if ($review->user_id !== $user->id) {
            abort(403, 'Vous n\'avez pas accès à cet avis.');
        }

        $review->delete();

        return redirect()->route('profile.reviews')
            ->with('success', 'Votre avis a été supprimé.');
    }
}

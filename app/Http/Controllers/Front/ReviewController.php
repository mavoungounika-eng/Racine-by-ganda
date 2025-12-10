<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Product $product)
    {
        // Vérifier si l'utilisateur a déjà laissé un avis
        $existingReview = Review::where('product_id', $product->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingReview) {
            return back()->with('error', 'Vous avez déjà laissé un avis pour ce produit.');
        }

        // Vérifier si l'utilisateur a acheté le produit
        $hasPurchased = Order::where('user_id', Auth::id())
            ->where('payment_status', 'paid')
            ->whereHas('items', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->exists();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Review::create([
            'product_id' => $product->id,
            'user_id' => Auth::id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_verified_purchase' => $hasPurchased,
            'is_approved' => true, // Auto-approuver pour l'instant
        ]);

        return back()->with('success', 'Votre avis a été enregistré. Merci !');
    }
}


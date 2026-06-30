<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PosProductController — catalogue produits de l'API POS Connect (Electron).
 *
 * GET /api/pos/v1/products
 * → { success: true, data: [{ id, name, price, stock, image_url }] }
 *
 * Ne retourne que les produits actifs du créateur authentifié.
 */
class PosProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'price', 'stock', 'main_image'])
            ->map(fn (Product $product) => [
                'id'        => $product->id,
                'name'      => $product->title,
                'price'     => (float) $product->price,
                'stock'     => (int) $product->stock,
                'image_url' => $this->imageUrl($product->main_image),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }

    /**
     * Construire une URL absolue pour l'image principale
     * (les chemins relatifs pointent vers le disque public).
     */
    protected function imageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}

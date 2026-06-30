<?php

namespace App\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Ai\ProductAiService;
use App\Jobs\AI\GenerateProductDescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductAiController extends Controller
{
    public function generateDescription(Request $request, int $id)
    {
        $product = Product::findOrFail($id);
        
        // Ownership check
        if ($product->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        GenerateProductDescription::dispatch($product)->onQueue('ai');

        return response()->json(['queued' => true, 'message' => 'Génération de la description en cours...']);
    }

    public function suggestPrice(int $id, ProductAiService $service)
    {
        $product = Product::findOrFail($id);
        
        if ($product->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $suggestion = $service->suggestPrice($product);
        return response()->json($suggestion);
    }

    public function analyzeSales(int $id, ProductAiService $service)
    {
        $product = Product::findOrFail($id);
        
        if ($product->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $analysis = $service->analyzeSales(auth()->user(), ['product_id' => $id]);
        return response()->json($analysis);
    }
}

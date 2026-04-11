<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * Upload multiple images for a product
     */
    public function upload(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $uploaded = [];
        
        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('products', 'public');
            
            $productImage = $product->images()->create([
                'image_path' => $path,
                'order' => $product->images()->count() + $index,
                'is_main' => $product->images()->count() === 0, // Première image = main
            ]);
            
            $uploaded[] = $productImage;
        }

        return back()->with('success', count($uploaded) . ' image(s) ajoutée(s) avec succès');
    }

    /**
     * Set an image as main
     */
    public function setMain(Product $product, ProductImage $image)
    {
        // Vérifier que l'image appartient au produit
        if ($image->product_id !== $product->id) {
            return back()->with('error', 'Image non trouvée');
        }

        // Retirer main des autres images
        $product->images()->update(['is_main' => false]);
        
        // Définir nouvelle image principale
        $image->update(['is_main' => true]);

        return back()->with('success', 'Image principale mise à jour');
    }

    /**
     * Reorder images
     */
    public function reorder(Request $request, Product $product)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:product_images,id',
        ]);

        foreach ($request->order as $index => $imageId) {
            ProductImage::where('id', $imageId)
                ->where('product_id', $product->id)
                ->update(['order' => $index]);
        }

        return response()->json(['success' => true, 'message' => 'Ordre mis à jour']);
    }

    /**
     * Delete an image
     */
    public function destroy(Product $product, ProductImage $image)
    {
        // Vérifier que l'image appartient au produit
        if ($image->product_id !== $product->id) {
            return back()->with('error', 'Image non trouvée');
        }

        // Supprimer le fichier physique
        Storage::disk('public')->delete($image->image_path);
        
        // Supprimer de la base de données
        $image->delete();

        return back()->with('success', 'Image supprimée avec succès');
    }
}

<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Modules\ERP\Models\ErpStock;
use Modules\ERP\Models\ErpStockMovement;
use Modules\ERP\Http\Requests\StoreStockAdjustmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur de gestion des stocks ERP
 * 
 * Gère l'affichage, la recherche et les ajustements de stock.
 * 
 * @package Modules\ERP\Http\Controllers
 */
class ErpStockController extends Controller
{
    /**
     * Affiche la liste des stocks (produits)
     * 
     * Permet de rechercher et filtrer les produits par statut de stock.
     * 
     * @param Request $request Requête HTTP avec paramètres de recherche/filtre
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhereHas('erpDetails', function($subQ) use ($request) {
                      $subQ->where('sku', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Accepter à la fois 'status' et 'filter' pour compatibilité
        $status = $request->input('status') ?? $request->input('filter');
        
        if ($status) {
            if ($status === 'low') {
                $query->where('stock', '<', 5)->where('stock', '>', 0);
            } elseif ($status === 'out') {
                $query->where('stock', '<=', 0);
            } elseif ($status === 'ok') {
                $query->where('stock', '>=', 5);
            }
        }

        $products = $query->orderBy('stock', 'asc')->paginate(20);

        $stats = [
            'total' => Product::count(),
            'low' => Product::where('stock', '<', 5)->where('stock', '>', 0)->count(),
            'out' => Product::where('stock', '<=', 0)->count(),
            'ok' => Product::where('stock', '>=', 5)->count(),
        ];

        return view('erp::stocks.index', compact('products', 'stats'));
    }

    /**
     * Affiche les mouvements de stock
     * 
     * Liste tous les mouvements de stock (entrées, sorties, transferts).
     * 
     * @param Request $request Requête HTTP avec paramètres de filtrage
     * @return \Illuminate\View\View
     */
    public function movements(Request $request)
    {
        $query = ErpStockMovement::with(['stockable', 'user']);

        // Filtre par date de début
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filtre par date de fin
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filtre par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(30);

        return view('erp::stocks.movements', compact('movements'));
    }

    /**
     * Exporte les mouvements de stock en Excel
     * 
     * Génère un fichier Excel avec les mouvements de stock filtrés.
     * 
     * @param Request $request Requête avec filtres (date_from, date_to, type)
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportMovements(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'type']);
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \Modules\ERP\Exports\StockMovementsExport($filters),
            'mouvements_stock_' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Affiche le formulaire d'ajustement de stock
     * 
     * @param Product $product Produit à ajuster
     * @return \Illuminate\View\View
     */
    public function adjust(Product $product)
    {
        return view('erp::stocks.adjust', compact('product'));
    }

    /**
     * Enregistre un ajustement de stock
     * 
     * Crée un mouvement de stock et met à jour la quantité disponible.
     * Vérifie que le stock est suffisant pour les sorties.
     * 
     * @param StoreStockAdjustmentRequest $request Données validées
     * @param Product $product Produit à ajuster
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeAdjustment(StoreStockAdjustmentRequest $request, Product $product)
    {
        $validated = $request->validated();

        // Vérification stock insuffisant pour sortie
        if ($validated['type'] === 'out' && $product->stock < $validated['quantity']) {
            return back()->withErrors(['quantity' => 'Stock insuffisant pour cette sortie. Stock actuel : ' . $product->stock]);
        }

        DB::transaction(function () use ($validated, $product) {
            // 1. Créer le mouvement
            ErpStockMovement::create([
                'stockable_type' => Product::class,
                'stockable_id' => $product->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
                'user_id' => Auth::id(),
                'from_location' => $validated['type'] === 'out' ? 'Entrepôt Principal' : 'Ajustement',
                'to_location' => $validated['type'] === 'in' ? 'Entrepôt Principal' : 'Ajustement',
                'reference_type' => 'manual_adjustment',
                'reference_id' => null, // Pas de commande liée
            ]);

            // 2. Mettre à jour le stock produit
            if ($validated['type'] === 'in') {
                $product->increment('stock', $validated['quantity']);
            } else {
                $product->decrement('stock', $validated['quantity']);
            }
        });

        return redirect()->route('erp.stocks.index')
            ->with('success', 'Stock ajusté avec succès !');
    }
}

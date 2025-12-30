<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CreatorOrderController extends Controller
{
    /**
     * Afficher la liste des commandes du créateur.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Récupérer les commandes qui contiennent au moins un produit du créateur
        $query = Order::whereHas('items.product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->with(['items.product' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }])
        ->with('user');
        
        // Filtre par statut si fourni
        if ($request->has('status') && $request->get('status') !== 'all') {
            $query->where('status', $request->get('status'));
        }
        
        $orders = $query->latest()
            ->paginate(15);
        
        // Calculer les totaux pour chaque commande (uniquement les produits du créateur)
        foreach ($orders as $order) {
            $order->creator_total = $order->items
                ->filter(function ($item) use ($user) {
                    return $item->product && $item->product->user_id === $user->id;
                })
                ->sum(function ($item) {
                    return $item->price * $item->quantity;
                });
        }
        
        // Optimisation : Utiliser une seule requête avec groupBy au lieu de 5 requêtes séparées
        $orderStats = Order::whereHas('items.product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->selectRaw('COUNT(*) as total, 
                         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid,
                         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as shipped,
                         SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed',
                ['pending', 'paid', 'shipped', 'completed'])
            ->first();
        
        $stats = [
            'total' => $orderStats->total ?? 0,
            'pending' => $orderStats->pending ?? 0,
            'paid' => $orderStats->paid ?? 0,
            'shipped' => $orderStats->shipped ?? 0,
            'completed' => $orderStats->completed ?? 0,
        ];
        
        return view('creator.orders.index', compact('orders', 'stats'));
    }

    /**
     * Afficher le détail d'une commande.
     * 
     * @param Order $order La commande à afficher
     * @return View|RedirectResponse Vue du détail ou redirection si non autorisé
     */
    public function show(Order $order): View|RedirectResponse
    {
        $this->authorize('view', $order);
        
        $user = Auth::user();
        
        // Charger uniquement les items du créateur
        $order->load(['items.product' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }, 'user']);
        
        // Filtrer les items pour ne garder que ceux du créateur
        $creatorItems = $order->items->filter(function ($item) use ($user) {
            return $item->product && $item->product->user_id === $user->id;
        });
        
        // Calculer le total pour le créateur
        $creatorTotal = $creatorItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });
        
        // Statuts possibles pour mise à jour (créateur ne peut pas modifier payment_status)
        $availableStatuses = [
            'pending' => 'En attente',
            'in_production' => 'En production',
            'ready_to_ship' => 'Prêt à expédier',
            'shipped' => 'Expédié',
            'completed' => 'Terminé',
        ];
        
        return view('creator.orders.show', compact('order', 'creatorItems', 'creatorTotal', 'availableStatuses'));
    }

    /**
     * Mettre à jour le statut d'une commande.
     * 
     * @param Request $request Requête avec le nouveau statut
     * @param Order $order La commande à mettre à jour
     * @return RedirectResponse Redirection avec message de succès
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('updateStatus', $order);
        
        $user = Auth::user();
        
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_production,ready_to_ship,shipped,completed'],
        ]);
        
        // Le créateur ne peut pas modifier payment_status, seulement status
        $order->update([
            'status' => $validated['status']
        ]);
        
        return redirect()->route('creator.orders.show', $order)
            ->with('success', 'Statut de la commande mis à jour avec succès.');
    }
}



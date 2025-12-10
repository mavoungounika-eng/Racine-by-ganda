<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CreatorFinanceController extends Controller
{
    /**
     * Taux de commission RACINE (configurable, par défaut 20%).
     */
    private const COMMISSION_RATE = 0.20; // 20%
    
    /**
     * Afficher la vue finances du créateur.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Période : par défaut, toutes les commandes livrées
        $period = $request->get('period', 'all');
        
        // Construire la requête de base pour les OrderItem du créateur
        $baseQuery = OrderItem::whereHas('product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->whereHas('order', function ($q) {
            $q->where('status', 'completed') // Seulement les commandes livrées
              ->where('payment_status', 'paid'); // Seulement les commandes payées
        });
        
        // Appliquer le filtre de période
        if ($period === 'month') {
            $baseQuery->whereHas('order', function ($q) {
                $q->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
            });
        } elseif ($period === 'year') {
            $baseQuery->whereHas('order', function ($q) {
                $q->whereYear('created_at', now()->year);
            });
        }
        
        // Calculer le chiffre d'affaires brut
        $grossRevenue = $baseQuery->sum(DB::raw('price * quantity'));
        
        // Calculer la commission RACINE
        $commission = $grossRevenue * self::COMMISSION_RATE;
        
        // Calculer le net créateur
        $netRevenue = $grossRevenue - $commission;
        
        // Récupérer les dernières commandes payées
        $recentPaidOrders = Order::whereHas('items.product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('status', 'completed')
        ->where('payment_status', 'paid')
        ->with(['items.product' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }])
        ->latest()
        ->take(10)
        ->get();
        
        // Calculer le montant net par commande
        foreach ($recentPaidOrders as $order) {
            $orderCreatorTotal = $order->items
                ->filter(function ($item) use ($user) {
                    return $item->product && $item->product->user_id === $user->id;
                })
                ->sum(function ($item) {
                    return $item->price * $item->quantity;
                });
            
            $order->creator_gross = $orderCreatorTotal;
            $order->creator_commission = $orderCreatorTotal * self::COMMISSION_RATE;
            $order->creator_net = $orderCreatorTotal - ($orderCreatorTotal * self::COMMISSION_RATE);
        }
        
        // Statistiques globales (toutes périodes)
        $allTimeStats = [
            'gross' => OrderItem::whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('order', function ($q) {
                $q->where('status', 'completed')
                  ->where('payment_status', 'paid');
            })
            ->sum(DB::raw('price * quantity')),
        ];
        
        $allTimeStats['commission'] = $allTimeStats['gross'] * self::COMMISSION_RATE;
        $allTimeStats['net'] = $allTimeStats['gross'] - $allTimeStats['commission'];
        
        return view('creator.finances.index', compact(
            'grossRevenue',
            'commission',
            'netRevenue',
            'recentPaidOrders',
            'allTimeStats',
            'period'
        ));
    }
}



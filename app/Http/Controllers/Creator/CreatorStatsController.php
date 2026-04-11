<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class CreatorStatsController extends Controller
{
    /**
     * Afficher les statistiques avancées du créateur.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Récupérer la période
        $period = $request->get('period', '30d');
        $dateRange = $this->getDateRange($period);
        
        // 1. Série temporelle des ventes
        $salesTimeSeries = $this->getSalesTimeSeries($user->id, $dateRange);
        
        // 2. Top produits
        $topProducts = $this->getTopProducts($user->id, $dateRange);
        
        // 3. Répartition statuts de commandes
        $orderStatusDistribution = $this->getOrderStatusDistribution($user->id, $dateRange);
        
        // 4. Comparatif période précédente
        $previousDateRange = $this->getPreviousDateRange($dateRange);
        $comparison = $this->getPeriodComparison($user->id, $dateRange, $previousDateRange);
        
        // 5. Résumé période actuelle
        $summary = [
            'current' => [
                'gross' => $this->calculateGrossRevenue($user->id, $dateRange),
                'orders_count' => $this->countOrders($user->id, $dateRange),
                'products_sold' => $this->countProductsSold($user->id, $dateRange),
            ],
            'previous' => [
                'gross' => $this->calculateGrossRevenue($user->id, $previousDateRange),
                'orders_count' => $this->countOrders($user->id, $previousDateRange),
                'products_sold' => $this->countProductsSold($user->id, $previousDateRange),
            ],
        ];
        
        // Calculer les évolutions en %
        $summary['evolution'] = [
            'gross_percent' => $this->calculateEvolutionPercent($summary['current']['gross'], $summary['previous']['gross']),
            'orders_percent' => $this->calculateEvolutionPercent($summary['current']['orders_count'], $summary['previous']['orders_count']),
            'products_percent' => $this->calculateEvolutionPercent($summary['current']['products_sold'], $summary['previous']['products_sold']),
        ];
        
        return view('creator.stats.index', compact(
            'period',
            'dateRange',
            'salesTimeSeries',
            'topProducts',
            'orderStatusDistribution',
            'summary'
        ));
    }
    
    /**
     * Obtenir la plage de dates selon la période.
     */
    private function getDateRange(string $period): array
    {
        $end = Carbon::now();
        
        switch ($period) {
            case '7d':
                $start = $end->copy()->subDays(7);
                break;
            case '30d':
                $start = $end->copy()->subDays(30);
                break;
            case 'month':
                $start = $end->copy()->startOfMonth();
                break;
            case 'year':
                $start = $end->copy()->startOfYear();
                break;
            case 'custom':
                // À implémenter si nécessaire
                $start = $end->copy()->subDays(30);
                break;
            default:
                $start = $end->copy()->subDays(30);
        }
        
        return ['start' => $start, 'end' => $end];
    }
    
    /**
     * Obtenir la plage de dates précédente.
     */
    private function getPreviousDateRange(array $currentRange): array
    {
        $daysDiff = $currentRange['start']->diffInDays($currentRange['end']);
        $end = $currentRange['start']->copy()->subDay();
        $start = $end->copy()->subDays($daysDiff);
        
        return ['start' => $start, 'end' => $end];
    }
    
    /**
     * Série temporelle des ventes.
     */
    private function getSalesTimeSeries(int $userId, array $dateRange): array
    {
        $daysDiff = $dateRange['start']->diffInDays($dateRange['end']);
        
        // Grouper par jour si < 30 jours, sinon par semaine
        $groupBy = $daysDiff <= 30 ? 'day' : 'week';
        
        $sales = OrderItem::whereHas('product', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereHas('order', function ($q) use ($dateRange) {
            $q->where('status', 'completed')
              ->where('payment_status', 'paid')
              ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })
        ->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(price * quantity) as total')
        )
        ->groupBy('date')
        ->orderBy('date')
        ->get();
        
        $labels = [];
        $values = [];
        
        // Remplir les jours manquants avec 0
        $current = $dateRange['start']->copy();
        while ($current <= $dateRange['end']) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $current->format('d/m');
            $sale = $sales->firstWhere('date', $dateStr);
            $values[] = $sale ? (float) $sale->total : 0;
            $current->addDay();
        }
        
        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
    
    /**
     * Top produits par CA.
     */
    private function getTopProducts(int $userId, array $dateRange, int $limit = 5): array
    {
        return OrderItem::whereHas('product', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereHas('order', function ($q) use ($dateRange) {
            $q->where('status', 'completed')
              ->where('payment_status', 'paid')
              ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })
        ->select(
            'product_id',
            DB::raw('SUM(price * quantity) as revenue'),
            DB::raw('SUM(quantity) as quantity_sold')
        )
        ->groupBy('product_id')
        ->with('product')
        ->orderByDesc('revenue')
        ->take($limit)
        ->get()
        ->map(function ($item) {
            return [
                'name' => $item->product->title ?? 'Produit supprimé',
                'revenue' => (float) $item->revenue,
                'quantity' => (int) $item->quantity_sold,
            ];
        })
        ->toArray();
    }
    
    /**
     * Répartition des statuts de commandes.
     */
    private function getOrderStatusDistribution(int $userId, array $dateRange): array
    {
        $distribution = Order::whereHas('items.product', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->select('status', DB::raw('COUNT(*) as count'))
        ->groupBy('status')
        ->get()
        ->pluck('count', 'status')
        ->toArray();
        
        // Statuts possibles
        $statuses = ['pending', 'paid', 'in_production', 'ready_to_ship', 'shipped', 'completed', 'cancelled'];
        $result = [];
        
        foreach ($statuses as $status) {
            $result[$status] = $distribution[$status] ?? 0;
        }
        
        return $result;
    }
    
    /**
     * Comparaison entre deux périodes.
     */
    private function getPeriodComparison(int $userId, array $currentRange, array $previousRange): array
    {
        $current = $this->calculateGrossRevenue($userId, $currentRange);
        $previous = $this->calculateGrossRevenue($userId, $previousRange);
        
        return [
            'current' => $current,
            'previous' => $previous,
            'evolution_percent' => $this->calculateEvolutionPercent($current, $previous),
        ];
    }
    
    /**
     * Calculer le CA brut.
     */
    private function calculateGrossRevenue(int $userId, array $dateRange): float
    {
        return (float) OrderItem::whereHas('product', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereHas('order', function ($q) use ($dateRange) {
            $q->where('status', 'completed')
              ->where('payment_status', 'paid')
              ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })
        ->sum(DB::raw('price * quantity'));
    }
    
    /**
     * Compter les commandes.
     */
    private function countOrders(int $userId, array $dateRange): int
    {
        return Order::whereHas('items.product', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->distinct('id')
        ->count('id');
    }
    
    /**
     * Compter les produits vendus.
     */
    private function countProductsSold(int $userId, array $dateRange): int
    {
        return (int) OrderItem::whereHas('product', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereHas('order', function ($q) use ($dateRange) {
            $q->where('status', 'completed')
              ->where('payment_status', 'paid')
              ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })
        ->sum('quantity');
    }
    
    /**
     * Calculer le pourcentage d'évolution.
     */
    private function calculateEvolutionPercent(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
}



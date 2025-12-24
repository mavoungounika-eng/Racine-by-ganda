<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CreatorFinanceDashboardController extends Controller
{
    /**
     * Frais de service plateforme RACINE (5%).
     */
    private const SERVICE_FEE_RATE = 0.05;
    
    /**
     * Taux de TVA au Congo (18%).
     */
    private const VAT_RATE = 0.18;

    /**
     * Affiche le dashboard financier du créateur.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $profile = $user->creatorProfile;
        $stripeAccount = $profile->stripeAccount;

        // Période sélectionnée
        $period = $request->get('period', 'month');

        // Statistiques globales
        $stats = $this->calculateStats($user, $period);

        // Dernières commandes payées
        $recentOrders = $this->getRecentOrders($user);

        // Données pour le graphique de ventes
        $salesChartData = $this->getSalesChartData($user, $period);

        return view('creator.finance.dashboard', compact(
            'stats',
            'recentOrders',
            'salesChartData',
            'period',
            'stripeAccount'
        ));
    }

    /**
     * Calcule les statistiques financières.
     */
    private function calculateStats($user, $period): array
    {
        $query = OrderItem::whereHas('product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->whereHas('order', function ($q) {
            $q->where('status', 'completed')
              ->where('payment_status', 'paid');
        });

        // Appliquer le filtre de période
        if ($period === 'week') {
            $query->whereHas('order', function ($q) {
                $q->where('created_at', '>=', now()->subWeek());
            });
        } elseif ($period === 'month') {
            $query->whereHas('order', function ($q) {
                $q->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
            });
        } elseif ($period === 'year') {
            $query->whereHas('order', function ($q) {
                $q->whereYear('created_at', now()->year);
            });
        }

        // Prix HT des produits vendus
        $productPriceHT = $query->sum(DB::raw('price * quantity'));
        
        // Frais de service (5%)
        $serviceFee = $productPriceHT * self::SERVICE_FEE_RATE;
        
        // Sous-total HT (prix produit + frais service)
        $subtotalHT = $productPriceHT + $serviceFee;
        
        // TVA (18% sur le sous-total HT)
        $vat = $subtotalHT * self::VAT_RATE;
        
        // Total TTC
        $totalTTC = $subtotalHT + $vat;
        
        // Revenu net créateur (prix HT du produit uniquement)
        $creatorRevenue = $productPriceHT;

        // Nombre de ventes
        $salesCount = $query->count();

        // Statistiques globales (toutes périodes)
        $allTimeProductPriceHT = OrderItem::whereHas('product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->whereHas('order', function ($q) {
            $q->where('status', 'completed')
              ->where('payment_status', 'paid');
        })
        ->sum(DB::raw('price * quantity'));

        $allTimeServiceFee = $allTimeProductPriceHT * self::SERVICE_FEE_RATE;
        $allTimeSubtotalHT = $allTimeProductPriceHT + $allTimeServiceFee;
        $allTimeVAT = $allTimeSubtotalHT * self::VAT_RATE;
        $allTimeTTC = $allTimeSubtotalHT + $allTimeVAT;

        return [
            'product_price_ht' => $productPriceHT,
            'service_fee' => $serviceFee,
            'subtotal_ht' => $subtotalHT,
            'vat' => $vat,
            'total_ttc' => $totalTTC,
            'creator_revenue' => $creatorRevenue,
            'sales_count' => $salesCount,
            'all_time_product_ht' => $allTimeProductPriceHT,
            'all_time_service_fee' => $allTimeServiceFee,
            'all_time_vat' => $allTimeVAT,
            'all_time_ttc' => $allTimeTTC,
        ];
    }

    /**
     * Récupère les dernières commandes payées.
     */
    private function getRecentOrders($user)
    {
        return Order::whereHas('items.product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('status', 'completed')
        ->where('payment_status', 'paid')
        ->with(['items.product' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }])
        ->latest()
        ->take(10)
        ->get()
        ->map(function ($order) use ($user) {
            $orderCreatorTotal = $order->items
                ->filter(function ($item) use ($user) {
                    return $item->product && $item->product->user_id === $user->id;
                })
                ->sum(function ($item) {
                    return $item->price * $item->quantity;
                });

            // Prix HT du produit
            $order->creator_product_ht = $orderCreatorTotal;
            
            // Frais de service (5%)
            $order->creator_service_fee = $orderCreatorTotal * self::SERVICE_FEE_RATE;
            
            // Sous-total HT
            $order->creator_subtotal_ht = $orderCreatorTotal + $order->creator_service_fee;
            
            // TVA (18%)
            $order->creator_vat = $order->creator_subtotal_ht * self::VAT_RATE;
            
            // Total TTC
            $order->creator_total_ttc = $order->creator_subtotal_ht + $order->creator_vat;
            
            // Revenu créateur (prix HT uniquement)
            $order->creator_revenue = $orderCreatorTotal;

            return $order;
        });
    }

    /**
     * Génère les données pour le graphique de ventes.
     */
    private function getSalesChartData($user, $period): array
    {
        if ($period === 'week') {
            return $this->getWeeklySalesData($user);
        } elseif ($period === 'month') {
            return $this->getMonthlySalesData($user);
        } else {
            return $this->getYearlySalesData($user);
        }
    }

    /**
     * Données de ventes hebdomadaires.
     */
    private function getWeeklySalesData($user): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = OrderItem::whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('order', function ($q) use ($date) {
                $q->whereDate('created_at', $date->toDateString())
                  ->where('status', 'completed')
                  ->where('payment_status', 'paid');
            })
            ->sum(DB::raw('price * quantity'));

            $data[] = [
                'label' => $date->format('D'),
                'value' => $revenue,
            ];
        }
        return $data;
    }

    /**
     * Données de ventes mensuelles (par jour).
     */
    private function getMonthlySalesData($user): array
    {
        $data = [];
        $daysInMonth = now()->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = now()->startOfMonth()->addDays($day - 1);
            $revenue = OrderItem::whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('order', function ($q) use ($date) {
                $q->whereDate('created_at', $date->toDateString())
                  ->where('status', 'completed')
                  ->where('payment_status', 'paid');
            })
            ->sum(DB::raw('price * quantity'));

            $data[] = [
                'label' => $day,
                'value' => $revenue,
            ];
        }
        return $data;
    }

    /**
     * Données de ventes annuelles (par mois).
     */
    private function getYearlySalesData($user): array
    {
        $data = [];
        $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

        for ($month = 1; $month <= 12; $month++) {
            $revenue = OrderItem::whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('order', function ($q) use ($month) {
                $q->whereMonth('created_at', $month)
                  ->whereYear('created_at', now()->year)
                  ->where('status', 'completed')
                  ->where('payment_status', 'paid');
            })
            ->sum(DB::raw('price * quantity'));

            $data[] = [
                'label' => $months[$month - 1],
                'value' => $revenue,
            ];
        }
        return $data;
    }
}

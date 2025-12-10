<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminStatsController extends Controller
{
    public function index(): View
    {
        // Stats globales
        $stats = [
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_users' => User::count(),
            'total_revenue' => Payment::where('status', 'paid')->sum('amount') ?? 0,
        ];

        // Produits les plus vendus
        $topProductsData = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();

        $topProducts = collect();
        foreach ($topProductsData as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->total_sold = $item->total_sold;
                $topProducts->push($product);
            }
        }

        // Ventes par mois (12 derniers mois)
        $monthlySales = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->copy()->subMonths($i);
            $monthlySales[] = [
                'month' => $date->format('M Y'),
                'amount' => Payment::where('status', 'paid')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('amount') ?? 0,
            ];
        }

        return view('admin.stats.index', compact('stats', 'topProducts', 'monthlySales'));
    }
}

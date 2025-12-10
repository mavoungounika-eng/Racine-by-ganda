<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard Super Admin - Vue complète du système
     */
    public function superAdmin()
    {
        $stats = [
            'users_total' => User::count(),
            'users_clients' => User::where('role', 'client')->count(),
            'users_createurs' => User::where('role', 'createur')->count(),
            'users_staff' => User::where('role', 'staff')->count(),
            'users_admins' => User::whereIn('role', ['admin', 'super_admin'])->count(),
            
            'orders_total' => Order::count(),
            'orders_pending' => Order::where('status', 'pending')->count(),
            'orders_completed' => Order::where('status', 'completed')->count(),
            'orders_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            
            'products_total' => Product::count(),
            'products_active' => Product::where('is_active', true)->count(),
            'products_low_stock' => Product::where('stock', '<', 5)->where('stock', '>', 0)->count(),
            'products_out_of_stock' => Product::where('stock', '<=', 0)->count(),
        ];
        
        $recent_orders = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        $recent_users = User::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('frontend::dashboards.super-admin', compact('stats', 'recent_orders', 'recent_users'));
    }

    /**
     * Dashboard Admin - Gestion opérationnelle
     */
    public function admin()
    {
        $stats = [
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'orders_pending' => Order::where('status', 'pending')->count(),
            'orders_processing' => Order::where('status', 'processing')->count(),
            'revenue_today' => Order::whereDate('created_at', today())
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
            'revenue_month' => Order::whereMonth('created_at', now()->month)
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
            
            'products_total' => Product::count(),
            'products_low_stock' => Product::where('stock', '<', 5)->where('stock', '>', 0)->count(),
            
            'users_total' => User::count(),
            'new_clients_today' => User::where('role', 'client')
                ->whereDate('created_at', today())
                ->count(),
        ];
        
        $pending_orders = Order::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('frontend::dashboards.admin', compact('stats', 'pending_orders'));
    }

    /**
     * Dashboard Staff - Tâches opérationnelles
     */
    public function staff()
    {
        $user = Auth::user();
        
        $stats = [
            'orders_pending' => Order::where('status', 'pending')->count(),
            'orders_processing' => Order::where('status', 'processing')->count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'products_low_stock' => Product::where('stock', '<', 5)->where('stock', '>', 0)->count(),
        ];
        
        $orders_to_process = Order::with('user')
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get();

        return view('frontend::dashboards.staff', compact('stats', 'orders_to_process'));
    }

    /**
     * Dashboard Créateur - DÉSACTIVÉ
     * 
     * Cette méthode est désactivée car le dashboard créateur a été migré vers
     * /createur/dashboard (route creator.dashboard) avec le nouveau layout.
     * 
     * @deprecated Utiliser creator.dashboard à la place
     */
    // public function createur()
    // {
    //     $user = Auth::user();
    //     
    //     $stats = [
    //         'my_products' => Product::where('user_id', $user->id)->count(),
    //         'my_products_active' => Product::where('user_id', $user->id)->where('is_active', true)->count(),
    //         'my_products_low_stock' => Product::where('user_id', $user->id)
    //             ->where('stock', '<', 5)
    //             ->where('stock', '>', 0)
    //             ->count(),
    //     ];
    //     
    //     $my_products = Product::where('user_id', $user->id)
    //         ->orderBy('created_at', 'desc')
    //         ->take(6)
    //         ->get();
    //
    //     return view('frontend::dashboards.createur', compact('stats', 'my_products'));
    // }

    /**
     * Dashboard Client - Ses commandes et son compte
     */
    public function client()
    {
        $user = Auth::user();
        
        $stats = [
            'my_orders_total' => Order::where('user_id', $user->id)->count(),
            'my_orders_pending' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
            'my_orders_completed' => Order::where('user_id', $user->id)->where('status', 'completed')->count(),
            'total_spent' => Order::where('user_id', $user->id)
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
        ];
        
        $my_orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('frontend::dashboards.client', compact('stats', 'my_orders'));
    }
}

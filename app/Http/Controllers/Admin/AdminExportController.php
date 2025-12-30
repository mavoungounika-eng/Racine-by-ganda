<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrdersExport;
use App\Exports\UsersExport;
use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminExportController extends Controller
{
    /**
     * Export des commandes
     */
    public function exportOrders(Request $request)
    {
        $format = $request->input('format', 'excel'); // excel, csv, json, pdf
        
        $filters = [
            'status' => $request->input('status'),
            'payment_status' => $request->input('payment_status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
        
        $filename = 'commandes-' . now()->format('Y-m-d-His');
        
        switch ($format) {
            case 'excel':
                return Excel::download(new OrdersExport($filters), "{$filename}.xlsx");
                
            case 'csv':
                return Excel::download(new OrdersExport($filters), "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
                
            case 'json':
                return $this->exportOrdersAsJson($filters);
                
            case 'pdf':
            case 'report':
                return $this->exportOrdersReport($filters);
                
            default:
                return Excel::download(new OrdersExport($filters), "{$filename}.xlsx");
        }
    }
    
    /**
     * Export des utilisateurs
     */
    public function exportUsers(Request $request)
    {
        $format = $request->input('format', 'excel');
        
        $filters = [
            'role' => $request->input('role'),
            'status' => $request->input('status'),
        ];
        
        $filename = 'utilisateurs-' . now()->format('Y-m-d-His');
        
        switch ($format) {
            case 'excel':
                return Excel::download(new UsersExport($filters), "{$filename}.xlsx");
                
            case 'csv':
                return Excel::download(new UsersExport($filters), "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
                
            case 'json':
                return $this->exportUsersAsJson($filters);
                
            default:
                return Excel::download(new UsersExport($filters), "{$filename}.xlsx");
        }
    }
    
    /**
     * Export des produits
     */
    public function exportProducts(Request $request)
    {
        $format = $request->input('format', 'excel');
        
        $filters = [
            'category_id' => $request->input('category_id'),
            'status' => $request->input('status'),
            'stock' => $request->input('stock'), // low, out, all
        ];
        
        $filename = 'produits-' . now()->format('Y-m-d-His');
        
        switch ($format) {
            case 'excel':
                return Excel::download(new ProductsExport($filters), "{$filename}.xlsx");
                
            case 'csv':
                return Excel::download(new ProductsExport($filters), "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
                
            case 'json':
                return $this->exportProductsAsJson($filters);
                
            default:
                return Excel::download(new ProductsExport($filters), "{$filename}.xlsx");
        }
    }
    
    /**
     * Rapport financier complet
     */
    public function exportFinancialReport(Request $request)
    {
        $period = $request->input('period', 'month'); // month, year, all
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        
        // Calculer les dates
        if ($period === 'month') {
            $dateFrom = Carbon::now()->startOfMonth();
            $dateTo = Carbon::now()->endOfMonth();
        } elseif ($period === 'year') {
            $dateFrom = Carbon::now()->startOfYear();
            $dateTo = Carbon::now()->endOfYear();
        }
        
        // Statistiques financières
        $stats = [
            'total_revenue' => Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->sum('total_amount'),
            'total_orders' => Order::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'paid_orders' => Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'pending_orders' => Order::where('payment_status', 'pending')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->count(),
            'average_order_value' => Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->avg('total_amount'),
        ];
        
        $stats['by_status'] = Order::whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        
        $stats['by_payment_method'] = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');
        
        $format = $request->input('format', 'html');
        
        if ($format === 'json') {
            return Response::json([
                'period' => $period,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'statistics' => $stats,
                'generated_at' => now()->toIso8601String(),
            ], 200, [], JSON_PRETTY_PRINT);
        }
        
        // Rapport HTML
        return view('admin.reports.financial', compact('stats', 'period', 'dateFrom', 'dateTo'));
    }
    
    /**
     * Export commandes en JSON
     */
    private function exportOrdersAsJson(array $filters)
    {
        $query = Order::with(['user', 'items.product']);
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        $data = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'date' => $order->created_at->format('Y-m-d H:i:s'),
                'customer' => [
                    'name' => $order->customer_name ?? ($order->user ? $order->user->name : 'Invité'),
                    'email' => $order->customer_email ?? ($order->user ? $order->user->email : null),
                    'phone' => $order->customer_phone ?? null,
                ],
                'amount' => $order->total_amount,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'items_count' => $order->items->count(),
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_title' => $item->product->title ?? null,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->price * $item->quantity,
                    ];
                }),
            ];
        });
        
        $filename = 'commandes-' . now()->format('Y-m-d-His') . '.json';
        
        return Response::json([
            'generated_at' => now()->toIso8601String(),
            'filters' => $filters,
            'total' => $data->count(),
            'orders' => $data,
        ], 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Type' => 'application/json; charset=utf-8',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Export utilisateurs en JSON
     */
    private function exportUsersAsJson(array $filters)
    {
        $query = User::query();
        
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        $users = $query->orderBy('created_at', 'desc')->get();
        
        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->getRoleSlug(),
                'status' => $user->status,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : null,
            ];
        });
        
        $filename = 'utilisateurs-' . now()->format('Y-m-d-His') . '.json';
        
        return Response::json([
            'generated_at' => now()->toIso8601String(),
            'filters' => $filters,
            'total' => $data->count(),
            'users' => $data,
        ], 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Type' => 'application/json; charset=utf-8',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Export produits en JSON
     */
    private function exportProductsAsJson(array $filters)
    {
        $query = Product::with('category');
        
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        if (!empty($filters['stock'])) {
            if ($filters['stock'] === 'low') {
                $query->where('stock', '<', 10)->where('stock', '>', 0);
            } elseif ($filters['stock'] === 'out') {
                $query->where('stock', '<=', 0);
            }
        }
        
        $products = $query->orderBy('created_at', 'desc')->get();
        
        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'category' => $product->category->name ?? null,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => $product->is_active,
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
            ];
        });
        
        $filename = 'produits-' . now()->format('Y-m-d-His') . '.json';
        
        return Response::json([
            'generated_at' => now()->toIso8601String(),
            'filters' => $filters,
            'total' => $data->count(),
            'products' => $data,
        ], 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Type' => 'application/json; charset=utf-8',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Rapport HTML des commandes
     */
    private function exportOrdersReport(array $filters)
    {
        $query = Order::with(['user', 'items.product']);
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        $totalRevenue = $orders->where('payment_status', 'paid')->sum('total_amount');
        $totalOrders = $orders->count();
        
        return view('admin.reports.orders', compact('orders', 'filters', 'totalRevenue', 'totalOrders'));
    }
}


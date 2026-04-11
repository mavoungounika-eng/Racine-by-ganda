<?php

namespace App\Http\Controllers\Creator;

use App\Exports\CreatorOrdersExport;
use App\Exports\CreatorProductsExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreatorExportController extends Controller
{
    /**
     * Export des commandes du créateur
     */
    public function exportOrders(Request $request)
    {
        $user = Auth::user();
        $format = $request->input('format', 'excel');
        
        $filters = [
            'status' => $request->input('status'),
            'payment_status' => $request->input('payment_status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
        
        $filename = 'mes-commandes-' . now()->format('Y-m-d-His');
        
        switch ($format) {
            case 'excel':
                return Excel::download(new CreatorOrdersExport($filters, $user->id), "{$filename}.xlsx");
                
            case 'csv':
                return Excel::download(new CreatorOrdersExport($filters, $user->id), "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
                
            case 'json':
                return $this->exportOrdersAsJson($filters, $user);
                
            default:
                return Excel::download(new CreatorOrdersExport($filters, $user->id), "{$filename}.xlsx");
        }
    }
    
    /**
     * Export des produits du créateur
     */
    public function exportProducts(Request $request)
    {
        $user = Auth::user();
        $format = $request->input('format', 'excel');
        
        $filters = [
            'category_id' => $request->input('category_id'),
            'status' => $request->input('status'),
            'stock' => $request->input('stock'),
        ];
        
        $filename = 'mes-produits-' . now()->format('Y-m-d-His');
        
        switch ($format) {
            case 'excel':
                return Excel::download(new CreatorProductsExport($filters, $user->id), "{$filename}.xlsx");
                
            case 'csv':
                return Excel::download(new CreatorProductsExport($filters, $user->id), "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
                
            case 'json':
                return $this->exportProductsAsJson($filters, $user);
                
            default:
                return Excel::download(new CreatorProductsExport($filters, $user->id), "{$filename}.xlsx");
        }
    }
    
    /**
     * Export rapport financier créateur
     */
    public function exportFinancialReport(Request $request)
    {
        $user = Auth::user();
        $period = $request->input('period', 'all'); // all, month, year
        
        $baseQuery = OrderItem::whereHas('product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->whereHas('order', function ($q) {
            $q->where('status', 'completed')
              ->where('payment_status', 'paid');
        });
        
        // Appliquer le filtre de période
        if ($period === 'month') {
            $baseQuery->whereHas('order', function ($q) {
                $q->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
            });
            $dateFrom = Carbon::now()->startOfMonth();
            $dateTo = Carbon::now()->endOfMonth();
        } elseif ($period === 'year') {
            $baseQuery->whereHas('order', function ($q) {
                $q->whereYear('created_at', now()->year);
            });
            $dateFrom = Carbon::now()->startOfYear();
            $dateTo = Carbon::now()->endOfYear();
        } else {
            $dateFrom = null;
            $dateTo = Carbon::now();
        }
        
        $grossRevenue = $baseQuery->sum(DB::raw('price * quantity'));
        $commission = $grossRevenue * 0.20;
        $netRevenue = $grossRevenue - $commission;
        
        // Récupérer les commandes
        $recentPaidOrders = Order::whereHas('items.product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('status', 'completed')
        ->where('payment_status', 'paid')
        ->with(['items.product' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }])
        ->latest()
        ->take(20)
        ->get();
        
        // Calculer les totaux par commande
        foreach ($recentPaidOrders as $order) {
            $orderCreatorTotal = $order->items
                ->filter(function ($item) use ($user) {
                    return $item->product && $item->product->user_id === $user->id;
                })
                ->sum(function ($item) {
                    return $item->price * $item->quantity;
                });
            
            $order->creator_gross = $orderCreatorTotal;
            $order->creator_commission = $orderCreatorTotal * 0.20;
            $order->creator_net = $orderCreatorTotal - ($orderCreatorTotal * 0.20);
        }
        
        $format = $request->input('format', 'html');
        
        if ($format === 'json') {
            return Response::json([
                'period' => $period,
                'date_from' => $dateFrom ? $dateFrom->format('Y-m-d') : null,
                'date_to' => $dateTo->format('Y-m-d'),
                'gross_revenue' => $grossRevenue,
                'commission' => $commission,
                'net_revenue' => $netRevenue,
                'commission_rate' => '20%',
                'recent_orders' => $recentPaidOrders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'date' => $order->created_at->format('Y-m-d H:i:s'),
                        'gross' => $order->creator_gross,
                        'commission' => $order->creator_commission,
                        'net' => $order->creator_net,
                    ];
                }),
                'generated_at' => now()->toIso8601String(),
            ], 200, [], JSON_PRETTY_PRINT);
        }
        
        // Rapport HTML
        return view('creator.reports.financial', compact(
            'grossRevenue',
            'commission',
            'netRevenue',
            'recentPaidOrders',
            'period',
            'dateFrom',
            'dateTo'
        ));
    }
    
    /**
     * Export commandes en JSON
     */
    private function exportOrdersAsJson(array $filters, $user)
    {
        $query = Order::whereHas('items.product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->with(['items.product' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }])
        ->with('user');
        
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
        
        $orders = $query->latest()->get();
        
        $data = $orders->map(function ($order) use ($user) {
            $creatorItems = $order->items->filter(function ($item) use ($user) {
                return $item->product && $item->product->user_id === $user->id;
            });
            
            $creatorTotal = $creatorItems->sum(function ($item) {
                return $item->price * $item->quantity;
            });
            
            $commission = $creatorTotal * 0.20;
            $net = $creatorTotal - $commission;
            
            return [
                'id' => $order->id,
                'date' => $order->created_at->format('Y-m-d H:i:s'),
                'customer' => [
                    'name' => $order->customer_name ?? ($order->user ? $order->user->name : 'Invité'),
                    'email' => $order->customer_email ?? ($order->user ? $order->user->email : null),
                ],
                'items' => $creatorItems->map(function ($item) {
                    return [
                        'product_title' => $item->product->title ?? null,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->price * $item->quantity,
                    ];
                }),
                'gross_revenue' => $creatorTotal,
                'commission' => $commission,
                'net_revenue' => $net,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
            ];
        });
        
        $filename = 'mes-commandes-' . now()->format('Y-m-d-His') . '.json';
        
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
     * Export produits en JSON
     */
    private function exportProductsAsJson(array $filters, $user)
    {
        $query = Product::where('user_id', $user->id)
            ->with('category');
        
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
            $sales = OrderItem::where('product_id', $product->id)
                ->whereHas('order', function ($q) {
                    $q->where('status', 'completed')
                      ->where('payment_status', 'paid');
                })
                ->sum('quantity');
            
            return [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'category' => $product->category->name ?? null,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => $product->is_active,
                'sales' => $sales,
                'created_at' => $product->created_at->format('Y-m-d H:i:s'),
            ];
        });
        
        $filename = 'mes-produits-' . now()->format('Y-m-d-His') . '.json';
        
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
}


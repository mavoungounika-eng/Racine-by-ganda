<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Models\ErpPurchaseItem;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\ERP\Models\ErpStockMovement;
use Modules\ERP\Models\ErpSupplier;
use Modules\ERP\Services\StockAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;

class ErpReportController extends Controller
{
    /**
     * Rapport de valorisation du stock
     */
    public function stockValuationReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'format' => 'nullable|in:html,json',
            ]);
            $format = $validated['format'] ?? 'html';
            
            // Valorisation produits finis
            $productsValuation = Product::where('stock', '>', 0)
                ->select('id', 'title', 'price', 'stock', DB::raw('price * stock as total_value'))
                ->orderByDesc('total_value')
                ->get();

            $totalProductsValue = $productsValuation->sum('total_value');

            // Valorisation matières premières - OPTIMISÉ : 3 requêtes au lieu de N×3
            // 1. Récupérer tous les mouvements en une fois
            $stockMovements = ErpStockMovement::where('stockable_type', ErpRawMaterial::class)
                ->selectRaw('stockable_id, type, SUM(quantity) as total')
                ->groupBy('stockable_id', 'type')
                ->get()
                ->groupBy('stockable_id')
                ->map(function ($movements) {
                    return [
                        'in' => $movements->where('type', 'in')->sum('total') ?? 0,
                        'out' => $movements->where('type', 'out')->sum('total') ?? 0,
                    ];
                });

            // 2. Récupérer tous les prix moyens en une fois
            $avgPrices = ErpPurchaseItem::where('purchasable_type', ErpRawMaterial::class)
                ->selectRaw('purchasable_id, AVG(unit_price) as avg_price')
                ->groupBy('purchasable_id')
                ->pluck('avg_price', 'purchasable_id');

            // 3. Calculer la valorisation avec les données pré-chargées
            $materialsValuation = ErpRawMaterial::all()->map(function ($material) use ($stockMovements, $avgPrices) {
                $movements = $stockMovements->get($material->id, ['in' => 0, 'out' => 0]);
                $currentStock = max(0, $movements['in'] - $movements['out']);
                $avgPrice = $avgPrices[$material->id] ?? 0;
                
                return [
                    'material' => $material,
                    'stock' => $currentStock,
                    'avg_price' => $avgPrice,
                    'total_value' => $currentStock * $avgPrice,
                ];
            })
            ->filter(function ($item) {
                return $item['stock'] > 0;
            })
            ->sortByDesc('total_value');

            $totalMaterialsValue = $materialsValuation->sum('total_value');
            $totalStockValue = $totalProductsValue + $totalMaterialsValue;

        if ($format === 'json') {
            return Response::json([
                'report_date' => now()->toIso8601String(),
                'products' => [
                    'items' => $productsValuation->map(function ($p) {
                        return [
                            'id' => $p->id,
                            'title' => $p->title,
                            'stock' => $p->stock,
                            'unit_price' => $p->price,
                            'total_value' => $p->total_value,
                        ];
                    }),
                    'total_value' => $totalProductsValue,
                    'count' => $productsValuation->count(),
                ],
                'materials' => [
                    'items' => $materialsValuation->values(),
                    'total_value' => $totalMaterialsValue,
                    'count' => $materialsValuation->count(),
                ],
                'grand_total' => $totalStockValue,
            ], 200, [], JSON_PRETTY_PRINT);
        }

            // Format HTML
            return view('erp::reports.stock-valuation', compact(
                'productsValuation',
                'materialsValuation',
                'totalProductsValue',
                'totalMaterialsValue',
                'totalStockValue'
            ));
        } catch (\Exception $e) {
            \Log::error('Erreur rapport valorisation stock', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            if ($format === 'json') {
                return response()->json(['error' => 'Erreur lors de la génération du rapport'], 500);
            }
            
            return redirect()->route('erp.dashboard')
                ->with('error', 'Erreur lors de la génération du rapport de valorisation');
        }
    }

    /**
     * Rapport d'achats
     */
    public function purchasesReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'format' => 'nullable|in:html,json',
                'period' => 'nullable|in:month,year,all',
                'date_from' => 'nullable|date|before_or_equal:today',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);
            
            $format = $validated['format'] ?? 'html';
            $period = $validated['period'] ?? 'month';
            
            $query = ErpPurchase::with(['supplier', 'items.purchasable']);

            if ($period === 'month') {
                $query->whereMonth('purchase_date', now()->month)
                      ->whereYear('purchase_date', now()->year);
                $dateFrom = Carbon::now()->startOfMonth();
                $dateTo = Carbon::now()->endOfMonth();
            } elseif ($period === 'year') {
                $query->whereYear('purchase_date', now()->year);
                $dateFrom = Carbon::now()->startOfYear();
                $dateTo = Carbon::now()->endOfYear();
            } else {
                $dateFrom = Carbon::parse($validated['date_from'] ?? Carbon::now()->subMonth());
                $dateTo = Carbon::parse($validated['date_to'] ?? Carbon::now());
                $query->whereBetween('purchase_date', [$dateFrom, $dateTo]);
            }

        $purchases = $query->orderBy('purchase_date', 'desc')->get();

        $stats = [
            'total_purchases' => $purchases->count(),
            'total_amount' => $purchases->sum('total_amount'),
            'by_status' => $purchases->groupBy('status')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('total_amount'),
                ];
            }),
            'by_supplier' => $purchases->groupBy('supplier_id')->map(function ($group) {
                $supplier = $group->first()->supplier;
                return [
                    'supplier' => $supplier ? $supplier->name : 'Inconnu',
                    'count' => $group->count(),
                    'total' => $group->sum('total_amount'),
                ];
            })->sortByDesc('total')->take(10),
        ];

        if ($format === 'json') {
            return Response::json([
                'period' => $period,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'statistics' => $stats,
                'purchases' => $purchases->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'reference' => $p->reference,
                        'supplier' => $p->supplier ? $p->supplier->name : null,
                        'date' => $p->purchase_date->format('Y-m-d'),
                        'status' => $p->status,
                        'total_amount' => $p->total_amount,
                        'items_count' => $p->items->count(),
                    ];
                }),
                'generated_at' => now()->toIso8601String(),
            ], 200, [], JSON_PRETTY_PRINT);
        }

            return view('erp::reports.purchases', compact('purchases', 'stats', 'period', 'dateFrom', 'dateTo'));
        } catch (\Exception $e) {
            \Log::error('Erreur rapport achats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            if ($format === 'json') {
                return response()->json(['error' => 'Erreur lors de la génération du rapport'], 500);
            }
            
            return redirect()->route('erp.dashboard')
                ->with('error', 'Erreur lors de la génération du rapport d\'achats');
        }
    }

    /**
     * Rapport des mouvements de stock
     */
    public function stockMovementsReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'format' => 'nullable|in:html,json',
                'period' => 'nullable|in:7d,30d,month,year',
                'type' => 'nullable|in:in,out',
            ]);
            
            $format = $validated['format'] ?? 'html';
            $period = $validated['period'] ?? '30d';
            
            $query = ErpStockMovement::with(['stockable', 'user']);

            // Période
            if ($period === '7d') {
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
            } elseif ($period === '30d') {
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
            } elseif ($period === 'month') {
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
            } elseif ($period === 'year') {
                $query->whereYear('created_at', now()->year);
            }

            // Type de mouvement
            if (!empty($validated['type'])) {
                $query->where('type', $validated['type']);
            }

        $movements = $query->orderBy('created_at', 'desc')->paginate(50);

        $stats = [
            'total_in' => ErpStockMovement::where('type', 'in')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->sum('quantity'),
            'total_out' => ErpStockMovement::where('type', 'out')
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->sum('quantity'),
            'by_reason' => ErpStockMovement::where('created_at', '>=', Carbon::now()->subDays(30))
                ->select('reason', DB::raw('COUNT(*) as count'), DB::raw('SUM(quantity) as total_qty'))
                ->groupBy('reason')
                ->get()
                ->keyBy('reason'),
        ];

        if ($format === 'json') {
            return Response::json([
                'period' => $period,
                'statistics' => $stats,
                'movements' => $movements->items(),
                'generated_at' => now()->toIso8601String(),
            ], 200, [], JSON_PRETTY_PRINT);
        }

            return view('erp::reports.stock-movements', compact('movements', 'stats', 'period'));
        } catch (\Exception $e) {
            \Log::error('Erreur rapport mouvements stock', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            if ($format === 'json') {
                return response()->json(['error' => 'Erreur lors de la génération du rapport'], 500);
            }
            
            return redirect()->route('erp.dashboard')
                ->with('error', 'Erreur lors de la génération du rapport des mouvements');
        }
    }

    /**
     * Suggestions de réapprovisionnement
     */
    public function replenishmentSuggestions()
    {
        try {
            $service = app(StockAlertService::class);
            $suggestions = $service->getReplenishmentSuggestions(10);

            // Grouper par urgence
            $grouped = collect($suggestions)->groupBy('urgency');

            return view('erp::reports.replenishment-suggestions', compact('suggestions', 'grouped'));
        } catch (\Exception $e) {
            \Log::error('Erreur suggestions réapprovisionnement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('erp.dashboard')
                ->with('error', 'Erreur lors de la génération des suggestions');
        }
    }
}


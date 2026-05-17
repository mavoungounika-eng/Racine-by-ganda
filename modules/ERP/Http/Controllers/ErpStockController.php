<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\ErpStock;
use Modules\ERP\Models\ErpStockMovement;
use Modules\ERP\Http\Requests\StoreStockAdjustmentRequest;

class ErpStockController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhereHas('erpDetails', function ($subQ) use ($request) {
                      $subQ->where('sku', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $status = $request->input('status') ?? $request->input('filter');

        if ($status) {
            if ($status === 'low')      $query->where('stock', '<', 5)->where('stock', '>', 0);
            elseif ($status === 'out')  $query->where('stock', '<=', 0);
            elseif ($status === 'ok')   $query->where('stock', '>=', 5);
        }

        $products = $query->orderBy('stock', 'asc')->paginate(20);

        $stats = Cache::remember('erp_stocks_stats', 300, function () {
            $result = DB::selectOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN stock < 5 AND stock > 0 THEN 1 ELSE 0 END) as low,
                    SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN stock >= 5 THEN 1 ELSE 0 END) as ok
                FROM products
            ");

            return [
                'total' => (int) ($result->total ?? 0),
                'low'   => (int) ($result->low ?? 0),
                'out'   => (int) ($result->out_of_stock ?? 0),
                'ok'    => (int) ($result->ok ?? 0),
            ];
        });

        return view('erp::stocks.index', compact('products', 'stats'));
    }

    public function dataStocks(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhereHas('erpDetails', function ($subQ) use ($s) {
                      $subQ->where('sku', 'like', "%{$s}%");
                  });
            });
        }

        $status = $request->get('status');
        if ($status) {
            if ($status === 'low')      $query->where('stock', '<', 5)->where('stock', '>', 0);
            elseif ($status === 'out')  $query->where('stock', '<=', 0);
            elseif ($status === 'ok')   $query->where('stock', '>=', 5);
        }

        $allowed = ['title', 'price', 'stock', 'created_at'];
        $sortBy  = in_array($request->get('sort_by'), $allowed, true) ? $request->get('sort_by') : 'stock';
        $sortDir = $request->get('sort_dir') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDir);

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function exportCsv(Request $request): Response
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%");
            });
        }

        $status = $request->get('status');
        if ($status) {
            if ($status === 'low')      $query->where('stock', '<', 5)->where('stock', '>', 0);
            elseif ($status === 'out')  $query->where('stock', '<=', 0);
            elseif ($status === 'ok')   $query->where('stock', '>=', 5);
        }

        $rows = $query->orderBy('stock', 'asc')->get();
        $csv  = "\xEF\xBB\xBF";
        $csv .= "ID;Produit;Stock;Prix;Statut stock\n";
        foreach ($rows as $r) {
            if ($r->stock <= 0)      $stockStatus = 'rupture';
            elseif ($r->stock < 5)   $stockStatus = 'faible';
            else                     $stockStatus = 'ok';

            $csv .= implode(';', [
                $r->id,
                '"'.str_replace('"', '\"\"', $r->title).'"',
                $r->stock ?? 0,
                $r->price ?? '',
                $stockStatus,
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="stocks_'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function movements(Request $request)
    {
        $query = ErpStockMovement::with(['stockable', 'user']);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(30);

        return view('erp::stocks.movements', compact('movements'));
    }

    public function exportMovements(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'type']);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \Modules\ERP\Exports\StockMovementsExport($filters),
            'mouvements_stock_' . date('Y-m-d') . '.xlsx'
        );
    }

    public function adjust(Product $product)
    {
        return view('erp::stocks.adjust', compact('product'));
    }

    public function storeAdjustment(StoreStockAdjustmentRequest $request, Product $product)
    {
        $validated = $request->validated();

        if ($validated['type'] === 'out' && $product->stock < $validated['quantity']) {
            return back()->withErrors(['quantity' => 'Stock insuffisant pour cette sortie. Stock actuel : ' . $product->stock]);
        }

        DB::transaction(function () use ($validated, $product) {
            ErpStockMovement::create([
                'stockable_type'  => Product::class,
                'stockable_id'    => $product->id,
                'type'            => $validated['type'],
                'quantity'        => $validated['quantity'],
                'reason'          => $validated['reason'],
                'user_id'         => Auth::id(),
                'from_location'   => $validated['type'] === 'out' ? 'Entrepôt Principal' : 'Ajustement',
                'to_location'     => $validated['type'] === 'in'  ? 'Entrepôt Principal' : 'Ajustement',
                'reference_type'  => 'manual_adjustment',
                'reference_id'    => null,
            ]);

            if ($validated['type'] === 'in') {
                $product->increment('stock', $validated['quantity']);
            } else {
                $product->decrement('stock', $validated['quantity']);
            }

            $quantityChange = $validated['type'] === 'in'
                ? $validated['quantity']
                : -$validated['quantity'];

            app(AuditService::class)->logStockAdjustment(
                productId: $product->id,
                quantityChange: $quantityChange,
                reason: $validated['reason'],
                user: Auth::user(),
                additionalData: [
                    'stock_type'  => $validated['type'],
                    'product_sku' => $product->erpDetails?->sku,
                ]
            );
        });

        return redirect()->route('erp.stocks.index')
            ->with('success', 'Stock ajusté avec succès !');
    }
}

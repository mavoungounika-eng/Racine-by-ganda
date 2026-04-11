<?php

namespace App\Http\Controllers\Admin;

use App\Models\StockAlert;
use Illuminate\Http\Request;

class AdminStockAlertController extends AdminController
{
    /**
     * Afficher la liste des alertes de stock.
     */
    public function index(Request $request)
    {
        $query = StockAlert::with(['product', 'resolver']);

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%');
            });
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'active' => StockAlert::where('status', 'active')->count(),
            'resolved' => StockAlert::where('status', 'resolved')->count(),
            'dismissed' => StockAlert::where('status', 'dismissed')->count(),
            'total' => StockAlert::count(),
        ];

        return view('admin.stock-alerts.index', compact('alerts', 'stats'));
    }

    /**
     * Résoudre une alerte.
     */
    public function resolve(StockAlert $alert)
    {
        $alert->resolve();

        return redirect()->route('admin.stock-alerts.index')
            ->with('success', 'Alerte résolue avec succès.');
    }

    /**
     * Ignorer une alerte.
     */
    public function dismiss(StockAlert $alert)
    {
        $alert->dismiss();

        return redirect()->route('admin.stock-alerts.index')
            ->with('success', 'Alerte ignorée.');
    }

    /**
     * Résoudre toutes les alertes actives.
     */
    public function resolveAll()
    {
        $count = StockAlert::where('status', 'active')
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

        return redirect()->route('admin.stock-alerts.index')
            ->with('success', "{$count} alertes résolues avec succès.");
    }
}


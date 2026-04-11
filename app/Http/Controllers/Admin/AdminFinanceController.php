<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CreatorSaleRecord;
use App\Models\Payment;
use Illuminate\View\View;

/**
 * Contrôleur financier Admin (Modèle SaaS Pur).
 * 
 * Affiche les revenus propres à RACINE et le volume d'affaires analytique 
 * généré par les créateurs sans gestion de fonds tiers.
 */
class AdminFinanceController extends Controller
{
    public function index(): View
    {
        $stats = [
            // Revenus encaissés par RACINE (Abonnements + Ventes propres)
            'total_racine_revenue' => Payment::where('status', 'paid')->sum('amount') ?? 0,
            
            // Volume d'affaires généré par les créateurs (Analytique uniquement)
            'creator_gross_volume' => CreatorSaleRecord::sum('gross_amount') ?? 0,
            
            // Nombre de ventes créateurs validées
            'creator_sales_count' => CreatorSaleRecord::count(),
            
            // Revenus RACINE du mois en cours
            'monthly_racine_revenue' => Payment::where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount') ?? 0,
        ];

        $recentPayments = Payment::with(['order' => function($query) {
                $query->with('user');
            }])
            ->where('status', 'paid')
            ->latest()
            ->take(10)
            ->get();

        // Top ventes créateurs récentes (Analytique)
        $recentCreatorSales = CreatorSaleRecord::with(['creator', 'order'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.finances.index', compact('stats', 'recentPayments', 'recentCreatorSales'));
    }
}

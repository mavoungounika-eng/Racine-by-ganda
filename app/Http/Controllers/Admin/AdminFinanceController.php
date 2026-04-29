<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CreatorSaleRecord;
use App\Models\Payment;
use App\Models\CreatorPayout;
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
        // Calcul des revenus totaux RACINE (paiements encaissés)
        $totalRevenue = Payment::where('status', 'paid')->sum('amount') ?? 0;
        
        // Calcul des revenus RACINE du mois en cours
        $monthlyRevenue = Payment::where('status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount') ?? 0;
        
        // Calcul des payouts en attente (à envoyer aux créateurs)
        $pendingPayoutsAmount = CreatorPayout::where('status', 'pending')->sum('amount') ?? 0;
        
        // Calcul des commissions payées
        $paidCommissions = CreatorPayout::where('status', 'completed')->sum('amount') ?? 0;

        $stats = [
            // Revenus encaissés par RACINE (Abonnements + Ventes propres)
            'total_revenue' => $totalRevenue,
            
            // Revenus RACINE du mois en cours
            'monthly_revenue' => $monthlyRevenue,
            
            // Payouts en attente vers les créateurs
            'pending_payouts' => $pendingPayoutsAmount,
            
            // Commissions totales payées
            'paid_commissions' => $paidCommissions,
            
            // Volume d'affaires généré par les créateurs (Analytique uniquement)
            'creator_gross_volume' => CreatorSaleRecord::sum('gross_amount') ?? 0,
            
            // Nombre de ventes créateurs validées
            'creator_sales_count' => CreatorSaleRecord::count(),
        ];

        $recentPayments = Payment::with(['order' => function($query) {
                $query->with('user');
            }])
            ->where('status', 'paid')
            ->latest()
            ->take(10)
            ->get();

        // Payouts en attente (avec relation creatorProfile)
        $pendingPayouts = CreatorPayout::where('status', 'pending')
            ->with('creatorProfile.user')
            ->latest()
            ->take(10)
            ->get();

        // Top ventes créateurs récentes (Analytique)
        $recentCreatorSales = CreatorSaleRecord::with(['creator', 'order'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.finances.index', compact('stats', 'recentPayments', 'pendingPayouts', 'recentCreatorSales'));
    }
}

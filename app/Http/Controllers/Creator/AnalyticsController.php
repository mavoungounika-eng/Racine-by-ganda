<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôleur Analytics pour les créateurs
 * 
 * Phase 4 : Structure préparée pour les statistiques créateur
 * 
 * TODO : Implémenter les méthodes pour afficher :
 * - CA du créateur
 * - Nombre de commandes contenant ses produits
 * - Top de ses produits
 * - Évolution dans le temps
 */
class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Dashboard Analytics créateur
     * 
     * TODO Phase 4 : Implémenter la vue avec les statistiques du créateur
     */
    public function index(Request $request)
    {
        $creatorId = Auth::id();
        
        // Par défaut : 30 derniers jours
        $endDate = now();
        $startDate = now()->subDays(30);

        // TODO : Récupérer les statistiques du créateur
        // $stats = $this->analyticsService->getCreatorStats($creatorId, $startDate, $endDate);

        // Pour l'instant, retourner un stub
        return view('creator.analytics.index', [
            'creator_id' => $creatorId,
            'note' => 'Dashboard créateur à implémenter',
        ]);
    }

    /**
     * Statistiques de ventes du créateur
     * 
     * TODO Phase 4 : Implémenter
     */
    public function sales(Request $request)
    {
        $creatorId = Auth::id();
        
        // TODO : Implémenter les statistiques de ventes pour le créateur
        // - Filtrer les commandes contenant ses produits
        // - Calculer le CA
        // - Top produits
        
        return view('creator.analytics.sales', [
            'creator_id' => $creatorId,
            'note' => 'Statistiques ventes créateur à implémenter',
        ]);
    }
}


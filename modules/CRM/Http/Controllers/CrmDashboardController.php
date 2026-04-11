<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\CrmContact;
use Modules\CRM\Models\CrmOpportunity;
use Modules\CRM\Models\CrmInteraction;
use Illuminate\Support\Facades\DB;

class CrmDashboardController extends Controller
{
    public function index()
    {
        // 1. Pipeline Value (Somme des opportunités non perdues/gagnées ou juste ouvertes ?)
        // "Valeur totale pipeline" implique souvent tout ce qui est en cours.
        $pipeline_value = CrmOpportunity::whereNotIn('stage', ['won', 'lost'])->sum('value');

        // 2. Opportunités Gagnées/Perdues (Mois en cours)
        $won_month = CrmOpportunity::where('stage', 'won')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $lost_month = CrmOpportunity::where('stage', 'lost')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // 3. Top Clients (Basé sur la valeur des opportunités gagnées)
        $top_clients = CrmContact::where('type', 'client')
            ->withSum(['opportunities as total_won_value' => function ($query) {
                $query->where('stage', 'won');
            }], 'value')
            ->orderByDesc('total_won_value')
            ->take(5)
            ->get();

        $stats = [
            'contacts_total' => CrmContact::count(),
            'contacts_leads' => CrmContact::where('type', 'lead')->count(),
            'contacts_clients' => CrmContact::where('type', 'client')->count(),
            'contacts_partners' => CrmContact::where('type', 'partner')->count(),
            
            'opportunities_total' => CrmOpportunity::count(),
            'opportunities_open' => CrmOpportunity::whereNotIn('stage', ['won', 'lost'])->count(),
            'opportunities_won_total' => CrmOpportunity::where('stage', 'won')->count(),
            'opportunities_won_month' => $won_month,
            'opportunities_lost_month' => $lost_month,
            
            'pipeline_value' => $pipeline_value,
            
            'interactions_today' => CrmInteraction::whereDate('created_at', today())->count(),
        ];

        // Activités récentes (Interactions)
        $recent_activities = CrmInteraction::with(['contact', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // Opportunités actives (pour la liste)
        $active_opportunities = CrmOpportunity::with('contact')
            ->whereNotIn('stage', ['won', 'lost'])
            ->orderBy('expected_close_date', 'asc')
            ->take(5)
            ->get();

        return view('crm::dashboard', compact('stats', 'recent_activities', 'active_opportunities', 'top_clients'));
    }
}

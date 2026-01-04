<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Afficher le dashboard admin
     */
    public function index()
    {
        try {
            $data = $this->dashboardService->getData();

            return view('admin.dashboard.index', $data);
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return view('admin.dashboard.index', [
                'error' => 'Impossible de charger le dashboard. Veuillez réessayer.',
                'global_state' => [],
                'alerts' => [],
                'commercial_activity' => [],
                'marketplace' => [],
                'operations' => [],
                'trends' => [],
                'last_updated' => now()->format('H:i'),
            ]);
        }
    }

    /**
     * Rafraîchir le cache du dashboard
     */
    public function refresh()
    {
        try {
            $this->dashboardService->refresh();

            return redirect()->route('admin.dashboard')
                ->with('success', 'Dashboard rafraîchi avec succès');
        } catch (\Exception $e) {
            Log::error('Dashboard refresh error: ' . $e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Erreur lors du rafraîchissement');
        }
    }
}

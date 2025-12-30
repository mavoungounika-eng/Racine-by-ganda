<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\MonetbilCallbackEvent;
use App\Models\PaymentProvider;
use App\Models\PaymentTransaction;
use App\Models\StripeWebhookEvent;
use App\Services\Payments\WebhookObservabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentHubController extends Controller
{
    /**
     * Dashboard Payments Hub - Vue d'ensemble
     *
     * @return View
     */
    public function index(): View
    {
        $this->authorize('payments.view');

        // KPIs - Source of truth : payment_transactions
        $kpis = $this->calculateKPIs();

        // Santé providers
        $providers = PaymentProvider::orderBy('priority')->get();

        // Derniers événements (Stripe + Monetbil)
        $recentEvents = $this->getRecentEvents();

        // Observabilité webhooks
        $observabilityService = new WebhookObservabilityService();
        $webhookMetrics = $observabilityService->getSummary([
            'window_minutes' => 60,
            'threshold_minutes' => config('payments.webhooks.stuck_requeue_minutes', 10),
        ]);

        return view('admin.payments.index', compact('kpis', 'providers', 'recentEvents', 'webhookMetrics'));
    }

    /**
     * Calculer les KPIs
     *
     * @return array
     */
    private function calculateKPIs(): array
    {
        // Total transactions
        $total = PaymentTransaction::count();

        // Transactions réussies
        $succeeded = PaymentTransaction::where('status', 'succeeded')->count();

        // Transactions échouées
        $failed = PaymentTransaction::where('status', 'failed')->count();

        // Transactions en attente
        $pending = PaymentTransaction::whereIn('status', ['pending', 'processing'])->count();

        // Taux de succès
        $successRate = $total > 0 ? round(($succeeded / $total) * 100, 2) : 0;

        // Montant total (source of truth : payment_transactions)
        $totalAmount = PaymentTransaction::where('status', 'succeeded')->sum('amount');

        // Panier moyen (si order liée)
        $avgCart = PaymentTransaction::where('status', 'succeeded')
            ->whereNotNull('order_id')
            ->selectRaw('AVG(amount) as avg_amount')
            ->value('avg_amount') ?? 0;

        return [
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $successRate,
            'total_amount' => $totalAmount,
            'avg_cart' => $avgCart,
        ];
    }

    /**
     * Récupérer les derniers événements (Stripe + Monetbil)
     *
     * @return array
     */
    private function getRecentEvents(): array
    {
        // Derniers événements Stripe
        $stripeEvents = StripeWebhookEvent::orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'provider' => 'stripe',
                    'event_id' => $event->event_id,
                    'event_type' => $event->event_type,
                    'status' => $event->status,
                    'created_at' => $event->created_at,
                ];
            });

        // Derniers événements Monetbil
        $monetbilEvents = MonetbilCallbackEvent::orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'provider' => 'monetbil',
                    'event_key' => $event->event_key,
                    'event_type' => $event->event_type,
                    'status' => $event->status,
                    'created_at' => $event->created_at,
                ];
            });

        // Fusionner et trier par date
        $allEvents = $stripeEvents->concat($monetbilEvents)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        return $allEvents->toArray();
    }
}





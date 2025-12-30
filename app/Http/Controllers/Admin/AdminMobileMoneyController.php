<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorSubscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestion des transactions Mobile Money (Admin)
 */
class AdminMobileMoneyController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Liste des transactions Mobile Money.
     */
    public function index(Request $request): View
    {
        // Pour l'instant, on filtre les abonnements sans stripe_subscription_id
        // (ce sont ceux payés via Mobile Money)
        $query = CreatorSubscription::with(['creatorProfile.user', 'plan'])
            ->whereNull('stripe_subscription_id');

        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('provider')) {
            // TODO: Ajouter un champ provider dans la table subscriptions
            // Pour l'instant, on ne peut pas filtrer par provider
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('creatorProfile.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest()->paginate(20);

        // Statistiques
        $stats = [
            'total' => CreatorSubscription::whereNull('stripe_subscription_id')->count(),
            'active' => CreatorSubscription::whereNull('stripe_subscription_id')
                ->where('status', 'active')->count(),
            'total_amount' => CreatorSubscription::whereNull('stripe_subscription_id')
                ->where('status', 'active')
                ->join('creator_plans', 'creator_subscriptions.creator_plan_id', '=', 'creator_plans.id')
                ->sum('creator_plans.price'),
        ];

        return view('admin.payments.mobile-money.index', compact('transactions', 'stats'));
    }

    /**
     * Détails d'une transaction Mobile Money.
     */
    public function show(CreatorSubscription $subscription): View
    {
        $subscription->load(['creatorProfile.user', 'plan']);

        return view('admin.payments.mobile-money.show', compact('subscription'));
    }

    /**
     * Valider manuellement une transaction.
     */
    public function validate(CreatorSubscription $subscription)
    {
        if ($subscription->status === 'active') {
            return redirect()->back()
                ->with('info', 'Cette transaction est déjà validée.');
        }

        $subscription->update([
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Mettre à jour les capacités
        app(\App\Services\CreatorCapabilityService::class)
            ->clearCache($subscription->creatorProfile->user_id);

        return redirect()->back()
            ->with('success', 'Transaction validée avec succès !');
    }

    /**
     * Rejeter une transaction.
     */
    public function reject(CreatorSubscription $subscription)
    {
        $subscription->update(['status' => 'canceled']);

        return redirect()->back()
            ->with('success', 'Transaction rejetée.');
    }
}

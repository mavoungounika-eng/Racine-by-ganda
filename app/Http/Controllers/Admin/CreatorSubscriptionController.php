<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorSubscription;
use App\Models\CreatorPlan;
use App\Models\User;
use App\Services\CreatorCapabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * CreatorSubscriptionController (Admin)
 * 
 * PHASE 10: Gestion admin des abonnements créateurs
 */
class CreatorSubscriptionController extends Controller
{
    protected CreatorCapabilityService $capabilityService;

    public function __construct(CreatorCapabilityService $capabilityService)
    {
        $this->middleware('admin');
        $this->capabilityService = $capabilityService;
    }

    /**
     * Liste des créateurs avec leurs abonnements.
     */
    public function index(Request $request): View
    {
        $query = User::whereHas('roleRelation', function ($q) {
            $q->whereIn('slug', ['createur', 'creator']);
        })->with(['creatorProfile', 'activeSubscription.plan']);

        // Filtre par plan
        if ($request->has('plan')) {
            $planId = $request->get('plan');
            $query->whereHas('activeSubscription', function ($q) use ($planId) {
                $q->where('creator_plan_id', $planId);
            });
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('creatorProfile', function ($q) use ($search) {
                      $q->where('brand_name', 'like', "%{$search}%");
                  });
            });
        }

        $creators = $query->paginate(20);
        $plans = CreatorPlan::active()->get();

        // Statistiques
        $stats = [
            'total_creators' => User::whereHas('roleRelation', function ($q) {
                $q->whereIn('slug', ['createur', 'creator']);
            })->count(),
            'with_subscription' => CreatorSubscription::where('status', 'active')->distinct('creator_id')->count(),
            'free_plan' => CreatorSubscription::whereHas('plan', function ($q) {
                $q->where('code', 'free');
            })->where('status', 'active')->count(),
            'paid_plans' => CreatorSubscription::whereHas('plan', function ($q) {
                $q->where('code', '!=', 'free');
            })->where('status', 'active')->count(),
        ];

        return view('admin.creator-subscriptions.index', compact('creators', 'plans', 'stats'));
    }

    /**
     * Détails d'un créateur et son abonnement.
     */
    public function show(User $creator): View
    {
        $creator->load(['creatorProfile', 'activeSubscription.plan', 'activeSubscription.plan.capabilities']);
        $subscription = $creator->activeSubscription();
        $plan = $creator->activePlan();
        $capabilities = $creator->capabilities();
        $allPlans = CreatorPlan::active()->get();

        return view('admin.creator-subscriptions.show', compact(
            'creator',
            'subscription',
            'plan',
            'capabilities',
            'allPlans'
        ));
    }

    /**
     * Changer manuellement le plan d'un créateur.
     */
    public function updatePlan(Request $request, User $creator): RedirectResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:creator_plans,id',
        ]);

        $plan = CreatorPlan::findOrFail($request->plan_id);

        // Créer ou mettre à jour l'abonnement
        $subscription = CreatorSubscription::updateOrCreate(
            [
                'creator_id' => $creator->id,
            ],
            [
                'creator_profile_id' => $creator->creatorProfile->id ?? null,
                'creator_plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => now(),
                'ends_at' => $plan->code === 'free' ? null : now()->addMonth(), // 1 mois pour les plans payants
            ]
        );

        // Invalider le cache
        $this->capabilityService->clearCache($creator);

        return redirect()->route('admin.creator-subscriptions.show', $creator)
            ->with('success', "Plan changé vers '{$plan->name}' avec succès !");
    }

    /**
     * Audit des capabilities d'un créateur.
     */
    public function audit(User $creator): View
    {
        $plan = $creator->activePlan();
        $capabilities = $plan->capabilities ?? collect();
        $activeCapabilities = $creator->capabilities();

        return view('admin.creator-subscriptions.audit', compact(
            'creator',
            'plan',
            'capabilities',
            'activeCapabilities'
        ));
    }
}

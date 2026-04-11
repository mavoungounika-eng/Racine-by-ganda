<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

/**
 * Gestion des plans d'abonnement créateur (Admin)
 */
class AdminCreatorPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Liste des plans d'abonnement.
     */
    public function index(): View
    {
        $plans = CreatorPlan::orderBy('price', 'asc')->get();
        
        return view('admin.subscriptions.plans.index', compact('plans'));
    }

    /**
     * Formulaire de création d'un plan.
     */
    public function create(): View
    {
        return view('admin.subscriptions.plans.create');
    }

    /**
     * Enregistrement d'un nouveau plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:creator_plans,code',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'stripe_product_id' => 'nullable|string',
            'stripe_price_id' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $plan = CreatorPlan::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'price' => $request->price,
            'features' => $request->features ?? [],
            'stripe_product_id' => $request->stripe_product_id,
            'stripe_price_id' => $request->stripe_price_id,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.subscriptions.plans.index')
            ->with('success', "Plan '{$plan->name}' créé avec succès !");
    }

    /**
     * Formulaire d'édition d'un plan.
     */
    public function edit(CreatorPlan $plan): View
    {
        return view('admin.subscriptions.plans.edit', compact('plan'));
    }

    /**
     * Mise à jour d'un plan.
     */
    public function update(Request $request, CreatorPlan $plan): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:creator_plans,code,' . $plan->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'stripe_product_id' => 'nullable|string',
            'stripe_price_id' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $plan->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'price' => $request->price,
            'features' => $request->features ?? [],
            'stripe_product_id' => $request->stripe_product_id,
            'stripe_price_id' => $request->stripe_price_id,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.subscriptions.plans.index')
            ->with('success', "Plan '{$plan->name}' mis à jour avec succès !");
    }

    /**
     * Suppression d'un plan.
     */
    public function destroy(CreatorPlan $plan): RedirectResponse
    {
        // Vérifier qu'aucun créateur n'utilise ce plan
        $activeSubscriptions = $plan->subscriptions()->where('status', 'active')->count();
        
        if ($activeSubscriptions > 0) {
            return redirect()->back()
                ->with('error', "Impossible de supprimer ce plan : {$activeSubscriptions} abonnement(s) actif(s).");
        }

        $planName = $plan->name;
        $plan->delete();

        return redirect()->route('admin.subscriptions.plans.index')
            ->with('success', "Plan '{$planName}' supprimé avec succès !");
    }

    /**
     * Activer/Désactiver un plan.
     */
    public function toggleActive(CreatorPlan $plan): RedirectResponse
    {
        $plan->update(['is_active' => !$plan->is_active]);
        
        $status = $plan->is_active ? 'activé' : 'désactivé';
        
        return redirect()->back()
            ->with('success', "Plan '{$plan->name}' {$status} avec succès !");
    }
}

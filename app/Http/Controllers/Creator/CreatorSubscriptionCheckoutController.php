<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\CreatorPlan;
use App\Services\CreatorSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CreatorSubscriptionCheckoutController extends Controller
{
    protected CreatorSubscriptionService $subscriptionService;

    public function __construct(CreatorSubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Affiche les plans disponibles.
     */
    public function selectPlan(): View
    {
        $user = Auth::user();
        $currentSubscription = $user->creatorProfile->subscriptions()->where('status', 'active')->first();
        $plans = CreatorPlan::active()->get();

        return view('creator.subscriptions.plans', compact('plans', 'currentSubscription'));
    }

    /**
     * Redirige vers Stripe Checkout pour un plan donné.
     */
    public function checkout(CreatorPlan $plan): RedirectResponse
    {
        $user = Auth::user();

        try {
            $checkoutUrl = $this->subscriptionService->createCheckoutSession($user, $plan);
            return redirect()->away($checkoutUrl);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la session Checkout : ' . $e->getMessage());
            return redirect()->route('creator.subscription.plans')
                ->with('error', 'Impossible de créer la session de paiement. Veuillez réessayer plus tard.');
        }
    }

    /**
     * Gère le retour après paiement réussi.
     */
    public function success(Request $request, CreatorPlan $plan): View
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('creator.dashboard')
                ->with('warning', 'Session de paiement introuvable.');
        }

        // Le webhook Stripe s'occupera de créer/mettre à jour l'abonnement
        // Afficher la page de confirmation avec les détails du plan
        return view('creator.subscriptions.success', compact('plan'));
    }

    /**
     * Gère l'annulation du paiement.
     */
    public function cancel(CreatorPlan $plan): RedirectResponse
    {
        return redirect()->route('creator.subscription.plans')
            ->with('info', 'Vous avez annulé le paiement. Vous pouvez réessayer à tout moment.');
    }

    /**
     * Affiche le formulaire de paiement Mobile Money.
     */
    public function checkoutMomo(CreatorPlan $plan): View
    {
        return view('creator.subscriptions.checkout-momo', compact('plan'));
    }

    /**
     * Traite le paiement Mobile Money.
     */
    public function processMomoPayment(Request $request, CreatorPlan $plan): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:orange,mtn,moov,wave',
            'phone' => 'required|string|regex:/^[0-9]{10}$/',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = Auth::user();
        $provider = $request->input('provider');
        $phone = $request->input('phone');

        try {
            // TODO: Intégrer l'API de paiement Mobile Money (Monetbil, etc.)
            // Pour l'instant, on simule un paiement réussi
            
            Log::info('Paiement Mobile Money initié', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'provider' => $provider,
                'phone' => $phone,
                'amount' => $plan->price,
            ]);

            // Créer l'abonnement manuellement (en attendant l'intégration de l'API)
            $subscription = $user->creatorProfile->subscriptions()->create([
                'creator_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'stripe_subscription_id' => null, // Pas de Stripe pour MoMo
                'stripe_customer_id' => null,
                'stripe_price_id' => null,
            ]);

            // Mettre à jour les capacités du créateur
            app(\App\Services\CreatorCapabilityService::class)->clearCache($user->id);

            return redirect()->route('creator.subscription.checkout.success', $plan)
                ->with('session_id', 'momo_' . $subscription->id);
        } catch (\Exception $e) {
            Log::error('Erreur lors du paiement Mobile Money : ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors du paiement. Veuillez réessayer.');
        }
    }
}

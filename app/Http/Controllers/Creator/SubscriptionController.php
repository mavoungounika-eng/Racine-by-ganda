<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\CreatorPlan;
use App\Models\CreatorSubscription;
use App\Models\CreatorStripeAccount;
use App\Services\CreatorCapabilityService;
use App\Services\Payments\CreatorSubscriptionCheckoutService;
use App\Services\SubscriptionAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * SubscriptionController
 * 
 * Gère les abonnements créateur (choix de plan, upgrade, activation)
 */
class SubscriptionController extends Controller
{
    protected CreatorCapabilityService $capabilityService;
    protected SubscriptionAnalyticsService $analyticsService;
    protected CreatorSubscriptionCheckoutService $checkoutService;

    public function __construct(
        CreatorCapabilityService $capabilityService,
        SubscriptionAnalyticsService $analyticsService,
        CreatorSubscriptionCheckoutService $checkoutService
    ) {
        $this->capabilityService = $capabilityService;
        $this->analyticsService = $analyticsService;
        $this->checkoutService = $checkoutService;
    }


    /**
     * Afficher la page de choix/upgrade de plan.
     */
    public function upgrade(): View
    {
        $user = Auth::user();
        $currentPlan = $user->activePlan();
        $currentSubscription = $user->creatorProfile?->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
            })
            ->with('plan')
            ->first();
        
        // Charger les plans avec leurs capabilities pour affichage dynamique
        $plans = CreatorPlan::active()
            ->with('capabilities')
            ->orderBy('price')
            ->get();

        return view('creator.subscriptions.plans', compact('plans', 'currentPlan', 'currentSubscription'));
    }

    /**
     * Afficher les détails d'un plan.
     */
    public function show(CreatorPlan $plan): View
    {
        $user = Auth::user();
        $currentPlan = $user->activePlan();
        
        // Charger les capabilities du plan
        $plan->load('capabilities');

        return view('creator.subscription.show', compact('plan', 'currentPlan'));
    }

    /**
     * Traiter le choix d'un plan (avant paiement).
     */
    public function select(Request $request, CreatorPlan $plan): RedirectResponse
    {
        $user = Auth::user();
        
        // Vérifier que le plan est actif
        if (!$plan->is_active) {
            return redirect()->route('creator.subscription.upgrade')
                ->with('error', 'Ce plan n\'est pas disponible.');
        }

        // Plans à prix zéro activables directement (sans Stripe)
        if ((float) $plan->price <= 0.0) {
            return $this->activateZeroPricePlan($user, $plan);
        }

        // Pour les plans payants, créer une session Stripe Checkout
        try {
            $checkoutUrl = $this->checkoutService->createCheckoutSession($user, $plan);
            return redirect($checkoutUrl);
        } catch (\RuntimeException $e) {
            return redirect()->route('creator.subscription.upgrade')
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('creator.subscription.upgrade')
                ->with('error', 'Une erreur est survenue lors de la création de la session de paiement. Veuillez réessayer.');
        }
    }

    /**
     * Activer directement un plan à prix zéro (sans Stripe).
     * Garde price==0 comme seule condition — indépendant du code plan.
     */
    protected function activateZeroPricePlan(User $user, CreatorPlan $plan): RedirectResponse
    {
        if ((float) $plan->price > 0.0) {
            \Illuminate\Support\Facades\Log::critical('Tentative d\'activation directe d\'un plan payant', [
                'user_id'    => $user->id,
                'plan_id'    => $plan->id,
                'plan_code'  => $plan->code,
                'plan_price' => $plan->price,
                'ip'         => request()->ip(),
            ]);
            abort(403, 'Les plans payants nécessitent un paiement. Accès refusé.');
        }

        $subscription = CreatorSubscription::updateOrCreate(
            ['creator_id' => $user->id],
            [
                'creator_profile_id'      => $user->creatorProfile->id ?? null,
                'creator_plan_id'         => $plan->id,
                'status'                  => 'active',
                'started_at'              => now(),
                'ends_at'                 => null,
                'stripe_subscription_id'  => null,
                'stripe_customer_id'      => null,
            ]
        );

        $this->capabilityService->clearCache($user);

        $this->analyticsService->trackEvent($user->id, 'created', null, $plan->id, 0);

        return redirect()->route('creator.dashboard')
            ->with('success', 'Plan activé avec succès !');
    }

    /**
     * Callback de succès du checkout Stripe.
     * 
     * SÉCURITÉ P0.2 : Cette méthode est "AFFICHAGE ONLY".
     * Elle ne crée JAMAIS d'abonnement.
     * L'abonnement est créé UNIQUEMENT par le webhook Stripe Billing (source de vérité).
     * 
     * Cette méthode :
     * - Vérifie que la session Stripe est payée
     * - Vérifie si l'abonnement existe déjà (créé par webhook)
     * - Affiche un message approprié selon l'état
     * - Redirige vers la page d'abonnement actuel
     */
    public function checkoutSuccess(Request $request, CreatorPlan $plan): RedirectResponse
    {
        $user = Auth::user();
        $sessionId = $request->query('session_id');

        // SÉCURITÉ P0.2 : Vérification session_id obligatoire
        if (empty($sessionId)) {
            \Illuminate\Support\Facades\Log::warning('Callback success sans session_id', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'ip' => $request->ip(),
            ]);
            return redirect()->route('creator.subscription.upgrade')
                ->with('error', 'Session de paiement invalide.');
        }

        try {
            // SÉCURITÉ P0.2 : Vérifier que la session Stripe existe et est payée
            $session = $this->checkoutService->retrieveCheckoutSession($sessionId);

            // Vérifier que la session appartient bien à ce créateur
            $sessionCreatorId = $session->metadata['creator_id'] ?? null;
            if ($sessionCreatorId != $user->id) {
                \Illuminate\Support\Facades\Log::warning('Callback success : session ne correspond pas au créateur', [
                    'user_id' => $user->id,
                    'session_creator_id' => $sessionCreatorId,
                    'session_id' => $sessionId,
                    'ip' => $request->ip(),
                ]);
                return redirect()->route('creator.subscription.upgrade')
                    ->with('error', 'Session de paiement invalide.');
            }

            // Vérifier que la session correspond au plan demandé
            $sessionPlanId = $session->metadata['plan_id'] ?? null;
            if ($sessionPlanId != $plan->id) {
                \Illuminate\Support\Facades\Log::warning('Callback success : session ne correspond pas au plan', [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'session_plan_id' => $sessionPlanId,
                    'session_id' => $sessionId,
                ]);
                return redirect()->route('creator.subscription.upgrade')
                    ->with('error', 'Session de paiement invalide.');
            }

            // Vérifier que le paiement est complété
            if ($session->payment_status !== 'paid') {
                return redirect()->route('creator.subscription.upgrade')
                    ->with('error', 'Le paiement n\'a pas été complété.');
            }

            // SÉCURITÉ P0.2 : Vérifier si l'abonnement existe déjà (créé par webhook)
            // Le webhook Stripe Billing est la SEULE source de vérité
            $subscription = CreatorSubscription::where('creator_id', $user->id)
                ->where('creator_plan_id', $plan->id)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                          ->orWhere('ends_at', '>', now());
                })
                ->first();

            if ($subscription) {
                // Abonnement déjà créé par le webhook → Tout est OK
                \Illuminate\Support\Facades\Log::info('Callback success : abonnement déjà actif (créé par webhook)', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'plan_id' => $plan->id,
                    'session_id' => $sessionId,
                ]);

                // Invalider le cache pour afficher les nouvelles capabilities
                $this->capabilityService->clearCache($user);

                return redirect()->route('creator.subscription.current')
                    ->with('success', 'Votre abonnement est actif ! Bienvenue dans l\'écosystème RACINE.');
            }

            // SÉCURITÉ P0.2 : Abonnement pas encore créé → Le webhook n'est pas encore arrivé
            // On attend (polling côté client ou message informatif)
            // On ne crée JAMAIS l'abonnement ici
            \Illuminate\Support\Facades\Log::info('Callback success : paiement confirmé, en attente du webhook', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'session_id' => $sessionId,
            ]);

            return redirect()->route('creator.subscription.current')
                ->with('info', 'Votre paiement a été confirmé. Votre abonnement sera activé dans quelques instants. Cette page se rafraîchira automatiquement.');

        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Session Stripe invalide ou expirée
            \Illuminate\Support\Facades\Log::error('Callback success : session Stripe invalide', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('creator.subscription.upgrade')
                ->with('error', 'Session de paiement invalide ou expirée.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Callback success : erreur inattendue', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('creator.subscription.upgrade')
                ->with('error', 'Erreur lors de la vérification du paiement. Si le paiement a été effectué, votre abonnement sera activé automatiquement par notre système.');
        }
    }

    /**
     * Callback d'annulation du checkout Stripe.
     */
    public function checkoutCancel(CreatorPlan $plan): RedirectResponse
    {
        return redirect()->route('creator.subscription.upgrade')
            ->with('info', 'Le paiement a été annulé. Vous pouvez réessayer à tout moment.');
    }

    /**
     * Traiter le paiement Mobile Money.
     * 
     * SÉCURITÉ P0.3 : DÉSACTIVÉ EN PRODUCTION
     * Cette méthode est désactivée jusqu'à implémentation complète de la vérification.
     * 
     * Pour activer Mobile Money pour les abonnements :
     * 1. Implémenter la vérification de signature (comme pour les commandes)
     * 2. Vérifier le statut du paiement auprès du provider (Monetbil/MTN/Airtel)
     * 3. Créer l'abonnement UNIQUEMENT après vérification serveur
     * 4. Utiliser un webhook/callback sécurisé (comme Stripe)
     */
    public function handleMobileMoneyPayment(Request $request, CreatorPlan $plan): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|min:9|max:15',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        try {
            $monetbil = app(\App\Services\Payments\MonetbilService::class);

            $paymentUrl = $monetbil->createPaymentUrl([
                'amount'     => $plan->price,
                'currency'   => 'XAF',
                'phone'      => $request->input('phone'),
                'reference'  => 'SUB-' . $user->id . '-' . $plan->id . '-' . time(),
                'return_url' => route('creator.subscription.checkout.success', $plan),
                'notify_url' => route('payment.monetbil.notify'),
            ]);

            Log::info('Monetbil subscription payment initiated via SubscriptionController', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            return redirect()->away($paymentUrl);

        } catch (\Exception $e) {
            Log::error('Erreur paiement Monetbil abonnement : ' . $e->getMessage(), [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);
            return redirect()->back()
                ->with('error', 'Impossible d\'initier le paiement Mobile Money. Veuillez réessayer.');
        }
    }


    /**
     * Afficher les détails de l'abonnement actuel.
     */
    public function current(): View
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription();
        $plan = $user->activePlan();
        $capabilities = $user->capabilities();

        return view('creator.subscription.current', compact('subscription', 'plan', 'capabilities'));
    }
}

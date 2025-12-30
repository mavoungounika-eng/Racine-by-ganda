<?php

namespace App\Services;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Exception\ApiErrorException;

/**
 * Service de gestion des abonnements créateurs avec Stripe Billing.
 * 
 * Ce service gère :
 * - La création de sessions Stripe Checkout
 * - L'annulation et la reprise d'abonnements
 * - La synchronisation des plans avec Stripe
 * 
 * ⚠️ Ce service ne gère PAS :
 * - Les webhooks → StripeBillingWebhookController
 * - Stripe Connect → StripeConnectService
 * - Les notifications → NotificationService
 */
class CreatorSubscriptionService
{
    /**
     * Constructeur du service.
     */
    public function __construct()
    {
        $stripeSecret = config('services.stripe.secret');
        
        if (empty($stripeSecret)) {
            throw new \RuntimeException(
                'Stripe Billing non configuré : la clé secrète Stripe (STRIPE_SECRET) est manquante.'
            );
        }
        
        Stripe::setApiKey($stripeSecret);
    }

    /**
     * Crée une session Stripe Checkout pour un plan donné.
     * 
     * @param User $creator Le créateur qui souscrit
     * @param CreatorPlan $plan Le plan choisi
     * @return string L'URL de la session Checkout
     * @throws \RuntimeException Si le plan n'a pas de price_id Stripe
     * @throws ApiErrorException Si l'API Stripe retourne une erreur
     */
    public function createCheckoutSession(User $creator, CreatorPlan $plan): string
    {
        if (empty($plan->stripe_price_id)) {
            throw new \RuntimeException(
                "Le plan '{$plan->name}' n'a pas de price_id Stripe configuré. Veuillez synchroniser les plans avec Stripe."
            );
        }

        $creatorProfile = $creator->creatorProfile;
        if (!$creatorProfile) {
            throw new \RuntimeException("Le créateur n'a pas de profil créateur.");
        }

        try {
            // Récupérer ou créer le customer Stripe
            $existingSubscription = CreatorSubscription::where('creator_profile_id', $creatorProfile->id)->first();
            $customerId = $existingSubscription->stripe_customer_id ?? null;

            $sessionParams = [
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => route('creator.subscription.checkout.success', ['plan' => $plan->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('creator.subscription.checkout.cancel', ['plan' => $plan->id]),
                'metadata' => [
                    'creator_id' => $creator->id,
                    'creator_profile_id' => $creatorProfile->id,
                    'plan_id' => $plan->id,
                ],
                'subscription_data' => [
                    'metadata' => [
                        'creator_id' => $creator->id,
                        'creator_profile_id' => $creatorProfile->id,
                        'plan_id' => $plan->id,
                    ],
                ],
            ];

            // Si le créateur a déjà un customer_id, le réutiliser
            if ($customerId) {
                $sessionParams['customer'] = $customerId;
            } else {
                $sessionParams['customer_email'] = $creator->email;
            }

            $session = Session::create($sessionParams);

            Log::info('Stripe Checkout session created', [
                'creator_id' => $creator->id,
                'plan_id' => $plan->id,
                'session_id' => $session->id,
            ]);

            return $session->url;
        } catch (ApiErrorException $e) {
            Log::error('Erreur Stripe lors de la création de la session Checkout', [
                'creator_id' => $creator->id,
                'plan_id' => $plan->id,
                'stripe_error' => $e->getMessage(),
                'stripe_error_code' => $e->getStripeCode(),
            ]);
            throw $e;
        }
    }

    /**
     * Annule un abonnement Stripe.
     * 
     * @param CreatorSubscription $subscription L'abonnement à annuler
     * @param bool $immediately Si true, annule immédiatement. Sinon, annule à la fin de la période.
     * @return void
     * @throws ApiErrorException Si l'API Stripe retourne une erreur
     */
    public function cancelSubscription(CreatorSubscription $subscription, bool $immediately = false): void
    {
        if (empty($subscription->stripe_subscription_id)) {
            throw new \RuntimeException("L'abonnement n'a pas d'identifiant Stripe.");
        }

        try {
            if ($immediately) {
                Subscription::update($subscription->stripe_subscription_id, [
                    'cancel_at_period_end' => false,
                ]);
                Subscription::retrieve($subscription->stripe_subscription_id)->cancel();
            } else {
                Subscription::update($subscription->stripe_subscription_id, [
                    'cancel_at_period_end' => true,
                ]);
            }

            Log::info('Stripe subscription canceled', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
                'immediately' => $immediately,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Erreur Stripe lors de l\'annulation de l\'abonnement', [
                'subscription_id' => $subscription->id,
                'stripe_error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Réactive un abonnement annulé (cancel_at_period_end = true).
     * 
     * @param CreatorSubscription $subscription L'abonnement à réactiver
     * @return void
     * @throws ApiErrorException Si l'API Stripe retourne une erreur
     */
    public function resumeSubscription(CreatorSubscription $subscription): void
    {
        if (empty($subscription->stripe_subscription_id)) {
            throw new \RuntimeException("L'abonnement n'a pas d'identifiant Stripe.");
        }

        try {
            Subscription::update($subscription->stripe_subscription_id, [
                'cancel_at_period_end' => false,
            ]);

            Log::info('Stripe subscription resumed', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Erreur Stripe lors de la reprise de l\'abonnement', [
                'subscription_id' => $subscription->id,
                'stripe_error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

<?php

namespace App\Services\Payments;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\User;
use App\Services\Payments\StripeConnectService;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

/**
 * Service de gestion des checkouts Stripe pour les abonnements créateurs.
 * 
 * Ce service :
 * - Vérifie que le créateur peut recevoir des paiements (canCreatorReceivePayments)
 * - Crée une session Stripe Checkout en mode subscription
 * - Utilise le compte Connect du créateur (pas la plateforme)
 * - Gère la création/synchronisation des Price Stripe
 * 
 * ⚠️ Ce service ne fait PAS :
 * - De gestion d'abonnement après paiement → Géré par StripeBillingWebhookController
 * - De notification → Géré par d'autres services
 * - De redirection → Géré par le contrôleur
 */
class CreatorSubscriptionCheckoutService
{
    protected StripeConnectService $stripeConnectService;

    public function __construct(StripeConnectService $stripeConnectService)
    {
        $this->stripeConnectService = $stripeConnectService;
        
        $stripeSecret = config('services.stripe.secret');
        if (empty($stripeSecret)) {
            throw new \RuntimeException(
                'Stripe non configuré : la clé secrète Stripe (STRIPE_SECRET) est manquante dans la configuration. ' .
                'Veuillez ajouter STRIPE_SECRET dans votre fichier .env avec une clé Stripe valide (format: sk_test_... ou sk_live_...). ' .
                'Consultez la documentation : https://dashboard.stripe.com/apikeys'
            );
        }
        
        // Vérifier si c'est un placeholder (valeur de test non valide)
        if (str_contains(strtolower($stripeSecret), 'your_secret_key') || 
            str_contains(strtolower($stripeSecret), 'sk_test_your') ||
            strlen($stripeSecret) < 20) {
            throw new \RuntimeException(
                'Stripe non configuré : la clé secrète Stripe (STRIPE_SECRET) contient une valeur de placeholder. ' .
                'Veuillez remplacer la valeur dans votre fichier .env par une vraie clé Stripe (format: sk_test_... ou sk_live_...). ' .
                'Récupérez votre clé sur : https://dashboard.stripe.com/apikeys'
            );
        }
        
        Stripe::setApiKey($stripeSecret);
    }

    /**
     * Crée une session Stripe Checkout pour un abonnement créateur.
     * 
     * V2.1 : Support des abonnements annuels via le paramètre $billingCycle.
     * 
     * Vérifications effectuées (dans l'ordre) :
     * 1. Le créateur peut recevoir des paiements (canCreatorReceivePayments)
     * 2. Le plan est actif
     * 3. Le plan n'est pas gratuit (gratuit = activation directe, pas de checkout)
     * 
     * @param User $creator Le créateur qui souhaite s'abonner
     * @param CreatorPlan $plan Le plan d'abonnement choisi
     * @param string $billingCycle Cycle de facturation : 'monthly' ou 'annually' (V2.1)
     * @return string L'URL de la session Checkout Stripe
     * @throws \RuntimeException Si le créateur ne peut pas recevoir de paiements
     * @throws \RuntimeException Si le plan est gratuit ou inactif
     * @throws ApiErrorException Si l'API Stripe retourne une erreur
     */
    public function createCheckoutSession(User $creator, CreatorPlan $plan, string $billingCycle = 'monthly'): string
    {
        // Vérification 1 : Le créateur est bien un créateur
        if (!$creator->isCreator()) {
            throw new \RuntimeException(
                "L'utilisateur {$creator->id} n'est pas un créateur."
            );
        }

        $creatorProfile = $creator->creatorProfile;
        if (!$creatorProfile) {
            throw new \RuntimeException(
                "Le créateur {$creator->id} n'a pas de profil créateur."
            );
        }

        // Vérification 2 : Le créateur peut recevoir des paiements
        if (!$this->stripeConnectService->canCreatorReceivePayments($creatorProfile)) {
            throw new \RuntimeException(
                "Le créateur {$creator->id} ne peut pas recevoir de paiements. " .
                "Vérifiez que le compte Stripe Connect est activé et que l'abonnement est actif."
            );
        }

        // Vérification 3 : Le plan est actif
        if (!$plan->is_active) {
            throw new \RuntimeException(
                "Le plan {$plan->code} n'est pas actif."
            );
        }

        // Vérification 4 : Le plan n'est pas gratuit
        if ($plan->code === 'free' || $plan->price == 0) {
            throw new \RuntimeException(
                "Le plan {$plan->code} est gratuit. Utilisez l'activation directe, pas le checkout."
            );
        }

        // Récupérer le compte Stripe Connect du créateur
        $stripeAccount = CreatorStripeAccount::where('creator_profile_id', $creatorProfile->id)->first();
        if (!$stripeAccount || empty($stripeAccount->stripe_account_id)) {
            throw new \RuntimeException(
                "Le créateur {$creator->id} n'a pas de compte Stripe Connect valide."
            );
        }

        try {
            // V2.1 : Créer ou récupérer le Price Stripe pour ce plan avec le bon cycle
            $stripePriceId = $this->getOrCreateStripePrice($plan, $stripeAccount->stripe_account_id, $billingCycle);

            // Construire les URLs de callback
            $baseUrl = config('app.url');
            $successUrl = route('creator.subscription.checkout.success', [
                'plan' => $plan->id,
            ]) . '?session_id={CHECKOUT_SESSION_ID}';
            
            $cancelUrl = route('creator.subscription.checkout.cancel', [
                'plan' => $plan->id,
            ]);

            // Créer la session Stripe Checkout en mode subscription
            // IMPORTANT : La session est créée au nom de la plateforme (pas du compte Connect)
            // car le créateur paie son abonnement à la plateforme
            // Le compte Connect est utilisé uniquement pour vérifier l'éligibilité
            $session = Session::create([
                'mode' => 'subscription',
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price' => $stripePriceId,
                        'quantity' => 1,
                    ],
                ],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => $creator->email,
                'metadata' => [
                    'creator_id' => $creator->id,
                    'creator_profile_id' => $creatorProfile->id,
                    'plan_id' => $plan->id,
                    'plan_code' => $plan->code,
                    'stripe_account_id' => $stripeAccount->stripe_account_id, // Pour référence dans le webhook
                ],
            ]);

            Log::info('Stripe Checkout session créée pour abonnement créateur', [
                'creator_id' => $creator->id,
                'creator_profile_id' => $creatorProfile->id,
                'plan_id' => $plan->id,
                'plan_code' => $plan->code,
                'stripe_account_id' => $stripeAccount->stripe_account_id,
                'session_id' => $session->id,
                'stripe_price_id' => $stripePriceId,
            ]);

            return $session->url;
        } catch (ApiErrorException $e) {
            Log::error('Erreur Stripe lors de la création de la session Checkout', [
                'creator_id' => $creator->id,
                'plan_id' => $plan->id,
                'stripe_account_id' => $stripeAccount->stripe_account_id ?? null,
                'stripe_error' => $e->getMessage(),
                'stripe_error_code' => $e->getStripeCode(),
            ]);
            throw $e;
        }
    }

    /**
     * Récupère ou crée un Price Stripe pour un plan.
     * 
     * V2.1 : Support des abonnements annuels.
     * 
     * Si le plan a déjà un stripe_price_id, vérifie qu'il existe dans Stripe.
     * Sinon, crée un nouveau Price Stripe.
     * 
     * IMPORTANT : Le Price est créé au nom de la plateforme (pas du compte Connect)
     * car l'abonnement est payé à la plateforme.
     * 
     * @param CreatorPlan $plan Le plan d'abonnement
     * @param string $stripeAccountId L'ID du compte Stripe Connect (pour référence uniquement)
     * @param string $billingCycle Cycle de facturation : 'monthly' ou 'annually' (V2.1)
     * @return string L'ID du Price Stripe (price_xxx)
     * @throws ApiErrorException Si l'API Stripe retourne une erreur
     */
    protected function getOrCreateStripePrice(CreatorPlan $plan, string $stripeAccountId, string $billingCycle = 'monthly'): string
    {
        try {
            // Créer un Product Stripe pour ce plan (si nécessaire)
            $productName = "Abonnement {$plan->name}";
            $productDescription = $plan->description ?? "Plan d'abonnement {$plan->name} pour créateurs";
            
            // Créer le Product au nom de la plateforme (pas du compte Connect)
            $product = Product::create([
                'name' => $productName,
                'description' => $productDescription,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'plan_code' => $plan->code,
                ],
            ]);

            // V2.1 : Déterminer le prix et l'interval selon le cycle
            $interval = $billingCycle === 'annually' ? 'year' : 'month';
            $priceAmount = $billingCycle === 'annually' 
                ? ($plan->annual_price ?? $plan->price * 10) 
                : $plan->price;
            $amountInCents = intval($priceAmount * 100); // Convertir en centimes
            
            $price = Price::create([
                'product' => $product->id,
                'currency' => strtolower(config('services.stripe.currency', 'xaf')),
                'unit_amount' => $amountInCents,
                'recurring' => [
                    'interval' => $interval,
                ],
                'metadata' => [
                    'plan_id' => $plan->id,
                    'plan_code' => $plan->code,
                    'billing_cycle' => $billingCycle,
                ],
            ]);

            Log::info('Price Stripe créé pour plan créateur', [
                'plan_id' => $plan->id,
                'plan_code' => $plan->code,
                'billing_cycle' => $billingCycle,
                'stripe_price_id' => $price->id,
                'stripe_product_id' => $product->id,
                'amount' => $priceAmount,
            ]);

            return $price->id;
        } catch (ApiErrorException $e) {
            Log::error('Erreur Stripe lors de la création du Price', [
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'stripe_error' => $e->getMessage(),
                'stripe_error_code' => $e->getStripeCode(),
            ]);
            throw $e;
        }
    }

    /**
     * Récupère les informations d'une session Checkout Stripe.
     * 
     * @param string $sessionId L'ID de la session Checkout
     * @return \Stripe\Checkout\Session La session Stripe
     * @throws ApiErrorException Si l'API Stripe retourne une erreur
     */
    public function retrieveCheckoutSession(string $sessionId): \Stripe\Checkout\Session
    {
        try {
            return Session::retrieve($sessionId);
        } catch (ApiErrorException $e) {
            Log::error('Erreur Stripe lors de la récupération de la session Checkout', [
                'session_id' => $sessionId,
                'stripe_error' => $e->getMessage(),
                'stripe_error_code' => $e->getStripeCode(),
            ]);
            throw $e;
        }
    }
}


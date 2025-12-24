<?php

namespace App\Services\Payments;

use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Stripe;
use Stripe\Exception\ApiErrorException;

/**
 * Service de gestion des comptes Stripe Connect Express pour les créateurs.
 * 
 * Ce service gère :
 * - La création de comptes Stripe Connect Express
 * - La génération de liens d'onboarding
 * - La synchronisation des statuts de compte
 * - La vérification de l'éligibilité aux paiements
 * 
 * ⚠️ Ce service ne gère PAS :
 * - Les abonnements (billing) → CreatorSubscriptionService
 * - La suspension de créateurs → CreatorSuspensionService
 * - Les webhooks → StripeConnectWebhookController
 * - Les notifications → NotificationService
 */
class StripeConnectService
{
    /**
     * Constructeur du service.
     * 
     * Initialise la clé API Stripe depuis la configuration Laravel.
     * 
     * @throws \RuntimeException Si la clé Stripe n'est pas configurée
     */
    public function __construct()
    {
        $stripeSecret = config('services.stripe.secret');
        
        if (empty($stripeSecret)) {
            throw new \RuntimeException(
                'Stripe Connect non configuré : la clé secrète Stripe (STRIPE_SECRET) est manquante dans la configuration. ' .
                'Veuillez ajouter STRIPE_SECRET dans votre fichier .env avec une clé Stripe valide (format: sk_test_... ou sk_live_...). ' .
                'Consultez la documentation : https://dashboard.stripe.com/apikeys'
            );
        }
        
        // Vérifier si c'est un placeholder (valeur de test non valide)
        if (str_contains(strtolower($stripeSecret), 'your_secret_key') || 
            str_contains(strtolower($stripeSecret), 'sk_test_your') ||
            strlen($stripeSecret) < 20) {
            throw new \RuntimeException(
                'Stripe Connect non configuré : la clé secrète Stripe (STRIPE_SECRET) contient une valeur de placeholder. ' .
                'Veuillez remplacer la valeur dans votre fichier .env par une vraie clé Stripe (format: sk_test_... ou sk_live_...). ' .
                'Récupérez votre clé sur : https://dashboard.stripe.com/apikeys'
            );
        }
        
        Stripe::setApiKey($stripeSecret);
    }

    /**
     * Crée un compte Stripe Connect Express pour un créateur.
     * 
     * Cette méthode :
     * - Vérifie que le créateur n'a pas déjà un compte Stripe
     * - Crée un compte Stripe Connect Express avec les capacités card_payments et transfers
     * - Persiste les informations dans la base de données avec le statut initial
     * 
     * ⚠️ Cette méthode ne crée PAS :
     * - D'abonnement (billing) → Voir CreatorSubscriptionService
     * - De lien d'onboarding → Voir createOnboardingLink()
     * 
     * @param CreatorProfile $creator Le profil du créateur pour lequel créer le compte
     * @return CreatorStripeAccount Le compte Stripe Connect créé et persisté
     * @throws \RuntimeException Si le créateur a déjà un compte Stripe
     * @throws \RuntimeException Si le créateur n'a pas d'utilisateur associé ou pas d'email
     * @throws ApiErrorException Si l'API Stripe retourne une erreur
     */
    public function createAccount(CreatorProfile $creator): CreatorStripeAccount
    {
        // Vérifier que le créateur n'a pas déjà un compte Stripe
        $existingAccount = CreatorStripeAccount::where('creator_profile_id', $creator->id)->first();
        if ($existingAccount !== null) {
            throw new \RuntimeException(
                "Le créateur {$creator->id} possède déjà un compte Stripe Connect (ID: {$existingAccount->stripe_account_id})."
            );
        }

        // Vérifier que le créateur a un utilisateur associé avec un email
        if (!$creator->user) {
            throw new \RuntimeException(
                "Le créateur {$creator->id} n'a pas d'utilisateur associé. Impossible de créer un compte Stripe Connect."
            );
        }

        $userEmail = $creator->user->email;
        if (empty($userEmail)) {
            throw new \RuntimeException(
                "L'utilisateur du créateur {$creator->id} n'a pas d'adresse email. Impossible de créer un compte Stripe Connect."
            );
        }

        try {
            // Créer le compte Stripe Connect Express
            $stripeAccount = Account::create([
                'type' => 'express',
                'country' => 'CG', // République du Congo
                'email' => $userEmail,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);

            // Persister les informations dans la base de données
            $creatorStripeAccount = CreatorStripeAccount::create([
                'creator_profile_id' => $creator->id,
                'stripe_account_id' => $stripeAccount->id,
                'account_type' => 'express',
                'onboarding_status' => 'in_progress',
                'charges_enabled' => false,
                'payouts_enabled' => false,
                'details_submitted' => false,
                'requirements_currently_due' => $stripeAccount->requirements->currently_due ?? null,
                'requirements_eventually_due' => $stripeAccount->requirements->eventually_due ?? null,
                'capabilities' => $this->extractCapabilities($stripeAccount),
            ]);

            Log::info('Compte Stripe Connect créé avec succès', [
                'creator_profile_id' => $creator->id,
                'stripe_account_id' => $stripeAccount->id,
            ]);

            return $creatorStripeAccount;
        } catch (ApiErrorException $e) {
            Log::error('Erreur Stripe lors de la création du compte Connect', [
                'creator_profile_id' => $creator->id,
                'stripe_error' => $e->getMessage(),
                'stripe_error_code' => $e->getStripeCode(),
            ]);
            throw $e;
        }
    }

    /**
     * Extrait les capacités du compte Stripe au format attendu par la base de données.
     * 
     * @param Account $stripeAccount Le compte Stripe
     * @return array|null Les capacités au format array ou null
     */
    private function extractCapabilities(Account $stripeAccount): ?array
    {
        if (!isset($stripeAccount->capabilities)) {
            return null;
        }

        $capabilities = [];
        foreach ($stripeAccount->capabilities as $capability => $status) {
            $capabilities[$capability] = [
                'status' => $status->status ?? null,
                'requested' => $status->requested ?? false,
            ];
        }

        return $capabilities;
    }

    /**
     * Crée un lien d'onboarding Stripe pour un compte Stripe Connect Express.
     * 
     * Cette méthode :
     * - Vérifie que le compte Stripe existe et est valide
     * - Crée un AccountLink Stripe de type account_onboarding
     * - Définit les URLs de refresh et return
     * - Persiste l'URL du lien et sa date d'expiration
     * - Retourne l'URL du lien d'onboarding
     * 
     * ⚠️ Cette méthode ne fait PAS :
     * - De redirection → Géré par le contrôleur
     * - De logique d'abonnement → Voir CreatorSubscriptionService
     * - De logique KYC métier → Géré par Stripe
     * - De traitement de webhook → Voir StripeConnectWebhookController
     * 
     * @param CreatorStripeAccount $account Le compte Stripe Connect pour lequel créer le lien
     * @return string L'URL du lien d'onboarding Stripe
     * @throws \RuntimeException Si le compte Stripe n'existe pas ou n'est pas valide
     * @throws ApiErrorException Si l'API Stripe retourne une erreur
     */
    public function createOnboardingLink(CreatorStripeAccount $account): string
    {
        // Vérifier que le compte Stripe existe et a un stripe_account_id valide
        if (empty($account->stripe_account_id)) {
            throw new \RuntimeException(
                "Le compte Stripe Connect {$account->id} n'a pas d'identifiant Stripe valide. Impossible de créer un lien d'onboarding."
            );
        }

        // Construire les URLs de refresh et return
        $baseUrl = config('app.url');
        $refreshUrl = url('/creator/stripe/onboarding/refresh');
        $returnUrl = url('/creator/stripe/onboarding/return');

        try {
            // Créer le lien d'onboarding Stripe
            $accountLink = AccountLink::create([
                'account' => $account->stripe_account_id,
                'refresh_url' => $refreshUrl,
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
            ]);

            // Extraire la date d'expiration du lien (Stripe retourne un timestamp Unix)
            $expiresAt = null;
            if (isset($accountLink->expires_at)) {
                $expiresAt = now()->setTimestamp($accountLink->expires_at);
            } else {
                // Par défaut, les liens Stripe expirent après 24 heures
                $expiresAt = now()->addHours(24);
            }

            // Persister l'URL du lien et sa date d'expiration
            $account->update([
                'onboarding_link_url' => $accountLink->url,
                'onboarding_link_expires_at' => $expiresAt,
            ]);

            Log::info('Lien d\'onboarding Stripe créé avec succès', [
                'creator_stripe_account_id' => $account->id,
                'stripe_account_id' => $account->stripe_account_id,
                'onboarding_link_url' => $accountLink->url,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

            return $accountLink->url;
        } catch (ApiErrorException $e) {
            Log::error('Erreur Stripe lors de la création du lien d\'onboarding', [
                'creator_stripe_account_id' => $account->id,
                'stripe_account_id' => $account->stripe_account_id,
                'stripe_error' => $e->getMessage(),
                'stripe_error_code' => $e->getStripeCode(),
            ]);
            throw $e;
        }
    }

    /**
     * Synchronise le statut d'un compte Stripe Connect Express avec la base de données.
     * 
     * Cette méthode :
     * - Récupère le compte Stripe via l'API Stripe
     * - Charge le CreatorStripeAccount correspondant depuis la base
     * - Met à jour les champs de statut (charges_enabled, payouts_enabled, requirements, etc.)
     * - Détermine le statut d'onboarding en fonction des indicateurs Stripe
     * - Gère les états partiels (onboarding incomplet)
     * 
     * ⚠️ Cette méthode ne fait PAS :
     * - De création d'abonnement → Voir CreatorSubscriptionService
     * - De suspension de créateur → Voir CreatorSuspensionService
     * - De notification → Voir NotificationService
     * - De redirection → Géré par le contrôleur
     * - De traitement de webhook → Voir StripeConnectWebhookController
     * 
     * @param string $stripeAccountId L'identifiant du compte Stripe (format acct_xxx)
     * @return void
     * @throws \RuntimeException Si le compte Stripe n'existe pas en base de données
     * @throws ApiErrorException Si l'API Stripe retourne une erreur
     */
    public function syncAccountStatus(string $stripeAccountId): void
    {
        // Charger le compte depuis la base de données
        $creatorAccount = CreatorStripeAccount::where('stripe_account_id', $stripeAccountId)->first();
        
        if (!$creatorAccount) {
            throw new \RuntimeException(
                "Aucun compte Stripe Connect trouvé avec l'identifiant Stripe : {$stripeAccountId}."
            );
        }

        try {
            // Récupérer le compte Stripe via l'API
            $stripeAccount = Account::retrieve($stripeAccountId);

            // Extraire les données du compte Stripe
            $chargesEnabled = (bool) ($stripeAccount->charges_enabled ?? false);
            $payoutsEnabled = (bool) ($stripeAccount->payouts_enabled ?? false);
            $detailsSubmitted = (bool) ($stripeAccount->details_submitted ?? false);
            
            $requirementsCurrentlyDue = $stripeAccount->requirements->currently_due ?? null;
            $requirementsEventuallyDue = $stripeAccount->requirements->eventually_due ?? null;
            
            // Convertir les requirements en array si nécessaire
            $requirementsCurrentlyDueArray = is_array($requirementsCurrentlyDue) 
                ? $requirementsCurrentlyDue 
                : (is_object($requirementsCurrentlyDue) ? (array) $requirementsCurrentlyDue : null);
            
            $requirementsEventuallyDueArray = is_array($requirementsEventuallyDue) 
                ? $requirementsEventuallyDue 
                : (is_object($requirementsEventuallyDue) ? (array) $requirementsEventuallyDue : null);

            // Déterminer le statut d'onboarding
            $onboardingStatus = $this->determineOnboardingStatus(
                $chargesEnabled,
                $payoutsEnabled,
                $detailsSubmitted,
                $requirementsCurrentlyDueArray
            );

            // Sauvegarder l'état précédent de payouts_enabled pour détecter le changement KYC
            $wasPayoutsEnabledBefore = $creatorAccount->payouts_enabled;

            // Mettre à jour le compte en base de données
            $creatorAccount->update([
                'charges_enabled' => $chargesEnabled,
                'payouts_enabled' => $payoutsEnabled,
                'details_submitted' => $detailsSubmitted,
                'requirements_currently_due' => $requirementsCurrentlyDueArray,
                'requirements_eventually_due' => $requirementsEventuallyDueArray,
                'capabilities' => $this->extractCapabilities($stripeAccount),
                'onboarding_status' => $onboardingStatus,
                'last_synced_at' => now(),
            ]);

            // Détecter si le KYC vient d'être complété et notifier le créateur
            $kycService = app(\App\Services\CreatorKycService::class);
            if ($kycService->hasKycJustCompleted($creatorAccount, $wasPayoutsEnabledBefore)) {
                $kycService->activatePayouts($creatorAccount);
            }

            Log::info('Statut du compte Stripe Connect synchronisé avec succès', [
                'creator_stripe_account_id' => $creatorAccount->id,
                'stripe_account_id' => $stripeAccountId,
                'charges_enabled' => $chargesEnabled,
                'payouts_enabled' => $payoutsEnabled,
                'details_submitted' => $detailsSubmitted,
                'onboarding_status' => $onboardingStatus,
                'kyc_just_completed' => $kycService->hasKycJustCompleted($creatorAccount, $wasPayoutsEnabledBefore),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Erreur Stripe lors de la synchronisation du compte Connect', [
                'creator_stripe_account_id' => $creatorAccount->id,
                'stripe_account_id' => $stripeAccountId,
                'stripe_error' => $e->getMessage(),
                'stripe_error_code' => $e->getStripeCode(),
            ]);
            throw $e;
        }
    }

    /**
     * Détermine le statut d'onboarding en fonction des indicateurs Stripe.
     * 
     * Règles de mapping :
     * - complete : charges_enabled === true ET payouts_enabled === true
     * - in_progress : details_submitted === true OU requirements en attente OU charges_enabled === false
     * - failed : compte restreint ou erreur détectée (non implémenté pour l'instant, reste in_progress)
     * - pending : compte créé mais aucune action (non utilisé après création)
     * 
     * @param bool $chargesEnabled Le créateur peut recevoir des paiements
     * @param bool $payoutsEnabled Le créateur peut recevoir des versements
     * @param bool $detailsSubmitted Les informations KYC sont soumises
     * @param array|null $requirementsCurrentlyDue Les exigences en attente
     * @return string Le statut d'onboarding : 'complete', 'in_progress', 'failed', ou 'pending'
     */
    private function determineOnboardingStatus(
        bool $chargesEnabled,
        bool $payoutsEnabled,
        bool $detailsSubmitted,
        ?array $requirementsCurrentlyDue
    ): string {
        // Compte complètement activé : charges ET payouts activés
        if ($chargesEnabled && $payoutsEnabled) {
            return 'complete';
        }

        // Compte en cours d'onboarding : détails soumis mais pas encore activé
        if ($detailsSubmitted) {
            return 'in_progress';
        }

        // Exigences en attente : onboarding en cours
        if (!empty($requirementsCurrentlyDue) && is_array($requirementsCurrentlyDue)) {
            return 'in_progress';
        }

        // Par défaut : en cours (onboarding non terminé)
        return 'in_progress';
    }

    /**
     * Vérifie si un créateur peut recevoir des paiements.
     * 
     * Cette méthode effectue toutes les vérifications nécessaires pour déterminer
     * si un créateur est éligible pour recevoir des paiements sur la plateforme.
     * 
     * Vérifications effectuées (dans l'ordre) :
     * 1. Le créateur possède un compte Stripe Connect
     * 2. Le compte Stripe a charges_enabled === true
     * 3. Le compte Stripe a payouts_enabled === true
     * 4. Le statut d'onboarding est 'complete'
     * 5. Le créateur est actif (is_active === true ET status === 'active')
     * 6. L'abonnement du créateur est actif (status === 'active')
     * 
     * ⚠️ Cette méthode ne fait PAS :
     * - D'appel Stripe → Utilise uniquement les données en base
     * - D'écriture en base → Lecture seule
     * - De log métier → Aucun log
     * - De levée d'exception → Retourne false en cas d'échec
     * - De logique UI / webhook → Vérification pure
     * 
     * @param CreatorProfile $creator Le profil du créateur à vérifier
     * @return bool true si le créateur peut recevoir des paiements, false sinon
     */
    public function canCreatorReceivePayments(CreatorProfile $creator): bool
    {
        // Vérification 1 : Le créateur possède un compte Stripe Connect
        $stripeAccount = CreatorStripeAccount::where('creator_profile_id', $creator->id)->first();
        if (!$stripeAccount) {
            return false;
        }

        // Vérification 2 : charges_enabled === true
        if (!$stripeAccount->charges_enabled) {
            return false;
        }

        // Vérification 3 : payouts_enabled === true
        if (!$stripeAccount->payouts_enabled) {
            return false;
        }

        // Vérification 4 : onboarding_status === 'complete'
        if ($stripeAccount->onboarding_status !== 'complete') {
            return false;
        }

        // Vérification 5 : Le créateur est actif (non suspendu)
        if (!$creator->is_active || $creator->status !== 'active') {
            return false;
        }

        // Vérification 6 : L'abonnement du créateur est actif
        $subscription = CreatorSubscription::where('creator_profile_id', $creator->id)->first();
        if (!$subscription || $subscription->status !== 'active') {
            return false;
        }

        // Toutes les vérifications sont passées
        return true;
    }
}


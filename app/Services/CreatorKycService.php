<?php

namespace App\Services;

use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Notifications\KycCompletedNotification;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion de l'automatisation KYC pour les créateurs.
 * 
 * Ce service gère :
 * - La vérification du statut KYC d'un créateur
 * - L'envoi de notifications quand le KYC est complété
 * - L'activation automatique des paiements
 * 
 * ⚠️ Ce service ne fait PAS :
 * - D'appels directs à l'API Stripe → Utilise les données déjà synchronisées
 * - De gestion des webhooks → StripeConnectWebhookController
 * - De création de compte Stripe → StripeConnectService
 */
class CreatorKycService
{
    /**
     * Vérifie le statut KYC d'un créateur.
     * 
     * @param CreatorProfile $profile Le profil créateur
     * @return array Statut KYC avec détails
     */
    public function checkKycStatus(CreatorProfile $profile): array
    {
        $stripeAccount = $profile->stripeAccount;

        if (!$stripeAccount) {
            return [
                'status' => 'not_started',
                'message' => 'Aucun compte Stripe Connect configuré.',
                'can_receive_payouts' => false,
            ];
        }

        $isComplete = $stripeAccount->details_submitted 
            && $stripeAccount->charges_enabled 
            && $stripeAccount->payouts_enabled;

        if ($isComplete) {
            return [
                'status' => 'complete',
                'message' => 'Vérification complète. Vous pouvez recevoir des paiements.',
                'can_receive_payouts' => true,
                'onboarding_status' => $stripeAccount->onboarding_status,
            ];
        }

        if ($stripeAccount->details_submitted && !$stripeAccount->payouts_enabled) {
            return [
                'status' => 'pending_review',
                'message' => 'Documents soumis. En cours de vérification par Stripe.',
                'can_receive_payouts' => false,
                'requirements' => $stripeAccount->requirements_currently_due ?? [],
            ];
        }

        return [
            'status' => 'incomplete',
            'message' => 'Veuillez compléter votre vérification d\'identité.',
            'can_receive_payouts' => false,
            'requirements' => $stripeAccount->requirements_currently_due ?? [],
        ];
    }

    /**
     * Notifie un créateur que sa vérification KYC est complète.
     * 
     * @param CreatorProfile $profile Le profil créateur
     * @return void
     */
    public function notifyKycComplete(CreatorProfile $profile): void
    {
        $user = $profile->user;

        if (!$user) {
            Log::warning('CreatorKycService: Cannot notify KYC completion, user not found', [
                'creator_profile_id' => $profile->id,
            ]);
            return;
        }

        try {
            $user->notify(new KycCompletedNotification($profile));

            Log::info('CreatorKycService: KYC completion notification sent', [
                'creator_profile_id' => $profile->id,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('CreatorKycService: Failed to send KYC completion notification', [
                'creator_profile_id' => $profile->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Active les paiements pour un créateur (appelé automatiquement par le webhook).
     * 
     * Cette méthode est appelée quand Stripe confirme que le KYC est validé.
     * 
     * @param CreatorStripeAccount $stripeAccount Le compte Stripe
     * @return bool True si les paiements ont été activés, false sinon
     */
    public function activatePayouts(CreatorStripeAccount $stripeAccount): bool
    {
        $profile = $stripeAccount->creatorProfile;

        if (!$profile) {
            Log::warning('CreatorKycService: Cannot activate payouts, profile not found', [
                'stripe_account_id' => $stripeAccount->id,
            ]);
            return false;
        }

        // Vérifier que le compte Stripe est bien validé
        if (!$stripeAccount->details_submitted || !$stripeAccount->payouts_enabled) {
            Log::info('CreatorKycService: Payouts not yet enabled by Stripe', [
                'stripe_account_id' => $stripeAccount->id,
                'details_submitted' => $stripeAccount->details_submitted,
                'payouts_enabled' => $stripeAccount->payouts_enabled,
            ]);
            return false;
        }

        // Si le profil créateur n'est pas encore actif, l'activer
        if ($profile->status !== 'active') {
            $profile->update([
                'status' => 'active',
                'is_active' => true,
            ]);

            Log::info('CreatorKycService: Creator profile activated', [
                'creator_profile_id' => $profile->id,
                'stripe_account_id' => $stripeAccount->id,
            ]);
        }

        // Envoyer la notification
        $this->notifyKycComplete($profile);

        return true;
    }

    /**
     * Détecte si le statut KYC vient de changer vers "complete".
     * 
     * @param CreatorStripeAccount $stripeAccount Le compte Stripe
     * @param bool $wasPayoutsEnabledBefore État précédent de payouts_enabled
     * @return bool True si le KYC vient d'être complété
     */
    public function hasKycJustCompleted(CreatorStripeAccount $stripeAccount, bool $wasPayoutsEnabledBefore): bool
    {
        // Le KYC est considéré comme "juste complété" si :
        // - payouts_enabled est maintenant true
        // - payouts_enabled était false avant
        // - details_submitted est true
        return $stripeAccount->payouts_enabled 
            && !$wasPayoutsEnabledBefore 
            && $stripeAccount->details_submitted;
    }
}

<?php

namespace App\Services\Financial;

use App\Models\CreatorSubscription;
use App\Services\CreatorCapabilityService;
use Illuminate\Support\Facades\Log;

/**
 * Service d'optimisation automatique des abonnements
 * 
 * Phase 6.4 - Optimisation Automatique
 */
class SubscriptionOptimizationService
{
    protected CreatorCapabilityService $capabilityService;

    public function __construct(CreatorCapabilityService $capabilityService)
    {
        $this->capabilityService = $capabilityService;
    }

    /**
     * Suspendre automatiquement les créateurs avec abonnement unpaid
     * 
     * @param int $gracePeriodDays Période de grâce en jours (défaut: 0 = immédiat)
     * @return int Nombre de créateurs suspendus
     */
    public function suspendUnpaidCreators(int $gracePeriodDays = 0): int
    {
        $cutoffDate = now()->subDays($gracePeriodDays);

        $unpaidSubscriptions = CreatorSubscription::where('status', 'unpaid')
            ->where('updated_at', '<=', $cutoffDate)
            ->with('creator')
            ->get();

        $suspendedCount = 0;

        foreach ($unpaidSubscriptions as $subscription) {
            if ($subscription->creator) {
                // Invalider le cache pour forcer le downgrade vers FREE
                $this->capabilityService->clearCache($subscription->creator);

                // Logger l'événement
                $this->logSubscriptionEvent($subscription, 'suspended', [
                    'reason' => 'Abonnement unpaid',
                    'grace_period_days' => $gracePeriodDays,
                ]);

                $suspendedCount++;
            }
        }

        Log::info("Suspension automatique des créateurs unpaid", [
            'count' => $suspendedCount,
            'grace_period_days' => $gracePeriodDays,
        ]);

        return $suspendedCount;
    }

    /**
     * Réactiver automatiquement les abonnements après paiement réussi
     * 
     * @return int Nombre d'abonnements réactivés
     */
    public function reactivateAfterPayment(): int
    {
        // Les abonnements sont automatiquement réactivés via le webhook invoice.paid
        // Cette méthode peut être utilisée pour une réactivation manuelle ou batch

        $reactivatedCount = 0;

        // Trouver les abonnements past_due qui ont été payés récemment
        // (généralement géré par le webhook, mais peut servir pour un batch)
        $pastDueSubscriptions = CreatorSubscription::where('status', 'past_due')
            ->where('updated_at', '>=', now()->subHours(24))
            ->with('creator')
            ->get();

        foreach ($pastDueSubscriptions as $subscription) {
            // Vérifier si le paiement a été effectué (via webhook)
            // Si oui, réactiver
            // Cette logique est généralement gérée par StripeBillingWebhookController
        }

        return $reactivatedCount;
    }

    /**
     * Downgrade automatique vers FREE pour les abonnements expirés
     * 
     * @return int Nombre d'abonnements downgradés
     */
    public function downgradeExpiredSubscriptions(): int
    {
        $expiredSubscriptions = CreatorSubscription::where('status', 'active')
            ->where('ends_at', '<', now())
            ->with('creator')
            ->get();

        $downgradedCount = 0;

        foreach ($expiredSubscriptions as $subscription) {
            if ($subscription->creator) {
                // Mettre à jour le statut
                $subscription->update([
                    'status' => 'canceled',
                ]);

                // Invalider le cache pour forcer le downgrade vers FREE
                $this->capabilityService->clearCache($subscription->creator);

                // Logger l'événement
                $this->logSubscriptionEvent($subscription, 'downgraded', [
                    'reason' => 'Abonnement expiré',
                    'expired_at' => $subscription->ends_at,
                ]);

                $downgradedCount++;
            }
        }

        Log::info("Downgrade automatique des abonnements expirés", [
            'count' => $downgradedCount,
        ]);

        return $downgradedCount;
    }

    /**
     * Logger un événement d'abonnement
     * 
     * @param CreatorSubscription $subscription
     * @param string $eventType
     * @param array $metadata
     * @return void
     */
    protected function logSubscriptionEvent(CreatorSubscription $subscription, string $eventType, array $metadata = []): void
    {
        // TODO: Créer la table creator_subscription_events si elle n'existe pas
        // Pour l'instant, on log dans les logs Laravel

        Log::info("Événement abonnement: {$eventType}", [
            'subscription_id' => $subscription->id,
            'creator_id' => $subscription->creator_id,
            'stripe_subscription_id' => $subscription->stripe_subscription_id,
            'event_type' => $eventType,
            'metadata' => $metadata,
        ]);

        // Si la table existe, créer l'enregistrement
        if (\Illuminate\Support\Facades\Schema::hasTable('creator_subscription_events')) {
            \Illuminate\Support\Facades\DB::table('creator_subscription_events')->insert([
                'creator_subscription_id' => $subscription->id,
                'creator_id' => $subscription->creator_id,
                'event_type' => $eventType,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Exécuter toutes les optimisations automatiques
     * 
     * @return array Statistiques des optimisations
     */
    public function runOptimizations(): array
    {
        Log::info('Démarrage des optimisations automatiques');

        $suspended = $this->suspendUnpaidCreators(config('subscriptions.grace_period_days', 0));
        $downgraded = $this->downgradeExpiredSubscriptions();
        $reactivated = $this->reactivateAfterPayment();

        return [
            'suspended' => $suspended,
            'downgraded' => $downgraded,
            'reactivated' => $reactivated,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}


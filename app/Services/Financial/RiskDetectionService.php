<?php

namespace App\Services\Financial;

use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service de détection automatique des risques
 * 
 * Phase 6.3 - Détection Automatique des Risques
 */
class RiskDetectionService
{
    /**
     * Détecter les créateurs à risque
     * 
     * Critères :
     * - Abonnement past_due
     * - Paiement échoué ≥ 2 fois
     * - Onboarding incomplet > 7 jours
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function detectCreatorsAtRisk()
    {
        $atRisk = collect();

        // 1. Créateurs avec abonnement past_due
        $pastDueCreators = CreatorProfile::whereHas('subscriptions', function ($query) {
            $query->where('status', 'past_due');
        })->with(['subscriptions', 'stripeAccount', 'user'])->get();

        foreach ($pastDueCreators as $creator) {
            $atRisk->push([
                'creator' => $creator,
                'risk_reason' => 'Abonnement en retard de paiement (past_due)',
                'risk_level' => 'high',
                'suggested_action' => 'Relance email + Suspension si unpaid',
            ]);
        }

        // 2. Créateurs avec paiements échoués ≥ 2 fois
        // (On considère qu'un abonnement passé de active → past_due → unpaid = 2 échecs)
        $failedPaymentCreators = CreatorProfile::whereHas('subscriptions', function ($query) {
            $query->where('status', 'unpaid')
                ->where('updated_at', '>=', now()->subDays(30)); // Dans les 30 derniers jours
        })->with(['subscriptions', 'stripeAccount', 'user'])->get();

        foreach ($failedPaymentCreators as $creator) {
            $subscription = $creator->subscriptions()->where('status', 'unpaid')->first();
            $atRisk->push([
                'creator' => $creator,
                'risk_reason' => 'Paiement échoué (statut unpaid)',
                'risk_level' => 'critical',
                'suggested_action' => 'Suspension automatique + Downgrade FREE',
            ]);
        }

        // 3. Créateurs avec onboarding incomplet > 7 jours
        $incompleteOnboardingCreators = CreatorProfile::whereHas('stripeAccount', function ($query) {
            $query->where('onboarding_status', 'in_progress')
                ->where('created_at', '<', now()->subDays(7));
        })->with(['stripeAccount', 'user'])->get();

        foreach ($incompleteOnboardingCreators as $creator) {
            $daysIncomplete = $creator->stripeAccount->created_at->diffInDays(now());
            $atRisk->push([
                'creator' => $creator,
                'risk_reason' => "Onboarding incomplet depuis {$daysIncomplete} jours",
                'risk_level' => 'medium',
                'suggested_action' => 'Relance email + Rappel onboarding',
            ]);
        }

        return $atRisk;
    }

    /**
     * Envoyer des alertes automatiques pour les créateurs à risque
     * 
     * @param bool $sendEmail Envoyer un email à l'admin
     * @return int Nombre d'alertes envoyées
     */
    public function sendRiskAlerts(bool $sendEmail = true): int
    {
        $atRisk = $this->detectCreatorsAtRisk();
        $alertCount = 0;

        foreach ($atRisk as $risk) {
            $creator = $risk['creator'];
            $riskLevel = $risk['risk_level'];

            // Marquer le créateur avec un flag risk_level
            // TODO: Ajouter une colonne risk_level dans creator_profiles si nécessaire
            // Pour l'instant, on log

            Log::warning('Créateur à risque détecté', [
                'creator_id' => $creator->id,
                'creator_name' => $creator->brand_name,
                'risk_reason' => $risk['risk_reason'],
                'risk_level' => $riskLevel,
                'suggested_action' => $risk['suggested_action'],
            ]);

            $alertCount++;

            // Envoyer un email à l'admin si niveau critique
            if ($sendEmail && $riskLevel === 'critical') {
                // TODO: Implémenter l'envoi d'email
                // Mail::to(config('mail.admin_email'))->send(new CreatorRiskAlert($creator, $risk));
            }
        }

        return $alertCount;
    }

    /**
     * Obtenir les statistiques des risques
     * 
     * @return array
     */
    public function getRiskStatistics(): array
    {
        $atRisk = $this->detectCreatorsAtRisk();

        $byLevel = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
        ];

        foreach ($atRisk as $risk) {
            $level = $risk['risk_level'];
            $byLevel[$level]++;
        }

        return [
            'total_at_risk' => $atRisk->count(),
            'by_level' => $byLevel,
            'creators' => $atRisk->map(function ($risk) {
                return [
                    'creator_id' => $risk['creator']->id,
                    'creator_name' => $risk['creator']->brand_name,
                    'risk_reason' => $risk['risk_reason'],
                    'risk_level' => $risk['risk_level'],
                    'suggested_action' => $risk['suggested_action'],
                ];
            })->toArray(),
        ];
    }

    /**
     * Exécuter la détection des risques (à appeler via cron)
     * 
     * @return void
     */
    public function runRiskDetection(): void
    {
        Log::info('Démarrage de la détection automatique des risques');

        $atRisk = $this->detectCreatorsAtRisk();
        $count = $atRisk->count();

        if ($count > 0) {
            Log::warning("{$count} créateur(s) à risque détecté(s)", [
                'count' => $count,
            ]);

            // Envoyer les alertes
            $this->sendRiskAlerts();
        } else {
            Log::info('Aucun créateur à risque détecté');
        }
    }
}


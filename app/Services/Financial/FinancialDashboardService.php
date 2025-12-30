<?php

namespace App\Services\Financial;

use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de calcul des KPI financiers pour le dashboard admin
 * 
 * Phase 6.1 - Dashboard Financier Admin
 */
class FinancialDashboardService
{
    /**
     * Calculer le MRR (Monthly Recurring Revenue)
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return float MRR en XAF
     */
    public function calculateMRR(?string $month = null): float
    {
        $month = $month ?? now()->format('Y-m');
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        // Récupérer tous les abonnements actifs à la fin du mois
        $subscriptions = CreatorSubscription::where('status', 'active')
            ->where(function ($query) use ($endOfMonth) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $endOfMonth);
            })
            ->where('started_at', '<=', $endOfMonth)
            ->with('plan')
            ->get();

        $mrr = 0;

        foreach ($subscriptions as $subscription) {
            if ($subscription->plan) {
                $mrr += (float) $subscription->plan->price;
            }
        }

        return round($mrr, 2);
    }

    /**
     * Calculer l'ARR (Annual Recurring Revenue)
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return float ARR en XAF
     */
    public function calculateARR(?string $month = null): float
    {
        $mrr = $this->calculateMRR($month);
        return round($mrr * 12, 2);
    }

    /**
     * Obtenir le total des abonnements actifs
     * 
     * @return int
     */
    public function getTotalActiveSubscriptions(): int
    {
        return CreatorSubscription::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->count();
    }

    /**
     * Obtenir le total des abonnements annulés (ce mois)
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return int
     */
    public function getTotalCanceledSubscriptions(?string $month = null): int
    {
        $month = $month ?? now()->format('Y-m');
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        return CreatorSubscription::where('status', 'canceled')
            ->whereBetween('canceled_at', [$startOfMonth, $endOfMonth])
            ->count();
    }

    /**
     * Calculer le revenu net de la plateforme
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return float Revenu net en XAF
     */
    public function calculateNetRevenue(?string $month = null): float
    {
        // Pour l'instant, revenu net = MRR (pas de commission déduite)
        // TODO: Ajouter déduction des frais Stripe si nécessaire
        return $this->calculateMRR($month);
    }

    /**
     * Obtenir le nombre de créateurs actifs
     * 
     * @return int
     */
    public function getActiveCreators(): int
    {
        return CreatorProfile::where('is_active', true)
            ->where('status', 'active')
            ->whereHas('subscriptions', function ($query) {
                $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('ends_at')
                            ->orWhere('ends_at', '>=', now());
                    });
            })
            ->count();
    }

    /**
     * Obtenir le nombre de créateurs bloqués
     * 
     * @return array ['stripe' => int, 'subscription' => int, 'total' => int]
     */
    public function getBlockedCreators(): array
    {
        // Créateurs bloqués par Stripe (charges_enabled = false)
        $blockedByStripe = CreatorProfile::whereHas('stripeAccount', function ($query) {
            $query->where('charges_enabled', false)
                ->orWhere('onboarding_status', '!=', 'complete');
        })->count();

        // Créateurs bloqués par abonnement (unpaid, past_due, canceled)
        $blockedBySubscription = CreatorProfile::whereHas('subscriptions', function ($query) {
            $query->whereIn('status', ['unpaid', 'past_due', 'canceled']);
        })->count();

        // Total unique (un créateur peut être bloqué pour plusieurs raisons)
        $total = CreatorProfile::where(function ($query) {
            $query->whereHas('stripeAccount', function ($q) {
                $q->where('charges_enabled', false)
                    ->orWhere('onboarding_status', '!=', 'complete');
            })->orWhereHas('subscriptions', function ($q) {
                $q->whereIn('status', ['unpaid', 'past_due', 'canceled']);
            });
        })->count();

        return [
            'stripe' => $blockedByStripe,
            'subscription' => $blockedBySubscription,
            'total' => $total,
        ];
    }

    /**
     * Obtenir le nombre de créateurs en onboarding
     * 
     * @return int
     */
    public function getCreatorsInOnboarding(): int
    {
        return CreatorProfile::whereHas('stripeAccount', function ($query) {
            $query->where('onboarding_status', 'in_progress')
                ->where('details_submitted', true);
        })->count();
    }

    /**
     * Obtenir le nombre de créateurs en risque (past_due)
     * 
     * @return int
     */
    public function getCreatorsAtRisk(): int
    {
        return CreatorProfile::whereHas('subscriptions', function ($query) {
            $query->where('status', 'past_due');
        })->count();
    }

    /**
     * Obtenir les statistiques de paiements Stripe
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return array
     */
    public function getPaymentStats(?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        // Compter les abonnements avec statut actif (paiements réussis)
        $successful = CreatorSubscription::where('status', 'active')
            ->whereBetween('started_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Compter les abonnements avec statut unpaid/past_due (paiements échoués)
        $failed = CreatorSubscription::whereIn('status', ['unpaid', 'past_due'])
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->count();

        $total = $successful + $failed;
        $failureRate = $total > 0 ? round(($failed / $total) * 100, 2) : 0;

        return [
            'successful' => $successful,
            'failed' => $failed,
            'total' => $total,
            'failure_rate' => $failureRate,
        ];
    }

    /**
     * Obtenir les derniers webhooks reçus
     * 
     * @param int $limit Nombre de webhooks à retourner
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentWebhooks(int $limit = 10)
    {
        // Utiliser la table stripe_webhook_events si elle existe
        if (DB::getSchemaBuilder()->hasTable('stripe_webhook_events')) {
            return DB::table('stripe_webhook_events')
                ->whereIn('event_type', [
                    'customer.subscription.created',
                    'customer.subscription.updated',
                    'customer.subscription.deleted',
                    'invoice.paid',
                    'invoice.payment_failed',
                ])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        }

        return collect([]);
    }

    /**
     * Obtenir les derniers incidents Stripe
     * 
     * @param int $limit Nombre d'incidents à retourner
     * @return array
     */
    public function getRecentStripeIncidents(int $limit = 10): array
    {
        // Créateurs avec problèmes Stripe
        $incidents = CreatorProfile::whereHas('stripeAccount', function ($query) {
            $query->where(function ($q) {
                $q->where('charges_enabled', false)
                    ->orWhere('payouts_enabled', false)
                    ->orWhere('onboarding_status', 'failed');
            });
        })
            ->with(['stripeAccount', 'user'])
            ->limit($limit)
            ->get();

        return $incidents->map(function ($creator) {
            $account = $creator->stripeAccount;
            $issues = [];

            if (!$account->charges_enabled) {
                $issues[] = 'Charges désactivés';
            }
            if (!$account->payouts_enabled) {
                $issues[] = 'Payouts désactivés';
            }
            if ($account->onboarding_status === 'failed') {
                $issues[] = 'Onboarding échoué';
            }

            return [
                'creator_id' => $creator->id,
                'creator_name' => $creator->brand_name,
                'stripe_account_id' => $account->stripe_account_id,
                'issues' => $issues,
                'last_synced_at' => $account->last_synced_at,
            ];
        })->toArray();
    }

    /**
     * Obtenir toutes les métriques du dashboard
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return array
     */
    public function getDashboardMetrics(?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');

        return [
            'month' => $month,
            'revenue' => [
                'mrr' => $this->calculateMRR($month),
                'arr' => $this->calculateARR($month),
                'net_revenue' => $this->calculateNetRevenue($month),
            ],
            'subscriptions' => [
                'active' => $this->getTotalActiveSubscriptions(),
                'canceled_this_month' => $this->getTotalCanceledSubscriptions($month),
            ],
            'creators' => [
                'active' => $this->getActiveCreators(),
                'blocked' => $this->getBlockedCreators(),
                'in_onboarding' => $this->getCreatorsInOnboarding(),
                'at_risk' => $this->getCreatorsAtRisk(),
            ],
            'payments' => $this->getPaymentStats($month),
            'webhooks' => [
                'recent' => $this->getRecentWebhooks(10),
            ],
            'stripe_incidents' => $this->getRecentStripeIncidents(10),
        ];
    }
}


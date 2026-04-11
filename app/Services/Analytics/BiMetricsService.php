<?php

namespace App\Services\Analytics;

use App\Models\CreatorSubscription;
use App\Models\CreatorSubscriptionInvoice;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service BI - Métriques Centralisées
 * 
 * Module 7 - Analytics & BI
 * 
 * RÈGLE D'OR : READ-ONLY
 * - Aucune écriture en base
 * - Aucun automatisme déclencheur
 * - Calculs purs et testables
 * 
 * Toutes les formules BI sont centralisées ici pour :
 * - Cohérence des calculs
 * - Facilité de test
 * - Maintenance simplifiée
 */
class BiMetricsService
{
    /**
     * Calculer le MRR (Monthly Recurring Revenue)
     * 
     * Définition : MRR = somme des abonnements actifs normalisés mensuellement
     * 
     * Règles :
     * - Uniquement abonnements actifs ou en trial
     * - Uniquement plans payants (price > 0)
     * - Exclure les abonnements expirés (ends_at < fin du mois)
     * - Pas de double comptage
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return float MRR en XAF
     */
    public function calculateMRR(?string $month = null): float
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = "bi.metrics.mrr.{$month}";
        
        return Cache::remember($cacheKey, 1800, function () use ($month) {
            $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();
            
            // ✅ OPTIMISATION : Requête agrégée avec join pour éviter N+1 et eager loading
            $mrr = CreatorSubscription::whereIn('creator_subscriptions.status', ['active', 'trialing'])
                ->where(function ($query) use ($endOfMonth) {
                    $query->whereNull('creator_subscriptions.ends_at')
                        ->orWhere('creator_subscriptions.ends_at', '>=', $endOfMonth);
                })
                ->where('creator_subscriptions.started_at', '<=', $endOfMonth)
                ->join('creator_plans', 'creator_subscriptions.creator_plan_id', '=', 'creator_plans.id')
                ->where('creator_plans.price', '>', 0)
                ->where('creator_plans.code', '!=', 'free')
                ->selectRaw('COALESCE(SUM(creator_plans.price), 0) as total_mrr')
                ->value('total_mrr');
            
            return round((float) ($mrr ?? 0), 2);
        });
    }

    /**
     * Calculer l'ARR (Annual Recurring Revenue)
     * 
     * Définition : ARR = MRR × 12
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return float ARR en XAF
     */
    public function calculateARR(?string $month = null): float
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = "bi.metrics.arr.{$month}";
        
        return Cache::remember($cacheKey, 1800, function () use ($month) {
            $mrr = $this->calculateMRR($month);
            return round($mrr * 12, 2);
        });
    }

    /**
     * Calculer l'ARPU (Average Revenue Per User)
     * 
     * Définition : ARPU = MRR / Nombre de créateurs payants
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return float ARPU en XAF
     */
    public function calculateARPU(?string $month = null): float
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = "bi.metrics.arpu.{$month}";
        
        return Cache::remember($cacheKey, 1800, function () use ($month) {
            $mrr = $this->calculateMRR($month);
            
            // Nombre de créateurs payants (abonnements actifs avec prix > 0)
            $payingCreators = CreatorSubscription::whereIn('status', ['active', 'trialing'])
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                })
                ->whereHas('plan', function ($query) {
                    $query->where('price', '>', 0)
                        ->where('code', '!=', 'free');
                })
                ->distinct('creator_profile_id')
                ->count('creator_profile_id');
            
            if ($payingCreators === 0) {
                return 0.0;
            }
            
            return round($mrr / $payingCreators, 2);
        });
    }

    /**
     * Calculer le taux de Churn (créateurs)
     * 
     * Définition : Churn = (Abonnements annulés / Abonnements actifs début période) × 100
     * 
     * @param string $period 'month' ou 'year' (défaut: 'month')
     * @return float Taux de churn en pourcentage
     */
    public function calculateChurnRate(string $period = 'month'): float
    {
        $cacheKey = "bi.metrics.churn_rate.{$period}";
        
        return Cache::remember($cacheKey, 1800, function () use ($period) {
            if ($period === 'year') {
                $startDate = now()->subYear()->startOfYear();
                $endDate = now()->subYear()->endOfYear();
            } else {
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
            }

            // Abonnements actifs au début de la période
            $activeAtStart = CreatorSubscription::whereIn('status', ['active', 'trialing'])
                ->where('started_at', '<=', $startDate)
                ->where(function ($query) use ($startDate) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>', $startDate);
                })
                ->count();

            if ($activeAtStart === 0) {
                return 0.0;
            }

            // Abonnements annulés pendant la période
            $canceledDuringPeriod = CreatorSubscription::where('status', 'canceled')
                ->whereNotNull('canceled_at')
                ->whereBetween('canceled_at', [$startDate, $endDate])
                ->count();

            return round(($canceledDuringPeriod / $activeAtStart) * 100, 2);
        });
    }

    /**
     * Calculer le LTV (Lifetime Value) estimé
     * 
     * Définition : LTV = ARPU × Durée moyenne d'abonnement (en mois)
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return float LTV en XAF
     */
    public function calculateLTV(?string $month = null): float
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = "bi.metrics.ltv.{$month}";
        
        return Cache::remember($cacheKey, 1800, function () use ($month) {
            $arpu = $this->calculateARPU($month);
            $averageDuration = $this->calculateAverageSubscriptionDuration();
            
            return round($arpu * $averageDuration, 2);
        });
    }

    /**
     * Calculer la durée moyenne d'abonnement (en mois)
     * 
     * Utilise les abonnements annulés pour calculer la durée moyenne réelle.
     * Si aucun abonnement annulé, utilise les abonnements actifs.
     * 
     * @return float Durée moyenne en mois
     */
    public function calculateAverageSubscriptionDuration(): float
    {
        $cacheKey = 'bi.metrics.avg_subscription_duration';
        
        return Cache::remember($cacheKey, 1800, function () {
            // ✅ OPTIMISATION : Requête agrégée pour calculer la moyenne
            $canceledSubscriptions = CreatorSubscription::where('status', 'canceled')
                ->whereNotNull('started_at')
                ->whereNotNull('canceled_at')
                ->selectRaw('
                    AVG(DATEDIFF(canceled_at, started_at) / 30.0) as avg_months
                ')
                ->value('avg_months');
            
            if ($canceledSubscriptions && $canceledSubscriptions > 0) {
                return round((float) $canceledSubscriptions, 2);
            }
            
            // Fallback : utiliser les abonnements actifs
            $activeSubscriptions = CreatorSubscription::whereIn('status', ['active', 'trialing'])
                ->whereNotNull('started_at')
                ->selectRaw('
                    AVG(DATEDIFF(NOW(), started_at) / 30.0) as avg_months
                ')
                ->value('avg_months');
            
            return round((float) ($activeSubscriptions ?? 0), 2);
        });
    }

    /**
     * Calculer le taux de conversion checkout
     * 
     * Définition : Conversion = (Commandes payées / Sessions checkout) × 100
     * 
     * @param Carbon $startDate Date de début
     * @param Carbon $endDate Date de fin
     * @return float Taux de conversion en pourcentage
     */
    public function calculateCheckoutConversionRate(Carbon $startDate, Carbon $endDate): float
    {
        $cacheKey = "bi.metrics.checkout_conversion.{$startDate->format('Y-m-d')}.{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            // Commandes payées dans la période
            $paidOrders = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            
            // Sessions checkout (approximation : toutes les commandes créées)
            // Note : Dans un vrai système, on utiliserait une table de sessions checkout
            $checkoutSessions = Order::whereBetween('created_at', [$startDate, $endDate])
                ->count();
            
            if ($checkoutSessions === 0) {
                return 0.0;
            }
            
            return round(($paidOrders / $checkoutSessions) * 100, 2);
        });
    }

    /**
     * Calculer les revenus par créateur
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return array ['creator_id' => revenue, ...]
     */
    public function calculateRevenueByCreator(?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = "bi.metrics.revenue_by_creator.{$month}";
        
        return Cache::remember($cacheKey, 1800, function () use ($month) {
            $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
            $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();
            
            // ✅ OPTIMISATION : Requête agrégée unique
            $revenues = CreatorSubscriptionInvoice::where('status', 'paid')
                ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                ->join('creator_subscriptions', 'creator_subscription_invoices.subscription_id', '=', 'creator_subscriptions.id')
                ->selectRaw('
                    creator_subscriptions.creator_profile_id,
                    SUM(creator_subscription_invoices.amount) as total_revenue
                ')
                ->groupBy('creator_subscriptions.creator_profile_id')
                ->pluck('total_revenue', 'creator_profile_id')
                ->toArray();
            
            return array_map('floatval', $revenues);
        });
    }

    /**
     * Calculer les revenus par canal
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return array ['channel' => revenue, ...]
     */
    public function calculateRevenueByChannel(?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = "bi.metrics.revenue_by_channel.{$month}";
        
        return Cache::remember($cacheKey, 1800, function () use ($month) {
            $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
            $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();
            
            // ✅ OPTIMISATION : Requête agrégée unique
            $revenues = Payment::where('status', 'paid')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->selectRaw('
                    payment_method as channel,
                    SUM(amount) as total_revenue
                ')
                ->groupBy('payment_method')
                ->pluck('total_revenue', 'channel')
                ->toArray();
            
            return array_map('floatval', $revenues);
        });
    }

    /**
     * Obtenir toutes les métriques BI en une seule fois
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return array Toutes les métriques
     */
    public function getAllMetrics(?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        $cacheKey = "bi.metrics.all.{$month}";
        
        return Cache::remember($cacheKey, 1800, function () use ($month) {
            return [
                'mrr' => $this->calculateMRR($month),
                'arr' => $this->calculateARR($month),
                'arpu' => $this->calculateARPU($month),
                'ltv' => $this->calculateLTV($month),
                'churn_rate_month' => $this->calculateChurnRate('month'),
                'churn_rate_year' => $this->calculateChurnRate('year'),
                'avg_subscription_duration' => $this->calculateAverageSubscriptionDuration(),
                'revenue_by_creator' => $this->calculateRevenueByCreator($month),
                'revenue_by_channel' => $this->calculateRevenueByChannel($month),
            ];
        });
    }
}


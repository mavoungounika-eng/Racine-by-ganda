<?php

namespace App\DTO\BI;

/**
 * DTO - Snapshot Financier
 * 
 * Phase 6.5 - Préparation IA / BI Externe
 * 
 * Structure de données pour export Power BI, Metabase, ou module IA futur
 */
class FinancialSnapshotDTO
{
    public function __construct(
        public readonly array $revenueMetrics,
        public readonly array $subscriptionMetrics,
        public readonly array $creatorMetrics,
        public readonly array $stripeHealthMetrics,
        public readonly array $riskMetrics,
        public readonly array $advancedKpis,
        public readonly array $alerts,
        public readonly string $snapshotDate,
        public readonly string $period
    ) {
    }

    /**
     * Convertir en tableau pour export JSON
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'snapshot_date' => $this->snapshotDate,
            'period' => $this->period,
            'revenue' => $this->revenueMetrics,
            'subscriptions' => $this->subscriptionMetrics,
            'creators' => $this->creatorMetrics,
            'stripe_health' => $this->stripeHealthMetrics,
            'risks' => $this->riskMetrics,
            'advanced_kpis' => $this->advancedKpis,
            'alerts' => $this->alerts,
        ];
    }

    /**
     * Convertir en JSON
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}




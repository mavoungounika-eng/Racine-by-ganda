<?php

namespace App\DTO\Decision;

/**
 * DTO - Snapshot Décisionnel Créateur
 * 
 * Phase 7.4 - Structure de données pour export BI / IA
 */
class CreatorDecisionSnapshotDTO
{
    public function __construct(
        public readonly int $creatorId,
        public readonly string $creatorName,
        public readonly array $decisionScore,
        public readonly array $churnPrediction,
        public readonly array $recommendations,
        public readonly array $riskAssessment,
        public readonly array $alerts,
        public readonly string $snapshotDate,
        public readonly array $metadata
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
            'creator_id' => $this->creatorId,
            'creator_name' => $this->creatorName,
            'snapshot_date' => $this->snapshotDate,
            'decision_score' => $this->decisionScore,
            'churn_prediction' => $this->churnPrediction,
            'recommendations' => $this->recommendations,
            'risk_assessment' => $this->riskAssessment,
            'alerts' => $this->alerts,
            'metadata' => $this->metadata,
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




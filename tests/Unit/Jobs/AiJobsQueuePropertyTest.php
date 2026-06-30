<?php

namespace Tests\Unit\Jobs;

use App\Jobs\AI\AnalyzeCreatorSales;
use App\Jobs\AI\DetectStockAnomalies;
use App\Jobs\AI\GenerateAdminSummary;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

/**
 * Régression T-01 — AI Jobs queue property conflict.
 *
 * Ces 3 jobs déclaraient `public string $queue = 'ai-processing';` alors que le trait
 * `Illuminate\Bus\Queueable` déclare `public $queue;` (sans type).
 *
 * PHP considère les deux définitions incompatibles et émet un FatalError à la composition
 * de la classe — bloquant TOUT dispatch du job.
 *
 * Fix : retirer la propriété typée et appeler `$this->onQueue('ai-processing')` dans
 * le constructeur.
 *
 * Ce test verrouille la non-régression : si un futur refactor réintroduit
 * `public string $queue = ...` sur un des jobs AI, les tests de l'instanciation
 * tombent avant même d'arriver à l'assertion.
 */
class AiJobsQueuePropertyTest extends TestCase
{
    public static function aiJobsProvider(): array
    {
        return [
            'DetectStockAnomalies' => [DetectStockAnomalies::class],
            'AnalyzeCreatorSales' => [AnalyzeCreatorSales::class],
            'GenerateAdminSummary' => [GenerateAdminSummary::class],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('aiJobsProvider')]
    public function job_can_be_instantiated_without_fatal_error(string $jobClass): void
    {
        // L'ancien bug provoquait un FatalError à la composition de la classe:
        //   "define the same property ($queue) ... definition differs and is considered incompatible"
        // Si cette ligne passe, la composition de traits est saine.
        $job = new $jobClass();

        $this->assertInstanceOf($jobClass, $job);
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('aiJobsProvider')]
    public function job_is_routed_to_ai_processing_queue(string $jobClass): void
    {
        $job = new $jobClass();

        $this->assertSame(
            'ai-processing',
            $job->queue,
            "Job {$jobClass} doit être routé sur la queue 'ai-processing' via onQueue() constructeur."
        );
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('aiJobsProvider')]
    public function job_does_not_redeclare_queue_property_with_incompatible_type(string $jobClass): void
    {
        $ref = new ReflectionClass($jobClass);
        $queueProp = $ref->getProperty('queue');

        // La propriété $queue doit venir du trait Queueable (déclarante = parent lookup)
        // et ne doit PAS être redéclarée dans la classe elle-même avec un type différent.
        $this->assertSame(
            \Illuminate\Bus\Queueable::class,
            $this->findPropertyOriginTrait($ref, 'queue'),
            "La propriété \$queue de {$jobClass} doit rester définie par le trait Queueable; ne pas la redéclarer dans la classe."
        );

        $this->assertNull(
            $queueProp->getType(),
            "Ne pas typer la propriété \$queue — le trait Queueable la déclare sans type et PHP rejette toute divergence."
        );
    }

    /**
     * Remonte la hiérarchie de traits pour trouver le trait d'origine d'une propriété.
     */
    private function findPropertyOriginTrait(ReflectionClass $ref, string $propertyName): ?string
    {
        foreach ($ref->getTraits() as $trait) {
            if ($trait->hasProperty($propertyName)) {
                return $trait->getName();
            }
            if ($origin = $this->findPropertyOriginTrait($trait, $propertyName)) {
                return $origin;
            }
        }

        return null;
    }
}

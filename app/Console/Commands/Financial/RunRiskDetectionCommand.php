<?php

namespace App\Console\Commands\Financial;

use App\Services\Financial\RiskDetectionService;
use Illuminate\Console\Command;

/**
 * Commande pour exécuter la détection automatique des risques
 * 
 * Phase 6.3 - Détection Automatique des Risques
 * 
 * Usage: php artisan financial:detect-risks
 */
class RunRiskDetectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial:detect-risks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Détecter automatiquement les créateurs à risque';

    /**
     * Execute the console command.
     */
    public function handle(RiskDetectionService $riskService): int
    {
        $this->info('Démarrage de la détection automatique des risques...');

        $riskService->runRiskDetection();

        $statistics = $riskService->getRiskStatistics();

        $this->info("Détection terminée.");
        $this->table(
            ['Niveau', 'Nombre'],
            [
                ['Critique', $statistics['by_level']['critical']],
                ['Élevé', $statistics['by_level']['high']],
                ['Moyen', $statistics['by_level']['medium']],
                ['Total', $statistics['total_at_risk']],
            ]
        );

        return Command::SUCCESS;
    }
}


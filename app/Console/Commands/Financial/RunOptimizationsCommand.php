<?php

namespace App\Console\Commands\Financial;

use App\Services\Financial\SubscriptionOptimizationService;
use Illuminate\Console\Command;

/**
 * Commande pour exécuter les optimisations automatiques
 * 
 * Phase 6.4 - Optimisation Automatique
 * 
 * Usage: php artisan financial:optimize
 */
class RunOptimizationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exécuter les optimisations automatiques des abonnements';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionOptimizationService $optimizationService): int
    {
        $this->info('Démarrage des optimisations automatiques...');

        $results = $optimizationService->runOptimizations();

        $this->info("Optimisations terminées.");
        $this->table(
            ['Action', 'Nombre'],
            [
                ['Suspendus (unpaid)', $results['suspended']],
                ['Downgradés (expirés)', $results['downgraded']],
                ['Réactivés', $results['reactivated']],
            ]
        );

        return Command::SUCCESS;
    }
}


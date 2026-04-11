<?php

namespace App\Console\Commands;

use App\Jobs\DowngradeExpiredSubscriptions as DowngradeExpiredSubscriptionsJob;
use Illuminate\Console\Command;

/**
 * Commande pour vérifier et downgrader les abonnements expirés.
 * 
 * PHASE 9: Downgrade automatique vers FREE si expiration
 */
class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'creator:check-expired-subscriptions 
                            {--dry-run : Afficher sans modifier}
                            {--force : Forcer le downgrade même si récent}';

    protected $description = 'Vérifie les abonnements expirés et les downgrade vers FREE';

    public function handle(): int
    {
        $this->info('🔍 Vérification des abonnements expirés...');

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Mode DRY-RUN activé - Aucune modification ne sera effectuée');
        }

        // Dispatch le job pour traiter les expirations
        DowngradeExpiredSubscriptionsJob::dispatch($dryRun);

        $this->info('✅ Job de downgrade dispatché avec succès');

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\ERP\Services\StockAlertService;

class CheckStockAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:check-stock-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les stocks faibles et envoie des alertes aux administrateurs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Vérification des alertes de stock...');
        
        $service = app(StockAlertService::class);
        $service->checkLowStockAlerts();
        
        $this->info('Vérification terminée. Alertes envoyées si nécessaire.');
        
        return Command::SUCCESS;
    }
}

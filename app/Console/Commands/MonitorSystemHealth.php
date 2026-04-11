<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Monitoring\HealthCheckService;
use App\Services\Monitoring\QueueMonitorService;
use App\Services\Monitoring\AlertService;
use Illuminate\Support\Facades\Log;

/**
 * MonitorSystemHealth - Surveillance automatique proactive
 * 
 * S'exécute périodiquement (via scheduler) pour :
 * 1. Vérifier l'état de tous les composants
 * 2. Analyser les seuils de queues
 * 3. Déclencher des alertes Slack/Email si anomalie
 */
class MonitorSystemHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:health {--notify : Envoyer des notifications si erreurs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier la santé du système et alerter en cas de problème';

    protected HealthCheckService $healthCheck;
    protected QueueMonitorService $queueMonitor;
    protected AlertService $alertService;

    public function __construct(
        HealthCheckService $healthCheck,
        QueueMonitorService $queueMonitor,
        AlertService $alertService
    ) {
        parent::__construct();
        $this->healthCheck = $healthCheck;
        $this->queueMonitor = $queueMonitor;
        $this->alertService = $alertService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('[' . now()->toDateTimeString() . '] Starting health check...');

        // 1. Check Global Health
        $health = $this->healthCheck->checkAll();
        $this->displayHealth($health);

        // 2. Check Queues specifically
        $queueAlerts = $this->queueMonitor->checkThresholds('default', 500, 120);
        
        // 3. Process Alerts
        $this->processAlerts($health, $queueAlerts);

        $this->info('Health check completed.');
        return 0;
    }

    /**
     * Afficher l'état dans la console
     */
    protected function displayHealth(array $health): void
    {
        $rows = [];
        foreach ($health['components'] as $name => $data) {
            $rows[] = [
                ucfirst($name),
                $data['status'] === 'ok' ? '<info>OK</info>' : '<error>FAIL</error>',
                $data['latency_ms'] ?? $data['usage_percent'] ?? 'N/A'
            ];
        }

        $this->table(['Component', 'Status', 'Metric'], $rows);
    }

    /**
     * Analyser et envoyer des notifications si nécessaire
     */
    protected function processAlerts(array $health, array $queueAlerts): void
    {
        if (!$this->option('notify')) {
            return;
        }

        // Alertes sur composants critiques
        foreach ($health['components'] as $name => $data) {
            if ($data['status'] === 'fail') {
                $this->alertService->critical(
                    "CRITICAL: Component Failure [{$name}]",
                    "The component '{$name}' is reporting a failure status in environment: " . config('app.env'),
                    ['details' => $data]
                );
            }
        }

        // Alertes sur les queues
        foreach ($queueAlerts as $alert) {
            $method = $alert['severity'] === 'critical' ? 'critical' : 'high';
            $this->alertService->$method(
                "Queue Warning: {$alert['type']}",
                $alert['message'],
                ['alert' => $alert]
            );
        }
    }
}

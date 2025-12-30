<?php

namespace App\Console\Commands;

use App\Models\PaymentAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PrunePaymentAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:prune-audit-logs 
                            {--days= : Nombre de jours de rétention (défaut: config)}
                            {--dry-run : Mode simulation (n\'efface rien)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge les logs d\'audit paiements anciens selon la politique de rétention';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days') ?? config('payments.audit_logs.retention_days', 365);
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($days);

        $this->info("Purge des logs d'audit antérieurs au : {$cutoffDate->format('Y-m-d H:i:s')}");
        if ($dryRun) {
            $this->warn("Mode DRY-RUN : aucune suppression ne sera effectuée.");
        }

        $query = PaymentAuditLog::where('created_at', '<', $cutoffDate);
        $count = $query->count();

        if ($dryRun) {
            $this->line("Logs d'audit à supprimer : {$count}");
        } else {
            $deleted = $query->delete();
            $this->info("Logs d'audit supprimés : {$deleted}");
            Log::info('Payment audit logs pruned', [
                'deleted_count' => $deleted,
                'cutoff_date' => $cutoffDate->toIso8601String(),
            ]);
        }

        return Command::SUCCESS;
    }
}





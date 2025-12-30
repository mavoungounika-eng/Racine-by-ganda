<?php

namespace App\Console\Commands\Payments;

use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneWebhookEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:prune-webhook-events 
                            {--days= : Nombre de jours de rétention (override config)}
                            {--dry-run : Afficher ce qui serait supprimé sans supprimer}
                            {--force : Forcer la suppression même des received/failed récents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune les événements webhook/callback anciens selon la politique de rétention';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = (int) ($this->option('days') ?? config('payments.events.retention_days', 90));
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $keepFailed = config('payments.events.keep_failed', true);

        if ($retentionDays < 1) {
            $this->error('Le nombre de jours doit être >= 1');
            return Command::FAILURE;
        }

        $cutoffDate = now()->subDays($retentionDays);

        $this->info("Prune des événements webhooks (rétention: {$retentionDays} jours, avant {$cutoffDate->format('Y-m-d H:i:s')})");
        if ($dryRun) {
            $this->warn('Mode DRY-RUN : aucune suppression ne sera effectuée');
        }

        $stats = [
            'stripe' => ['scanned' => 0, 'deleted' => 0],
            'monetbil' => ['scanned' => 0, 'deleted' => 0],
        ];

        // Stripe webhook events
        // Ne jamais supprimer received/failed/blocked sauf si force
        $stripeQuery = StripeWebhookEvent::where('created_at', '<', $cutoffDate)
            ->whereIn('status', ['processed', 'ignored']);

        if ($force) {
            // En mode force, on peut supprimer même received/failed/blocked
            $stripeQuery = StripeWebhookEvent::where('created_at', '<', $cutoffDate);
        } elseif ($keepFailed) {
            // Par défaut, garder les failed même anciens
            $stripeQuery->whereNotIn('status', ['failed', 'blocked']);
        } else {
            // Ne jamais supprimer blocked même en mode non-force
            $stripeQuery->whereNotIn('status', ['blocked']);
        }

        $stripeCount = $stripeQuery->count();
        $stats['stripe']['scanned'] = $stripeCount;

        if ($stripeCount > 0) {
            if ($dryRun) {
                $this->line("Stripe: {$stripeCount} événements seraient supprimés");
            } else {
                $deleted = $stripeQuery->delete();
                $stats['stripe']['deleted'] = $deleted;
                $this->info("Stripe: {$deleted} événements supprimés");
                Log::info('PruneWebhookEventsCommand: Stripe events pruned', [
                    'count' => $deleted,
                    'retention_days' => $retentionDays,
                    'cutoff_date' => $cutoffDate->toIso8601String(),
                ]);
            }
        }

        // Monetbil callback events
        // Ne jamais supprimer received/failed/blocked sauf si force
        $monetbilQuery = MonetbilCallbackEvent::where('created_at', '<', $cutoffDate)
            ->whereIn('status', ['processed', 'ignored']);

        if ($force) {
            // En mode force, on peut supprimer même received/failed/blocked
            $monetbilQuery = MonetbilCallbackEvent::where('created_at', '<', $cutoffDate);
        } elseif ($keepFailed) {
            // Par défaut, garder les failed même anciens
            $monetbilQuery->whereNotIn('status', ['failed', 'blocked']);
        } else {
            // Ne jamais supprimer blocked même en mode non-force
            $monetbilQuery->whereNotIn('status', ['blocked']);
        }

        $monetbilCount = $monetbilQuery->count();
        $stats['monetbil']['scanned'] = $monetbilCount;

        if ($monetbilCount > 0) {
            if ($dryRun) {
                $this->line("Monetbil: {$monetbilCount} événements seraient supprimés");
            } else {
                $deleted = $monetbilQuery->delete();
                $stats['monetbil']['deleted'] = $deleted;
                $this->info("Monetbil: {$deleted} événements supprimés");
                Log::info('PruneWebhookEventsCommand: Monetbil events pruned', [
                    'count' => $deleted,
                    'retention_days' => $retentionDays,
                    'cutoff_date' => $cutoffDate->toIso8601String(),
                ]);
            }
        }

        $totalScanned = $stats['stripe']['scanned'] + $stats['monetbil']['scanned'];
        $totalDeleted = $stats['stripe']['deleted'] + $stats['monetbil']['deleted'];

        $this->newLine();
        if ($dryRun) {
            $this->info("DRY-RUN: {$totalScanned} événements seraient supprimés");
        } else {
            $this->info("Prune terminé: {$totalScanned} scannés, {$totalDeleted} supprimés");
        }

        return Command::SUCCESS;
    }
}





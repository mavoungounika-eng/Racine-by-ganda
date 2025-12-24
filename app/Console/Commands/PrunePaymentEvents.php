<?php

namespace App\Console\Commands;

use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PrunePaymentEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:prune-events 
                            {--days= : Nombre de jours de rétention (défaut: config)}
                            {--dry-run : Mode simulation (n\'efface rien)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge les événements webhook/callback anciens selon la politique de rétention';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days') ?? config('payments.events.retention_days', 90);
        $keepFailed = config('payments.events.keep_failed', true);
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($days);

        $this->info("Purge des événements antérieurs au : {$cutoffDate->format('Y-m-d H:i:s')}");
        if ($keepFailed) {
            $this->info("Conservation des événements 'failed' activée.");
        }
        if ($dryRun) {
            $this->warn("Mode DRY-RUN : aucune suppression ne sera effectuée.");
        }

        // Stripe events
        $stripeQuery = StripeWebhookEvent::where('created_at', '<', $cutoffDate);
        if ($keepFailed) {
            $stripeQuery->where('status', '!=', 'failed');
        }
        $stripeCount = $stripeQuery->count();

        if ($dryRun) {
            $this->line("Stripe events à supprimer : {$stripeCount}");
        } else {
            $stripeDeleted = $stripeQuery->delete();
            $this->info("Stripe events supprimés : {$stripeDeleted}");
            Log::info('Payment events pruned', [
                'provider' => 'stripe',
                'deleted_count' => $stripeDeleted,
                'cutoff_date' => $cutoffDate->toIso8601String(),
            ]);
        }

        // Monetbil events
        $monetbilQuery = MonetbilCallbackEvent::where('created_at', '<', $cutoffDate);
        if ($keepFailed) {
            $monetbilQuery->where('status', '!=', 'failed');
        }
        $monetbilCount = $monetbilQuery->count();

        if ($dryRun) {
            $this->line("Monetbil events à supprimer : {$monetbilCount}");
        } else {
            $monetbilDeleted = $monetbilQuery->delete();
            $this->info("Monetbil events supprimés : {$monetbilDeleted}");
            Log::info('Payment events pruned', [
                'provider' => 'monetbil',
                'deleted_count' => $monetbilDeleted,
                'cutoff_date' => $cutoffDate->toIso8601String(),
            ]);
        }

        $total = $stripeCount + $monetbilCount;
        if ($dryRun) {
            $this->info("Total events à supprimer : {$total}");
        } else {
            $this->info("Purge terminée. Total supprimé : {$total}");
        }

        return Command::SUCCESS;
    }
}





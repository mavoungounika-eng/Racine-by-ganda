<?php

namespace App\Console\Commands\Payments;

use App\Jobs\ProcessMonetbilCallbackEventJob;
use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use App\Services\Payments\WebhookRequeueGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequeueStuckWebhookEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:requeue-stuck-webhooks 
                            {--minutes=10 : Seuil en minutes pour considérer un event comme "stuck"}
                            {--provider=all : Provider à traiter (stripe, monetbil, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Requeue les événements webhook/callback "stuck" (non dispatchés ou failed anciens)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $provider = $this->option('provider');

        if ($minutes < 1) {
            $this->error('Le seuil --minutes doit être >= 1');
            return Command::FAILURE;
        }

        $threshold = now()->subMinutes($minutes);

        $this->info("Requeue des événements stuck (seuil: {$minutes} minutes, avant {$threshold->format('Y-m-d H:i:s')})");

        $stats = [
            'stripe' => ['scanned' => 0, 'dispatched' => 0, 'skipped' => 0],
            'monetbil' => ['scanned' => 0, 'dispatched' => 0, 'skipped' => 0],
        ];

        // Traiter Stripe
        if ($provider === 'all' || $provider === 'stripe') {
            $stats['stripe'] = $this->requeueStripeEvents($threshold);
        }

        // Traiter Monetbil
        if ($provider === 'all' || $provider === 'monetbil') {
            $stats['monetbil'] = $this->requeueMonetbilEvents($threshold);
        }

        // Afficher le résumé
        $this->displaySummary($stats);

        return Command::SUCCESS;
    }

    /**
     * Requeue les événements Stripe stuck
     *
     * @param \Carbon\Carbon $threshold
     * @return array
     */
    private function requeueStripeEvents($threshold): array
    {
        $stats = ['scanned' => 0, 'dispatched' => 0, 'skipped' => 0];

        // Sélectionner les événements éligibles (stuck)
        // Note: Le filtrage par garde-fou se fait après la requête pour éviter les problèmes SQLite
        $events = StripeWebhookEvent::whereIn('status', ['received', 'failed'])
            ->where(function ($query) use ($threshold) {
                $query->whereNull('dispatched_at')
                    ->orWhere(function ($q) use ($threshold) {
                        $q->where('status', 'failed')
                            ->whereNotNull('dispatched_at')
                            ->where('dispatched_at', '<', $threshold);
                    });
            })
            ->where('created_at', '>=', $threshold->copy()->subDays(7)) // Limiter à 7 jours max
            ->get()
            ->filter(function ($event) {
                // Filtrer via service centralisé (garde-fou anti-boucle)
                return WebhookRequeueGuard::canRequeueStripe($event);
            });

        $stats['scanned'] = $events->count();

        foreach ($events as $event) {
            // Skip si status final
            if ($event->isProcessed()) {
                $stats['skipped']++;
                continue;
            }

            $dispatched = false;

            // Atomic claim 1 : dispatched_at IS NULL
            if ($event->dispatched_at === null) {
                $rowsAffected = DB::table('stripe_webhook_events')
                    ->where('id', $event->id)
                    ->whereNull('dispatched_at')
                    ->update([
                        'dispatched_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($rowsAffected === 1) {
                    try {
                        ProcessStripeWebhookEventJob::dispatch($event->id);
                        // Mettre à jour requeue_count et last_requeue_at
                        DB::table('stripe_webhook_events')
                            ->where('id', $event->id)
                            ->update([
                                'requeue_count' => DB::raw('requeue_count + 1'),
                                'last_requeue_at' => now(),
                                'updated_at' => now(),
                            ]);
                        $dispatched = true;
                        $stats['dispatched']++;
                        Log::info('RequeueStuckWebhookEvents: Stripe event requeued', [
                            'event_id' => $event->event_id,
                            'event_type' => $event->event_type,
                            'reason' => 'dispatched_at_null',
                        ]);
                    } catch (\Exception $e) {
                        Log::error('RequeueStuckWebhookEvents: Failed to dispatch Stripe job', [
                            'event_id' => $event->event_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Atomic claim 2 : failed + dispatched_at old
            if (!$dispatched && $event->status === 'failed' && $event->dispatched_at && $event->dispatched_at->lt($threshold)) {
                $rowsAffected = DB::table('stripe_webhook_events')
                    ->where('id', $event->id)
                    ->where('status', 'failed')
                    ->whereNotNull('dispatched_at')
                    ->where('dispatched_at', '<', $threshold)
                    ->update([
                        'dispatched_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($rowsAffected === 1) {
                    try {
                        ProcessStripeWebhookEventJob::dispatch($event->id);
                        // Mettre à jour requeue_count et last_requeue_at
                        DB::table('stripe_webhook_events')
                            ->where('id', $event->id)
                            ->update([
                                'requeue_count' => DB::raw('requeue_count + 1'),
                                'last_requeue_at' => now(),
                                'updated_at' => now(),
                            ]);
                        $dispatched = true;
                        $stats['dispatched']++;
                        Log::info('RequeueStuckWebhookEvents: Stripe event requeued', [
                            'event_id' => $event->event_id,
                            'event_type' => $event->event_type,
                            'reason' => 'failed_old',
                        ]);
                    } catch (\Exception $e) {
                        Log::error('RequeueStuckWebhookEvents: Failed to redispatch Stripe job', [
                            'event_id' => $event->event_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if (!$dispatched) {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    /**
     * Requeue les événements Monetbil stuck
     *
     * @param \Carbon\Carbon $threshold
     * @return array
     */
    private function requeueMonetbilEvents($threshold): array
    {
        $stats = ['scanned' => 0, 'dispatched' => 0, 'skipped' => 0];

        // Sélectionner les événements éligibles (stuck)
        $events = MonetbilCallbackEvent::whereIn('status', ['received', 'failed'])
            ->where(function ($query) use ($threshold) {
                $query->whereNull('dispatched_at')
                    ->orWhere(function ($q) use ($threshold) {
                        $q->where('status', 'failed')
                            ->whereNotNull('dispatched_at')
                            ->where('dispatched_at', '<', $threshold);
                    });
            })
            ->where('created_at', '>=', $threshold->copy()->subDays(7)) // Limiter à 7 jours max
            ->get()
            ->filter(function ($event) {
                // Filtrer via service centralisé (garde-fou anti-boucle)
                return WebhookRequeueGuard::canRequeueMonetbil($event);
            });

        // Le filtre a déjà été appliqué, donc on compte directement
        $stats['scanned'] = $events->count();

        foreach ($events as $event) {
            // Le garde-fou a déjà été appliqué dans le filtre, mais on vérifie quand même
            // pour éviter les problèmes de race condition
            if (!WebhookRequeueGuard::canRequeueMonetbil($event)) {
                // Tenter de marquer comme blocked si limite atteinte
                WebhookRequeueGuard::markMonetbilAsBlockedIfNeeded($event);
                $stats['skipped']++;
                continue;
            }

            // Skip si status final (double vérification)
            if (in_array($event->status, ['processed', 'ignored'])) {
                $stats['skipped']++;
                continue;
            }

            $dispatched = false;

            // Atomic claim 1 : dispatched_at IS NULL
            if ($event->dispatched_at === null) {
                $rowsAffected = DB::table('monetbil_callback_events')
                    ->where('id', $event->id)
                    ->whereNull('dispatched_at')
                    ->update([
                        'dispatched_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($rowsAffected === 1) {
                    try {
                        ProcessMonetbilCallbackEventJob::dispatch($event->id);
                        $dispatched = true;
                        $stats['dispatched']++;
                        Log::info('RequeueStuckWebhookEvents: Monetbil event requeued', [
                            'event_key' => $event->event_key,
                            'event_type' => $event->event_type,
                            'reason' => 'dispatched_at_null',
                        ]);
                    } catch (\Exception $e) {
                        Log::error('RequeueStuckWebhookEvents: Failed to dispatch Monetbil job', [
                            'event_key' => $event->event_key,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Atomic claim 2 : failed + dispatched_at old
            if (!$dispatched && $event->status === 'failed' && $event->dispatched_at && $event->dispatched_at->lt($threshold)) {
                $rowsAffected = DB::table('monetbil_callback_events')
                    ->where('id', $event->id)
                    ->where('status', 'failed')
                    ->whereNotNull('dispatched_at')
                    ->where('dispatched_at', '<', $threshold)
                    ->update([
                        'dispatched_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($rowsAffected === 1) {
                    try {
                        ProcessMonetbilCallbackEventJob::dispatch($event->id);
                        // Mettre à jour requeue_count et last_requeue_at (atomic)
                        DB::table('monetbil_callback_events')
                            ->where('id', $event->id)
                            ->where(function ($query) {
                                $query->where('requeue_count', '<', WebhookRequeueGuard::getMaxRequeuePerHour())
                                    ->orWhereNull('last_requeue_at')
                                    ->orWhere('last_requeue_at', '<=', now()->subHour());
                            })
                            ->update([
                                'requeue_count' => DB::raw('requeue_count + 1'),
                                'last_requeue_at' => now(),
                                'updated_at' => now(),
                            ]);
                        $dispatched = true;
                        $stats['dispatched']++;
                        Log::info('RequeueStuckWebhookEvents: Monetbil event requeued', [
                            'event_key' => $event->event_key,
                            'event_type' => $event->event_type,
                            'reason' => 'failed_old',
                        ]);
                    } catch (\Exception $e) {
                        Log::error('RequeueStuckWebhookEvents: Failed to redispatch Monetbil job', [
                            'event_key' => $event->event_key,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if (!$dispatched) {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    /**
     * Afficher le résumé des statistiques
     *
     * @param array $stats
     * @return void
     */
    private function displaySummary(array $stats): void
    {
        $this->newLine();
        $this->info('=== Résumé ===');

        foreach ($stats as $provider => $providerStats) {
            if ($providerStats['scanned'] > 0) {
                $this->line("{$provider}:");
                $this->line("  Scannés: {$providerStats['scanned']}");
                $this->line("  Dispatchés: {$providerStats['dispatched']}");
                $this->line("  Ignorés: {$providerStats['skipped']}");
            }
        }

        $totalScanned = $stats['stripe']['scanned'] + $stats['monetbil']['scanned'];
        $totalDispatched = $stats['stripe']['dispatched'] + $stats['monetbil']['dispatched'];
        $totalSkipped = $stats['stripe']['skipped'] + $stats['monetbil']['skipped'];

        $this->newLine();
        $this->info("Total: {$totalScanned} scannés, {$totalDispatched} dispatchés, {$totalSkipped} ignorés");
    }
}

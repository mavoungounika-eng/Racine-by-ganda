<?php

namespace App\Console\Commands;

use App\Models\WebhookFailure;
use App\Services\Webhooks\WebhookDeduplicationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WebhookRetryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:retry {provider?} {--limit=10} {--all} {--force}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Retry failed webhooks (Stripe, Monetbil, etc)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $provider = $this->argument('provider');
        $limit = (int) $this->option('limit');
        $all = $this->option('all');
        $force = $this->option('force');

        $service = app(WebhookDeduplicationService::class);

        // Valider le provider si spécifié
        if ($provider && !in_array(strtolower($provider), ['stripe', 'monetbil', 'paypal'])) {
            $this->error("❌ Provider invalide: {$provider}");
            $this->info('Providers supportés: stripe, monetbil, paypal');
            return 1;
        }

        // Récupérer les webhooks à retrirer
        $query = WebhookFailure::where('status', '!=', 'processed');

        if ($provider) {
            $query->where('provider', strtolower($provider));
        }

        if (!$all) {
            $query->where('retry_count', '<', 3); // Max 3 retries
        }

        $failures = $query->orderBy('created_at', 'asc')->limit($limit)->get();

        if ($failures->isEmpty()) {
            $this->info('✅ Aucun webhook à retrirer');
            return 0;
        }

        $this->info("🔄 Retrirage de {$failures->count()} webhooks...\n");

        $successCount = 0;
        $failureCount = 0;

        foreach ($failures as $failure) {
            $this->line("Processing: {$failure->provider} #{$failure->id} ({$failure->event_type})");

            // Check retry count
            if ($failure->retry_count >= 3 && !$force) {
                $this->warn("  ⏭️  Max retries reached (count: {$failure->retry_count})");
                continue;
            }

            // Try to retry
            try {
                $result = $service->retry($failure, function ($payload) {
                    // Reconstruct handler based on provider
                    // This is a simplified handler - in production, would dispatch to proper job
                    Log::info('Webhook retry handler called', [
                        'provider' => $this->argument('provider') ?? 'unknown',
                        'external_id' => $payload['external_id'] ?? 'unknown',
                    ]);
                });

                if ($result) {
                    $this->info('  ✅ Succès');
                    $successCount++;
                } else {
                    $this->warn('  ❌ Échec du retry (max attempts atteint)');
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $this->error("  💥 Exception: " . $e->getMessage());
                $failureCount++;
                Log::error('Webhook retry command exception', [
                    'webhook_id' => $failure->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Summary
        $this->line("\n" . str_repeat('=', 50));
        $this->info("📊 Résumé:");
        $this->info("  ✅ Succès: {$successCount}");
        $this->error("  ❌ Échecs: {$failureCount}");
        $this->line(str_repeat('=', 50));

        // Statistics
        $stats = $service->getStatistics();
        $this->line("\n📈 Statistiques globales:");
        $this->info("  Total: {$stats['total']}");
        $this->warn("  Pending: {$stats['pending']}");
        $this->error("  Dead Letter: {$stats['dead_letter']}");

        if (!empty($stats['by_provider'])) {
            $this->line("\n  Par provider:");
            foreach ($stats['by_provider'] as $prov => $count) {
                $this->info("    - {$prov}: {$count}");
            }
        }

        return $successCount > 0 ? 0 : 1;
    }
}

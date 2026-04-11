<?php

namespace App\Console\Commands;

use App\Services\Webhooks\WebhookRetryService;
use Illuminate\Console\Command;

/**
 * Rejoue les webhooks en échec (nécessitant retry)
 * Utilise WebhookRetryService::retryFailedWebhooks avec handlers par provider
 */
class RetryWebhookFailuresCommand extends Command
{
    protected $signature = 'webhook:retry-failures {--limit=10 : Nombre max de webhooks à rejouer}';

    protected $description = 'Rejoue les webhooks en échec depuis la Dead Letter Queue (Stripe, Monetbil)';

    public function handle(WebhookRetryService $retryService): int
    {
        $limit = (int) $this->option('limit');

        $this->info("🔄 Recherche de webhooks à rejouer (limit: {$limit})...");

        $handler = function (array $payload) {
            $provider = $payload['provider'] ?? 'unknown';

            if ($provider === 'stripe') {
                $controller = app(\App\Http\Controllers\Webhooks\StripeWebhookController::class);
                $controller->processFromPayload($payload);
                return;
            }

            if ($provider === 'monetbil') {
                $this->processMonetbilPayload($payload);
                return;
            }

            \Illuminate\Support\Facades\Log::warning('[WEBHOOK] Unsupported provider for retry', [
                'provider' => $provider,
            ]);
            throw new \RuntimeException("Provider non supporté pour le retry: {$provider}");
        };

        $successCount = $retryService->retryFailedWebhooks($handler, $limit);

        if ($successCount > 0) {
            $this->info("✅ {$successCount} webhook(s) rejoué(s) avec succès.");
        } else {
            $this->info('✅ Aucun webhook à rejouer.');
        }

        return 0;
    }

    private function processMonetbilPayload(array $payload): void
    {
        $eventId = $payload['monetbil_callback_event_id'] ?? null;
        if ($eventId) {
            \App\Jobs\ProcessMonetbilCallbackEventJob::dispatch($eventId);
            return;
        }
        throw new \RuntimeException('Monetbil retry: monetbil_callback_event_id manquant dans le payload');
    }
}

<?php

namespace App\Console\Commands\Payments;

use App\Models\StripeWebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class StripeWebhookSmoke extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:stripe-webhook-smoke 
                            {--tail=0 : Number of log lines to display}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Smoke test for Stripe webhook configuration and recent events';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Stripe Webhook Smoke Test ===');
        $this->newLine();

        $checks = [];
        $allPassed = true;

        // 1. Check STRIPE_ENABLED
        $stripeEnabled = env('STRIPE_ENABLED', false);
        $checks[] = [
            'name' => 'STRIPE_ENABLED=true',
            'status' => $stripeEnabled === true || $stripeEnabled === 'true',
            'message' => $stripeEnabled ? 'OK' : 'FAIL - Set STRIPE_ENABLED=true in .env',
        ];
        if (!$checks[count($checks) - 1]['status']) {
            $allPassed = false;
        }

        // 2. Check STRIPE_WEBHOOK_SECRET
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET', '');
        $hasValidSecret = !empty($webhookSecret) && str_starts_with($webhookSecret, 'whsec_');
        $checks[] = [
            'name' => 'STRIPE_WEBHOOK_SECRET starts with whsec_',
            'status' => $hasValidSecret,
            'message' => $hasValidSecret ? 'OK' : 'FAIL - Set STRIPE_WEBHOOK_SECRET=whsec_... in .env',
        ];
        if (!$checks[count($checks) - 1]['status']) {
            $allPassed = false;
        }

        // 3. Check route exists
        $routeExists = Route::has('api.webhooks.stripe');
        $checks[] = [
            'name' => 'Route api/webhooks/stripe exists',
            'status' => $routeExists,
            'message' => $routeExists ? 'OK' : 'FAIL - Route not found. Check routes/api.php',
        ];
        if (!$checks[count($checks) - 1]['status']) {
            $allPassed = false;
        }

        // 4. Check database connection
        try {
            DB::connection()->getPdo();
            $dbOk = true;
            $dbMessage = 'OK';
        } catch (\Exception $e) {
            $dbOk = false;
            $dbMessage = 'FAIL - ' . $e->getMessage();
        }
        $checks[] = [
            'name' => 'Database connection',
            'status' => $dbOk,
            'message' => $dbMessage,
        ];
        if (!$checks[count($checks) - 1]['status']) {
            $allPassed = false;
        }

        // 5. Check table exists
        $tableExists = false;
        if ($dbOk) {
            try {
                $tableExists = DB::getSchemaBuilder()->hasTable('stripe_webhook_events');
            } catch (\Exception $e) {
                // Ignore
            }
        }
        $checks[] = [
            'name' => 'Table stripe_webhook_events exists',
            'status' => $tableExists,
            'message' => $tableExists ? 'OK' : 'FAIL - Run migrations: php artisan migrate',
        ];
        if (!$checks[count($checks) - 1]['status']) {
            $allPassed = false;
        }

        // Display results
        foreach ($checks as $check) {
            $icon = $check['status'] ? '✓' : '✗';
            $color = $check['status'] ? 'green' : 'red';
            $this->line("  {$icon} {$check['name']}: <fg={$color}>{$check['message']}</>");
        }

        $this->newLine();

        // Display recent events if table exists
        if ($tableExists) {
            try {
                $recentEvents = StripeWebhookEvent::latest()->take(5)->get();
                if ($recentEvents->count() > 0) {
                    $this->info('Recent webhook events:');
                    $this->table(
                        ['ID', 'Event ID', 'Type', 'Status', 'Created'],
                        $recentEvents->map(function ($event) {
                            return [
                                $event->id,
                                substr($event->event_id ?? 'N/A', 0, 20) . '...',
                                $event->event_type ?? 'N/A',
                                $event->status ?? 'N/A',
                                $event->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                            ];
                        })->toArray()
                    );
                } else {
                    $this->warn('No webhook events found in database.');
                }
            } catch (\Exception $e) {
                $this->warn('Could not fetch recent events: ' . $e->getMessage());
            }
        }

        // Display log tail if requested
        $tail = (int) $this->option('tail');
        if ($tail > 0) {
            $this->newLine();
            $this->info("Last {$tail} log lines (filtered):");
            $this->displayLogTail($tail);
        }

        $this->newLine();

        if ($allPassed) {
            $this->info('✓ All checks passed!');
            return 0;
        } else {
            $this->error('✗ Some checks failed. See instructions above.');
            $this->newLine();
            $this->line('Quick fixes:');
            $this->line('  1. Update .env with STRIPE_ENABLED=true and STRIPE_WEBHOOK_SECRET=whsec_...');
            $this->line('  2. Run: php artisan optimize:clear');
            $this->line('  3. Run: php artisan migrate (if table missing)');
            return 1;
        }
    }

    /**
     * Display filtered log tail
     */
    private function displayLogTail(int $lines): void
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            $this->warn("Log file not found: {$logPath}");
            return;
        }

        try {
            $allLines = file($logPath);
            if ($allLines === false) {
                $this->warn('Could not read log file');
                return;
            }

            // Filter lines containing webhook-related content
            $filtered = array_filter($allLines, function ($line) {
                return stripos($line, 'received_stripe_webhook') !== false
                    || stripos($line, 'StripeWebhookEvent') !== false
                    || stripos($line, 'stripe webhook') !== false;
            });

            // Get last N lines
            $filtered = array_slice($filtered, -$lines);

            if (empty($filtered)) {
                $this->warn('No matching log entries found.');
                return;
            }

            foreach ($filtered as $line) {
                $this->line(rtrim($line));
            }
        } catch (\Exception $e) {
            $this->warn('Error reading log file: ' . $e->getMessage());
        }
    }
}

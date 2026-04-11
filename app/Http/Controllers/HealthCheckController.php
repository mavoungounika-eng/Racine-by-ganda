<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Stripe\StripeClient;

class HealthCheckController extends Controller
{
    public function health(): JsonResponse
    {
        $checks = [];

        $checks['database'] = $this->checkDatabase();
        $checks['redis']    = $this->checkRedis();
        $checks['stripe']   = $this->checkStripe();
        $checks['queue']    = $this->checkQueue();

        $healthy = collect($checks)->every(fn($check) => in_array($check['status'], ['ok', 'skipped']));

        $alerts = collect($checks)
            ->filter(fn($c) => ($c['status'] ?? null) === 'error')
            ->map(fn($c, $s) => [
                'service' => $s,
                'message' => $c['message'] ?? 'Health check failed',
            ])
            ->values()
            ->all();

        return response()->json([
            'status'    => $healthy ? 'healthy' : 'degraded',
            'alerts'    => $alerts,
            'timestamp' => now()->toIso8601String(),
            'checks'    => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Database connection failed'];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::connection()->ping();
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Redis connection failed'];
        }
    }

    private function checkQueue(): array
    {
        try {
            $threshold = config('queue-protection.monitoring.thresholds.queue_size.critical', 1000);
            $size = Redis::llen('queues:webhooks');
            if ($size > $threshold) {
                return ['status' => 'error', 'message' => "Queue size critical: {$size} jobs pending"];
            }
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'skipped', 'message' => 'Queue check unavailable'];
        }
    }

    private function checkStripe(): array
    {
        if (!env('STRIPE_ENABLED', false)) {
            return ['status' => 'skipped', 'message' => 'Stripe disabled'];
        }

        $secretKey = config('services.stripe.secret');
        if (empty($secretKey) || str_contains((string)$secretKey, 'REPLACE')) {
            return ['status' => 'skipped', 'message' => 'Stripe not configured'];
        }

        try {
            $stripe = new StripeClient($secretKey);
            $stripe->balance->retrieve();
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Stripe API connection failed'];
        }
    }
}

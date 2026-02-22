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
        $checks['redis'] = $this->checkRedis();
        $checks['stripe'] = $this->checkStripe();

        $healthy = collect($checks)->every(fn($check) => in_array($check['status'], ['ok', 'skipped']));

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
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

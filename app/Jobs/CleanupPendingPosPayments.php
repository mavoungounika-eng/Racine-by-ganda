<?php

namespace App\Jobs;

use App\Models\PosPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupPendingPosPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [30, 60, 120];

    public int $threshold; // minutes

    public function __construct(int $threshold = 30)
    {
        $this->threshold = $threshold;
    }

    public function handle(): void
    {
        $cutoff = now()->subMinutes($this->threshold);

        $stale = PosPayment::whereIn('method', ['card', 'mobile_money'])
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->with('sale')
            ->get();

        foreach ($stale as $payment) {
            try {
                DB::transaction(function () use ($payment) {
                    $payment->cancel('timeout');
                    $payment->sale?->cancel(null, 'timeout');
                });
                Log::info('[POS Cleanup] Payment cancelled', [
                    'payment_id' => $payment->id,
                    'sale_id' => $payment->sale?->id,
                    'method' => $payment->method,
                    'age_minutes' => now()->diffInMinutes($payment->created_at),
                ]);
            } catch (\Throwable $e) {
                Log::error('[POS Cleanup] Failed to cancel payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[POS Cleanup] Done', ['cancelled' => $stale->count()]);
    }
}

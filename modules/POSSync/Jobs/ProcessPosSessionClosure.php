<?php

namespace Modules\POSSync\Jobs;

use App\Events\PosSessionClosed;
use App\Models\PosSession;
use App\Services\Pos\PosSessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPosSessionClosure implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $payload
    ) {}

    public function handle(PosSessionService $sessionService): void
    {
        $sessionId = $this->payload['session_id'] ?? null;

        if (!$sessionId) {
            Log::warning('ProcessPosSessionClosure skipped: missing session_id', [
                'payload' => $this->payload,
            ]);
            return;
        }

        $session = PosSession::with(['sales', 'cashMovements'])->find($sessionId);
        if (!$session) {
            Log::warning('ProcessPosSessionClosure skipped: session not found', [
                'session_id' => $sessionId,
            ]);
            return;
        }

        $eventAlreadyEmitted = false;

        if (!$session->isClosed()) {
            $closingCash = (float) ($this->payload['closing_cash'] ?? $this->payload['actual_cash'] ?? 0);
            $closedBy = (int) ($this->payload['closed_by'] ?? $this->payload['user_id'] ?? 0);
            $notes = $this->payload['notes'] ?? null;

            if ($closedBy <= 0) {
                Log::warning('ProcessPosSessionClosure skipped: session open and closed_by missing', [
                    'session_id' => $sessionId,
                    'payload' => $this->payload,
                ]);
                return;
            }

            $session = $sessionService->closeSession($session, $closingCash, $closedBy, $notes);
            $eventAlreadyEmitted = true;
        }

        if (!$eventAlreadyEmitted) {
            event(new PosSessionClosed($session));
        }

        $sales = $session->sales;
        $cashSales = $sales->where('payment_method', 'cash');
        $cardSales = $sales->where('payment_method', 'card');
        $mobileSales = $sales->where('payment_method', 'mobile_money');

        Log::info('ProcessPosSessionClosure processed', [
            'session_id' => $session->id,
            'status' => $session->status,
            'summary' => [
                'total_sales' => $sales->count(),
                'total_amount' => $sales->sum('total_amount'),
                'cash_total' => $cashSales->sum('total_amount'),
                'card_total' => $cardSales->sum('total_amount'),
                'mobile_total' => $mobileSales->sum('total_amount'),
                'expected_cash' => $session->expected_cash,
                'closing_cash' => $session->closing_cash,
                'cash_difference' => $session->cash_difference,
            ],
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPosSessionClosure failed', [
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
        ]);
    }
}

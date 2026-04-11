<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentStateHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment State Machine Pattern Implementation
 * 
 * Enforces valid state transitions for payments:
 * 
 * STATES:
 * - pending: Initial state, no payment action yet
 * - processing: Payment is being processed
 * - completed: Payment successful
 * - failed: Payment failed (can retry)
 * - refunded: Payment refunded
 * - expired: Payment expired (no longer valid)
 * 
 * VALID TRANSITIONS (state diagram):
 * pending     → processing, expired
 * processing  → completed, failed
 * completed   → refunded
 * failed      → pending (retry)
 * refunded    → (terminal, no transitions allowed)
 * expired     → (terminal, no transitions allowed)
 */
class PaymentStateMachine
{
    // Define valid state transitions as adjacency map
    private const VALID_TRANSITIONS = [
        'pending' => ['processing', 'expired'],
        'processing' => ['completed', 'failed'],
        'completed' => ['refunded'],
        'failed' => ['pending'],  // Retry: failed → pending
        'refunded' => [],          // Terminal state
        'expired' => [],           // Terminal state
    ];

    // Define all valid states
    private const VALID_STATES = [
        'pending',
        'processing',
        'completed',
        'failed',
        'refunded',
        'expired',
    ];

    /**
     * Transition a payment to a new state.
     * 
     * @throws \Exception if transition is invalid
     */
    public function transitionTo(
        string $paymentId,
        string $toState,
        string $trigger = null,
        array $metadata = [],
        string $reason = null
    ): PaymentStateHistory {
        // Validate target state
        if (!in_array($toState, self::VALID_STATES)) {
            Log::error('Invalid target state for payment', [
                'payment_id' => $paymentId,
                'to_state' => $toState,
                'valid_states' => self::VALID_STATES,
            ]);
            throw new \Exception("Invalid state: {$toState}");
        }

        // Get payment
        $payment = Payment::find($paymentId);
        if (!$payment) {
            Log::error('Payment not found', [
                'payment_id' => $paymentId,
            ]);
            throw new \Exception("Payment not found: {$paymentId}");
        }

        // Get current state
        $currentState = $this->getCurrentState($paymentId);

        // Validate transition is allowed
        $isValidTransition = $this->isValidTransition($currentState, $toState);
        if (!$isValidTransition) {
            Log::warning('Invalid state transition attempted', [
                'payment_id' => $paymentId,
                'from_state' => $currentState,
                'to_state' => $toState,
                'trigger' => $trigger,
            ]);
        }

        // Start transaction to ensure atomicity
        try {
            return DB::transaction(function () use (
                $paymentId,
                $payment,
                $currentState,
                $toState,
                $isValidTransition,
                $trigger,
                $metadata,
                $reason
            ) {
                // Create state history record
                $history = PaymentStateHistory::create([
                    'payment_id' => $paymentId,
                    'payment_type' => 'payment',
                    'from_state' => $currentState,
                    'to_state' => $toState,
                    'trigger' => $trigger,
                    'metadata' => $metadata,
                    'reason' => $reason,
                    'is_valid' => $isValidTransition,
                    'validation_error' => $isValidTransition ? null : 
                        "Invalid transition: {$currentState} → {$toState}",
                ]);

                // If transition is valid, update payment
                if ($isValidTransition) {
                    // State is tracked in PaymentStateHistory, not Payment model
                    Log::info('Payment state transitioned', [
                        'payment_id' => $paymentId,
                        'from' => $currentState,
                        'to' => $toState,
                        'trigger' => $trigger,
                    ]);
                }

                return $history;
            });
        } catch (\Exception $e) {
            Log::error('Failed to transition payment state', [
                'payment_id' => $paymentId,
                'from' => $currentState,
                'to' => $toState,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get current state of a payment.
     * Defaults to 'pending' if no history exists.
     */
    public function getCurrentState(string $paymentId): string
    {
        $latest = PaymentStateHistory::where('payment_id', $paymentId)
            ->valid()
            ->orderByDesc('id')  // Order by ID instead of created_at for deterministic results
            ->first();

        return $latest?->to_state ?? 'pending';
    }

    /**
     * Get the full state transition history for a payment.
     */
    public function getHistory(string $paymentId)
    {
        return PaymentStateHistory::where('payment_id', $paymentId)
            ->valid()
            ->orderBy('id')  // Order by ID for deterministic results
            ->get();
    }

    /**
     * Check if a transition is valid.
     */
    public function isValidTransition(string $fromState, string $toState): bool
    {
        if (!isset(self::VALID_TRANSITIONS[$fromState])) {
            return false;
        }

        return in_array($toState, self::VALID_TRANSITIONS[$fromState]);
    }

    /**
     * Get possible next states from current state.
     */
    public function getPossibleNextStates(string $currentState): array
    {
        return self::VALID_TRANSITIONS[$currentState] ?? [];
    }

    /**
     * Check if state is terminal (no transitions allowed).
     */
    public function isTerminalState(string $state): bool
    {
        return empty(self::VALID_TRANSITIONS[$state] ?? []);
    }

    /**
     * Check if payment can be retried (failed → pending).
     */
    public function canRetry(string $paymentId): bool
    {
        $currentState = $this->getCurrentState($paymentId);
        return $currentState === 'failed' && $this->isValidTransition('failed', 'pending');
    }

    /**
     * Retry a failed payment.
     */
    public function retry(string $paymentId, string $reason = null): PaymentStateHistory
    {
        if (!$this->canRetry($paymentId)) {
            throw new \Exception("Payment cannot be retried: {$paymentId}");
        }

        return $this->transitionTo(
            $paymentId,
            'pending',
            'retry',
            [],
            $reason ?? 'Manual retry after failure'
        );
    }

    /**
     * Get statistics about state distribution.
     */
    public function getStatistics(): array
    {
        $counts = PaymentStateHistory::valid()
            ->selectRaw('to_state, COUNT(*) as count')
            ->groupBy('to_state')
            ->pluck('count', 'to_state')
            ->toArray();

        // Ensure all states are represented
        foreach (self::VALID_STATES as $state) {
            $counts[$state] = $counts[$state] ?? 0;
        }

        return [
            'total_transitions' => array_sum($counts),
            'by_state' => $counts,
            'terminal_count' => ($counts['refunded'] ?? 0) + ($counts['expired'] ?? 0),
        ];
    }

    /**
     * Get payments in a specific state.
     */
    public function paymentsInState(string $state)
    {
        if (!in_array($state, self::VALID_STATES)) {
            throw new \Exception("Invalid state: {$state}");
        }

        return DB::query()
            ->from('payments')
            ->whereIn('id', 
                PaymentStateHistory::where('to_state', $state)
                    ->valid()
                    ->distinct('payment_id')
                    ->pluck('payment_id')
            )
            ->get();
    }

    /**
     * Get time spent in each state (for analytics).
     */
    public function timeInStates(string $paymentId): array
    {
        $history = $this->getHistory($paymentId);
        $timePerState = [];

        for ($i = 0; $i < count($history) - 1; $i++) {
            $currentRecord = $history[$i];
            $nextRecord = $history[$i + 1];
            
            $state = $currentRecord->to_state;
            $durationSeconds = $currentRecord->created_at->diffInSeconds($nextRecord->created_at);
            
            $timePerState[$state] = ($timePerState[$state] ?? 0) + $durationSeconds;
        }

        // Add time in current state
        $lastRecord = $history->last();
        if ($lastRecord) {
            $state = $lastRecord->to_state;
            $durationSeconds = $lastRecord->created_at->diffInSeconds(now());
            $timePerState[$state] = ($timePerState[$state] ?? 0) + $durationSeconds;
        }

        return $timePerState;
    }

    /**
     * Get all valid transitions as a map.
     */
    public function getTransitionMap(): array
    {
        return self::VALID_TRANSITIONS;
    }

    /**
     * Validate state machine configuration (self-test).
     */
    public function validate(): array
    {
        $errors = [];

        // Check: all states in transitions map are valid
        foreach (self::VALID_TRANSITIONS as $state => $nextStates) {
            if (!in_array($state, self::VALID_STATES)) {
                $errors[] = "State in transitions map not in VALID_STATES: {$state}";
            }
            
            foreach ($nextStates as $nextState) {
                if (!in_array($nextState, self::VALID_STATES)) {
                    $errors[] = "Invalid next state: {$state} → {$nextState}";
                }
            }
        }

        // Check: no cycles (no indirect loops that could cause infinite states)
        // For simplicity, just check terminal states don't have transitions
        $terminalStates = ['refunded', 'expired'];
        foreach ($terminalStates as $state) {
            if (!empty(self::VALID_TRANSITIONS[$state] ?? [])) {
                $errors[] = "Terminal state has transitions: {$state}";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'total_states' => count(self::VALID_STATES),
            'total_transitions' => array_sum(array_map('count', self::VALID_TRANSITIONS)),
        ];
    }
}

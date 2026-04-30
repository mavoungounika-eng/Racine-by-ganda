<?php

namespace Tests\Feature\StateMachine;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentStateHistory;
use App\Models\User;
use App\Services\Payments\PaymentStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private PaymentStateMachine $stateMachine;
    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = app(PaymentStateMachine::class);
        
        // Create test user and order
        $user = User::factory()->create();
        $order = Order::factory()
            ->for($user)
            ->create([
                'total_amount' => 10000,
            ]);
        
        // Create test payment
        $this->payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'amount' => 10000,
            'currency' => 'XAF',
            'status' => 'pending',
            'metadata' => [],
        ]);
    }

    // ============================================================================
    // STATE TRANSITIONS: VALID PATHS
    // ============================================================================
    #[Test]
    public function transitions_from_pending_to_processing(): void
    {
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'processing',
            'webhook_received'
        );

        $this->assertTrue($history->is_valid);
        $this->assertEquals('pending', $history->from_state);
        $this->assertEquals('processing', $history->to_state);
        $this->assertEquals('processing', $this->stateMachine->getCurrentState($this->payment->id));
    }
    #[Test]
    public function transitions_from_pending_to_expired(): void
    {
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'expired',
            'timeout'
        );

        $this->assertTrue($history->is_valid);
        $this->assertEquals('expired', $this->stateMachine->getCurrentState($this->payment->id));
        $this->assertTrue($this->stateMachine->isTerminalState('expired'));
    }
    #[Test]
    public function transitions_from_processing_to_completed(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'completed',
            'webhook_success'
        );

        $this->assertTrue($history->is_valid);
        $this->assertEquals('completed', $this->stateMachine->getCurrentState($this->payment->id));
    }
    #[Test]
    public function transitions_from_processing_to_failed(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'failed',
            'webhook_failure',
            ['error_code' => 'DECLINED']
        );

        $this->assertTrue($history->is_valid);
        $this->assertEquals('failed', $this->stateMachine->getCurrentState($this->payment->id));
    }
    #[Test]
    public function transitions_from_completed_to_refunded(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $this->stateMachine->transitionTo($this->payment->id, 'completed', 'webhook_success');
        
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'refunded',
            'user_request'
        );

        $this->assertTrue($history->is_valid);
        $this->assertEquals('refunded', $this->stateMachine->getCurrentState($this->payment->id));
        $this->assertTrue($this->stateMachine->isTerminalState('refunded'));
    }

    // ============================================================================
    // STATE TRANSITIONS: INVALID PATHS
    // ============================================================================
    #[Test]
    public function rejects_invalid_transition_from_pending_to_completed(): void
    {
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'completed',
            'invalid_transition'
        );

        $this->assertFalse($history->is_valid);
        $this->assertNotNull($history->validation_error);
        // State should NOT be updated
        $this->assertEquals('pending', $this->stateMachine->getCurrentState($this->payment->id));
    }
    #[Test]
    public function rejects_invalid_transition_from_completed_to_processing(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $this->stateMachine->transitionTo($this->payment->id, 'completed', 'success');
        
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'processing',
            'invalid_backtrack'
        );

        $this->assertFalse($history->is_valid);
        $this->assertEquals('completed', $this->stateMachine->getCurrentState($this->payment->id));
    }
    #[Test]
    public function rejects_transition_from_refunded_state(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $this->stateMachine->transitionTo($this->payment->id, 'completed', 'success');
        $this->stateMachine->transitionTo($this->payment->id, 'refunded', 'refund_requested');
        
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'pending',
            'retry_after_refund'
        );

        $this->assertFalse($history->is_valid);
        $this->assertEquals('refunded', $this->stateMachine->getCurrentState($this->payment->id));
    }
    #[Test]
    public function rejects_transition_from_expired_state(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'expired', 'timeout');
        
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'processing',
            'retry_after_expiry'
        );

        $this->assertFalse($history->is_valid);
        $this->assertEquals('expired', $this->stateMachine->getCurrentState($this->payment->id));
    }

    // ============================================================================
    // RETRY LOGIC
    // ============================================================================
    #[Test]
    public function allows_retry_from_failed_state(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $this->stateMachine->transitionTo($this->payment->id, 'failed', 'webhook_failure');
        
        $this->assertTrue($this->stateMachine->canRetry($this->payment->id));
        
        $history = $this->stateMachine->retry($this->payment->id);
        
        $this->assertTrue($history->is_valid);
        $this->assertEquals('pending', $this->stateMachine->getCurrentState($this->payment->id));
        $this->assertEquals('retry', $history->trigger);
    }
    #[Test]
    public function prevents_retry_from_completed_state(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $this->stateMachine->transitionTo($this->payment->id, 'completed', 'success');
        
        $this->assertFalse($this->stateMachine->canRetry($this->payment->id));
        
        $this->expectException(\Exception::class);
        $this->stateMachine->retry($this->payment->id);
    }
    #[Test]
    public function prevents_retry_from_refunded_state(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $this->stateMachine->transitionTo($this->payment->id, 'completed', 'success');
        $this->stateMachine->transitionTo($this->payment->id, 'refunded', 'user_request');
        
        $this->assertFalse($this->stateMachine->canRetry($this->payment->id));
    }

    // ============================================================================
    // STATE TRACKING & HISTORY
    // ============================================================================
    #[Test]
    public function tracks_complete_state_history(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $this->stateMachine->transitionTo($this->payment->id, 'completed', 'webhook_success');
        $this->stateMachine->transitionTo($this->payment->id, 'refunded', 'user_request');
        
        $history = $this->stateMachine->getHistory($this->payment->id);
        
        $this->assertCount(3, $history);
        $this->assertEquals('pending', $history[0]->from_state);
        $this->assertEquals('processing', $history[0]->to_state);
        $this->assertEquals('completed', $history[1]->to_state);
        $this->assertEquals('refunded', $history[2]->to_state);
    }
    #[Test]
    public function returns_current_state(): void
    {
        $this->assertEquals('pending', $this->stateMachine->getCurrentState($this->payment->id));
        
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $this->assertEquals('processing', $this->stateMachine->getCurrentState($this->payment->id));
        
        $this->stateMachine->transitionTo($this->payment->id, 'completed', 'success');
        $this->assertEquals('completed', $this->stateMachine->getCurrentState($this->payment->id));
    }
    #[Test]
    public function ignores_invalid_transitions_in_history(): void
    {
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $this->stateMachine->transitionTo($this->payment->id, 'completed', 'success');
        
        // Try invalid transition
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'invalid');
        
        $history = $this->stateMachine->getHistory($this->payment->id);
        
        // Should only have 2 valid transitions
        $this->assertCount(2, $history);
    }

    // ============================================================================
    // TERMINAL STATES
    // ============================================================================
    #[Test]
    public function identifies_terminal_states(): void
    {
        $this->assertTrue($this->stateMachine->isTerminalState('refunded'));
        $this->assertTrue($this->stateMachine->isTerminalState('expired'));
        
        $this->assertFalse($this->stateMachine->isTerminalState('pending'));
        $this->assertFalse($this->stateMachine->isTerminalState('processing'));
        $this->assertFalse($this->stateMachine->isTerminalState('completed'));
        $this->assertFalse($this->stateMachine->isTerminalState('failed'));
    }

    // ============================================================================
    // NEXT STATES
    // ============================================================================
    #[Test]
    public function returns_possible_next_states(): void
    {
        $nextStates = $this->stateMachine->getPossibleNextStates('pending');
        $this->assertContains('processing', $nextStates);
        $this->assertContains('expired', $nextStates);
        $this->assertCount(2, $nextStates);
    }
    #[Test]
    public function returns_no_next_states_for_terminal_states(): void
    {
        $nextStates = $this->stateMachine->getPossibleNextStates('refunded');
        $this->assertEmpty($nextStates);
        
        $nextStates = $this->stateMachine->getPossibleNextStates('expired');
        $this->assertEmpty($nextStates);
    }

    // ============================================================================
    // STATISTICS
    // ============================================================================
    #[Test]
    public function returns_state_statistics(): void
    {
        // Create multiple transitions across different payments
        for ($i = 0; $i < 3; $i++) {
            $order = Order::factory()
                ->for(User::first())
                ->create(['total_amount' => 1000]);
            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => 'stripe',
                'amount' => 1000,
                'currency' => 'XAF',
                'status' => 'pending',
                'metadata' => [],
            ]);
            $this->stateMachine->transitionTo($payment->id, 'processing', 'initiated');
        }
        
        $this->stateMachine->transitionTo($this->payment->id, 'processing', 'initiated');
        $this->stateMachine->transitionTo($this->payment->id, 'completed', 'success');
        
        $stats = $this->stateMachine->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_transitions', $stats);
        $this->assertArrayHasKey('by_state', $stats);
        $this->assertGreaterThan(0, $stats['total_transitions']);
    }

    // ============================================================================
    // STATE MACHINE VALIDATION
    // ============================================================================
    #[Test]
    public function validates_state_machine_configuration(): void
    {
        $validation = $this->stateMachine->validate();
        
        $this->assertArrayHasKey('is_valid', $validation);
        $this->assertArrayHasKey('errors', $validation);
        $this->assertArrayHasKey('total_states', $validation);
        $this->assertArrayHasKey('total_transitions', $validation);
        
        $this->assertTrue($validation['is_valid']);
        $this->assertEmpty($validation['errors']);
        $this->assertEquals(6, $validation['total_states']);
    }

    // ============================================================================
    // METADATA & CONTEXT
    // ============================================================================
    #[Test]
    public function stores_metadata_with_transition(): void
    {
        $metadata = ['user_ip' => '192.168.1.1', 'device' => 'mobile'];
        
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'processing',
            'webhook_received',
            $metadata
        );

        $this->assertEquals($metadata, $history->metadata);
    }
    #[Test]
    public function stores_reason_for_transition(): void
    {
        $reason = 'User requested payment processing';
        
        $history = $this->stateMachine->transitionTo(
            $this->payment->id,
            'processing',
            'user_action',
            [],
            $reason
        );

        $this->assertEquals($reason, $history->reason);
    }

    // ============================================================================
    // ERROR HANDLING
    // ============================================================================
    #[Test]
    public function throws_exception_for_invalid_state(): void
    {
        $this->expectException(\Exception::class);
        $this->stateMachine->transitionTo($this->payment->id, 'invalid_state');
    }
    #[Test]
    public function throws_exception_for_nonexistent_payment(): void
    {
        $this->expectException(\Exception::class);
        $this->stateMachine->transitionTo('nonexistent_payment', 'processing');
    }
}

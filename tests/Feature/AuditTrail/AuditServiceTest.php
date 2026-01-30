<?php

namespace Tests\Feature\AuditTrail;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $this->auditService = app(AuditService::class);
    }

    /**
     * Test: Log a generic action
     */
    public function test_can_log_generic_action(): void
    {
        $user = User::factory()->create();
        
        $log = $this->auditService->log(
            'test_action',
            'Order',
            123,
            $user,
            ['data' => 'test']
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'test_action',
            'entity_type' => 'Order',
            'entity_id' => 123,
            'user_id' => $user->id,
            'metadata' => json_encode(['data' => 'test']),
        ]);

        $this->assertEquals('test_action', $log->action);
        $this->assertEquals('Order', $log->entity_type);
    }

    /**
     * Test: Log action without authenticated user
     */
    public function test_can_log_system_action(): void
    {
        $log = $this->auditService->log(
            'system_action',
            'Product',
            456
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'system_action',
            'entity_type' => 'Product',
            'entity_id' => 456,
            'user_id' => null,
        ]);
    }

    /**
     * Test: Log order refund
     */
    public function test_can_log_refund(): void
    {
        $user = User::factory()->create();
        
        $log = $this->auditService->logRefund(
            orderId: 789,
            amount: 99.99,
            reason: 'Customer request',
            user: $user
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'refund_created',
            'entity_type' => 'Order',
            'entity_id' => 789,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(99.99, $log->metadata['amount']);
        $this->assertEquals('Customer request', $log->metadata['reason']);
    }

    /**
     * Test: Log stock adjustment
     */
    public function test_can_log_stock_adjustment(): void
    {
        $user = User::factory()->create();
        
        $log = $this->auditService->logStockAdjustment(
            productId: 555,
            quantityChange: -10,
            reason: 'Loss discovered in inventory',
            user: $user
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'stock_adjusted',
            'entity_type' => 'Product',
            'entity_id' => 555,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(-10, $log->metadata['quantity_change']);
        $this->assertStringContainsString('Loss', $log->metadata['reason']);
    }

    /**
     * Test: Log payment method change
     */
    public function test_can_log_payment_method_change(): void
    {
        $user = User::factory()->create();
        
        $log = $this->auditService->logPaymentMethodChange(
            orderId: 999,
            oldMethod: 'credit_card',
            newMethod: 'bank_transfer',
            user: $user
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment_method_changed',
            'entity_type' => 'Order',
            'entity_id' => 999,
        ]);

        $this->assertEquals('credit_card', $log->metadata['old_method']);
        $this->assertEquals('bank_transfer', $log->metadata['new_method']);
    }

    /**
     * Test: Log webhook retry
     */
    public function test_can_log_webhook_retry(): void
    {
        $user = User::factory()->create();
        
        $log = $this->auditService->logWebhookRetry(
            webhookFailureId: 111,
            provider: 'stripe',
            eventType: 'payment_intent.succeeded',
            user: $user
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'webhook_retried',
            'entity_type' => 'WebhookFailure',
            'entity_id' => 111,
        ]);

        $this->assertEquals('stripe', $log->metadata['provider']);
        $this->assertEquals('payment_intent.succeeded', $log->metadata['event_type']);
    }

    /**
     * Test: Query logs by action
     */
    public function test_can_query_by_action(): void
    {
        $user = User::factory()->create();
        
        $this->auditService->logRefund(1, 50, null, $user);
        $this->auditService->logStockAdjustment(2, -5, null, $user);
        $this->auditService->logRefund(3, 100, null, $user);

        $refunds = AuditLog::action('refund_created')->get();
        
        $this->assertEquals(2, $refunds->count());
        $this->assertTrue($refunds->every(fn($log) => $log->action === 'refund_created'));
    }

    /**
     * Test: Query logs by entity
     */
    public function test_can_query_by_entity(): void
    {
        $user = User::factory()->create();
        
        $this->auditService->log('action1', 'Order', 100, $user);
        $this->auditService->log('action2', 'Order', 101, $user);
        $this->auditService->log('action3', 'Product', 50, $user);

        $orders = AuditLog::entity('Order')->get();
        
        $this->assertEquals(2, $orders->count());
    }

    /**
     * Test: Query logs for specific entity
     */
    public function test_can_query_for_entity(): void
    {
        $user = User::factory()->create();
        
        $this->auditService->log('action1', 'Order', 100, $user);
        $this->auditService->log('action2', 'Order', 100, $user);
        $this->auditService->log('action3', 'Order', 101, $user);

        $logs = AuditLog::for('Order', 100)->get();
        
        $this->assertEquals(2, $logs->count());
    }

    /**
     * Test: Query logs by user
     */
    public function test_can_query_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $this->auditService->log('action1', 'Order', 1, $user1);
        $this->auditService->log('action2', 'Order', 2, $user2);
        $this->auditService->log('action3', 'Order', 3, $user1);

        $user1Logs = AuditLog::byUser($user1->id)->get();
        
        $this->assertEquals(2, $user1Logs->count());
    }

    /**
     * Test: Query recent logs
     */
    public function test_can_query_recent_logs(): void
    {
        $user = User::factory()->create();
        
        $this->auditService->log('action1', 'Order', 1, $user);
        
        // Backdate a log
        $oldLog = AuditLog::create([
            'action' => 'action2',
            'entity_type' => 'Order',
            'entity_id' => 2,
            'user_id' => $user->id,
            'created_at' => now()->subDays(7),
        ]);

        $recent = AuditLog::recent(24)->get();
        
        $this->assertEquals(1, $recent->count());
        $this->assertNotContains($oldLog->id, $recent->pluck('id'));
    }

    /**
     * Test: IP address is captured
     */
    public function test_ip_address_is_captured(): void
    {
        $log = $this->auditService->log('test', 'Order', 1);
        
        $this->assertNotNull($log->ip_address);
        $this->assertNotEmpty($log->ip_address);
    }

    /**
     * Test: User-Agent is captured
     */
    public function test_user_agent_is_captured(): void
    {
        $log = $this->auditService->log('test', 'Order', 1);
        
        $this->assertNotNull($log->user_agent);
        $this->assertNotEmpty($log->user_agent);
    }

    /**
     * Test: Audit log has relationship to user
     */
    public function test_audit_log_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $log = $this->auditService->log('test', 'Order', 1, $user);
        
        $this->assertTrue($log->user()->exists());
        $this->assertEquals($user->id, $log->user->id);
    }
}

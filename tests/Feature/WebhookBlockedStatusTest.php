<?php

namespace Tests\Feature;

use App\Models\MonetbilCallbackEvent;
use App\Models\PaymentAuditLog;
use App\Models\Role;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use App\Services\Payments\WebhookRequeueGuard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookBlockedStatusTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureRolesExist();
        
        $adminRole = Role::where('slug', 'admin')->first();
        $this->user = User::firstOrCreate(
            ['email' => 'admin_blocked@test.com'],
            [
                'name' => 'Admin Blocked Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'role_id' => $adminRole?->id ?? 2,
                'is_admin' => true,
            ]
        );
    }

    private function ensureRolesExist(): void
    {
        Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
    }

    /**
     * Test qu'un event à limite atteinte apparaît "blocked"
     */
    public function test_event_at_limit_appears_blocked(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_blocked_test',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'requeue_count' => 5,
            'last_requeue_at' => now()->subMinutes(30), // Cooldown actif
            'payload_hash' => hash('sha256', 'test'),
        ]);

        // Tenter de requeue (devrait marquer comme blocked)
        // Note: markStripeAsBlockedIfNeeded nécessite que canRequeue retourne false
        // et que requeue_count >= 5 avec cooldown actif
        $blocked = WebhookRequeueGuard::markStripeAsBlockedIfNeeded($event);
        $event->refresh();

        $this->assertTrue($blocked, 'Event should be marked as blocked');
        $this->assertEquals('blocked', $event->status);
        $this->assertTrue($event->isBlocked());
    }

    /**
     * Test que l'action reset nécessite RBAC + reason
     */
    public function test_reset_requires_rbac_and_reason(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_reset_test',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'blocked',
            'requeue_count' => 5,
            'last_requeue_at' => now()->subMinutes(30),
            'payload_hash' => hash('sha256', 'test'),
        ]);

        // Sans permission
        $clientRole = Role::where('slug', 'client')->first();
        $client = User::firstOrCreate(
            ['email' => 'client_reset@test.com'],
            [
                'name' => 'Client Reset Test',
                'password' => bcrypt('password'),
                'role' => 'client',
                'role_id' => $clientRole?->id ?? 5,
                'is_admin' => false,
            ]
        );

        $this->actingAs($client)
            ->post(route('admin.payments.webhooks.stuck.resetWindow'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'reason' => 'Test reset',
            ])
            ->assertStatus(403);

        // Sans reason
        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.resetWindow'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'reason' => '', // Vide
            ])
            ->assertSessionHasErrors('reason');
    }

    /**
     * Test que reset réactive le bouton requeue
     */
    public function test_reset_reactivates_requeue(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_reset_reactivate',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'blocked',
            'requeue_count' => 5,
            'last_requeue_at' => now()->subMinutes(30),
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.resetWindow'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'reason' => 'Test reset reactivate',
            ])
            ->assertRedirect();

        $event->refresh();
        $this->assertEquals('received', $event->status);
        $this->assertEquals(0, $event->requeue_count);
        $this->assertNull($event->last_requeue_at);

        // Vérifier audit log
        $auditLog = PaymentAuditLog::where('target_type', StripeWebhookEvent::class)
            ->where('target_id', $event->id)
            ->where('action', 'reset_requeue_window')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('Test reset reactivate', $auditLog->reason);
        $this->assertEquals($this->user->id, $auditLog->user_id);
    }
}





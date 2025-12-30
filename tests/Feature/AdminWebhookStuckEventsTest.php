<?php

namespace Tests\Feature;

use App\Jobs\ProcessMonetbilCallbackEventJob;
use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\MonetbilCallbackEvent;
use App\Models\PaymentAuditLog;
use App\Models\Role;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AdminWebhookStuckEventsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // S'assurer que les rôles existent
        $this->ensureRolesExist();
        
        // Créer un utilisateur admin (autorisé pour payments.view et payments.reprocess)
        $adminRole = Role::where('slug', 'admin')->first();
        $this->user = User::firstOrCreate(
            ['email' => 'admin_stuck@test.com'],
            [
                'name' => 'Admin Stuck Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'role_id' => $adminRole?->id ?? 2,
                'is_admin' => true,
            ]
        );
    }

    /**
     * S'assurer que les rôles existent
     */
    private function ensureRolesExist(): void
    {
        Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
    }

    /**
     * Test que l'accès à la page stuck nécessite payments.view
     */
    public function test_unauthorized_user_cannot_access_stuck_page(): void
    {
        // Créer un utilisateur client (non autorisé)
        $clientRole = Role::where('slug', 'client')->first();
        $unauthorizedUser = User::firstOrCreate(
            ['email' => 'client_stuck@test.com'],
            [
                'name' => 'Client Stuck Test',
                'password' => bcrypt('password'),
                'role' => 'client',
                'role_id' => $clientRole?->id ?? 5,
                'is_admin' => false,
            ]
        );

        $this->actingAs($unauthorizedUser)
            ->get(route('admin.payments.webhooks.stuck.index'))
            ->assertStatus(403);
    }

    /**
     * Test que l'utilisateur avec payments.view peut voir la page
     */
    public function test_authorized_user_can_view_stuck_page(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.payments.webhooks.stuck.index'))
            ->assertStatus(200)
            ->assertSee('Stuck Webhooks');
    }

    /**
     * Test requeueOne avec reason obligatoire
     */
    public function test_requeue_one_requires_reason(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_requeue',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.requeueOne'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'minutes' => 10,
                // reason manquant
            ])
            ->assertSessionHasErrors('reason');
    }

    /**
     * Test requeueOne avec reason valide dispatche le job
     */
    public function test_requeue_one_dispatches_job_with_valid_reason(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_requeue',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.requeueOne'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'minutes' => 10,
                'reason' => 'Test requeue pour vérifier le fonctionnement',
            ])
            ->assertRedirect();

        // Vérifier que le job a été dispatché
        Bus::assertDispatched(ProcessStripeWebhookEventJob::class, 1);

        // Vérifier que dispatched_at est maintenant set
        $event->refresh();
        $this->assertNotNull($event->dispatched_at);
    }

    /**
     * Test que l'audit log est créé lors du requeue
     */
    public function test_requeue_one_creates_audit_log(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_audit',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $reason = 'Test audit log creation';

        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.requeueOne'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'minutes' => 10,
                'reason' => $reason,
            ]);

        // Vérifier l'audit log
        $auditLog = PaymentAuditLog::where('target_type', StripeWebhookEvent::class)
            ->where('target_id', $event->id)
            ->where('action', 'reprocess')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($reason, $auditLog->reason);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('single', $auditLog->diff['mode']);
    }

    /**
     * Test que requeueOne respecte l'atomic claim (pas de redispatch si déjà dispatché)
     */
    public function test_requeue_one_respects_atomic_claim(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        // Créer un event avec dispatched_at déjà set (récent)
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_atomic',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => now()->subMinutes(1), // Récent, ne doit pas être requeued
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.requeueOne'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'minutes' => 10,
                'reason' => 'Test atomic claim',
            ])
            ->assertRedirect();

        // Vérifier que le job n'a PAS été dispatché
        Bus::assertNothingDispatched();
    }

    /**
     * Test que requeueOne respecte le garde-fou anti-boucle (max 5/heure)
     */
    public function test_requeue_one_respects_anti_loop_guard(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        // Créer un event avec requeue_count = 5 et last_requeue_at récent (< 1 heure)
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_loop',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'requeue_count' => 5,
            'last_requeue_at' => now()->subMinutes(30), // Récent (< 1 heure)
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.requeueOne'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'minutes' => 10,
                'reason' => 'Test anti-loop guard',
            ])
            ->assertRedirect();

        // Vérifier que le job n'a PAS été dispatché (limite atteinte)
        Bus::assertNothingDispatched();
        
        // Vérifier que requeue_count n'a pas été incrémenté
        $event->refresh();
        $this->assertEquals(5, $event->requeue_count);
    }

    /**
     * Test que requeueOne fonctionne si last_requeue_at est > 1 heure (reset du cooldown)
     */
    public function test_requeue_one_allows_after_cooldown(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        // Créer un event avec requeue_count = 5 mais last_requeue_at > 1 heure
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_cooldown',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'requeue_count' => 5,
            'last_requeue_at' => now()->subHours(2), // > 1 heure, cooldown reset
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.requeueOne'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'minutes' => 10,
                'reason' => 'Test cooldown reset',
            ])
            ->assertRedirect();

        // Vérifier que le job a été dispatché (cooldown reset)
        Bus::assertDispatched(ProcessStripeWebhookEventJob::class, 1);
        
        // Vérifier que requeue_count a été incrémenté
        $event->refresh();
        $this->assertEquals(6, $event->requeue_count);
        $this->assertNotNull($event->last_requeue_at);
    }

    /**
     * Test requeueOne pour Monetbil
     */
    public function test_requeue_one_works_for_monetbil(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $event = MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'test_monetbil_requeue'),
            'payment_ref' => 'PAY_TEST',
            'transaction_id' => 'TXN_TEST',
            'status' => 'received',
            'dispatched_at' => null,
            'payload' => ['test' => 'data'],
            'received_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.requeueOne'), [
                'provider' => 'monetbil',
                'id' => $event->id,
                'minutes' => 10,
                'reason' => 'Test requeue Monetbil',
            ])
            ->assertRedirect();

        Bus::assertDispatched(ProcessMonetbilCallbackEventJob::class, 1);

        $event->refresh();
        $this->assertNotNull($event->dispatched_at);
    }

    /**
     * Test bulk requeue avec garde-fou (au moins 1 event bloqué, 1 autorisé)
     */
    public function test_bulk_requeue_respects_guard(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        // Event autorisé
        $event1 = StripeWebhookEvent::create([
            'event_id' => 'evt_test_1',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'requeue_count' => 2,
            'payload_hash' => hash('sha256', 'test1'),
        ]);

        // Event bloqué (limite atteinte)
        $event2 = StripeWebhookEvent::create([
            'event_id' => 'evt_test_2',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'requeue_count' => 5,
            'last_requeue_at' => now()->subMinutes(30), // Cooldown actif
            'payload_hash' => hash('sha256', 'test2'),
        ]);

        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.requeue'), [
                'provider' => 'stripe',
                'ids' => json_encode([['provider' => 'stripe', 'id' => $event1->id], ['provider' => 'stripe', 'id' => $event2->id]]),
                'minutes' => 10,
                'reason' => 'Test bulk requeue with guard',
            ])
            ->assertRedirect();

        // Seul event1 doit être dispatché
        Bus::assertDispatched(ProcessStripeWebhookEventJob::class, 1);
    }

    /**
     * Test requeue via commande artisan respecte le garde-fou
     */
    public function test_command_requeue_respects_guard(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        // Event autorisé
        $event1 = StripeWebhookEvent::create([
            'event_id' => 'evt_cmd_1',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'requeue_count' => 2,
            'payload_hash' => hash('sha256', 'test1'),
        ]);

        // Event bloqué
        $event2 = StripeWebhookEvent::create([
            'event_id' => 'evt_cmd_2',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'requeue_count' => 5,
            'last_requeue_at' => now()->subMinutes(30),
            'payload_hash' => hash('sha256', 'test2'),
        ]);

        $this->artisan('payments:requeue-stuck-webhooks', ['--minutes' => 10, '--provider' => 'stripe'])
            ->assertSuccessful();

        // Seul event1 doit être dispatché
        Bus::assertDispatched(ProcessStripeWebhookEventJob::class, 1);
    }

    /**
     * Test concurrence : double requeue sur le même event → un seul doit "claimer"
     */
    public function test_concurrency_double_requeue_same_event_only_one_claims(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_concurrent',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'requeue_count' => 0,
            'payload_hash' => hash('sha256', 'test'),
        ]);

        // Simuler 2 requêtes simultanées (en réalité séquentielles dans le test)
        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.requeueOne'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'minutes' => 10,
                'reason' => 'Test concurrent 1',
            ]);

        // Vérifier que dispatched_at est maintenant set
        $event->refresh();
        $this->assertNotNull($event->dispatched_at);

        // Deuxième requête doit être ignorée (atomic claim)
        $this->actingAs($this->user)
            ->post(route('admin.payments.webhooks.stuck.requeueOne'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'minutes' => 10,
                'reason' => 'Test concurrent 2',
            ]);

        // Un seul job doit être dispatché
        Bus::assertDispatched(ProcessStripeWebhookEventJob::class, 1);
    }

    /**
     * Test que requeueOne nécessite payments.reprocess
     */
    public function test_requeue_one_requires_reprocess_permission(): void
    {
        // Créer un utilisateur client (non autorisé pour payments.reprocess)
        $clientRole = Role::where('slug', 'client')->first();
        $client = User::firstOrCreate(
            ['email' => 'client_reprocess@test.com'],
            [
                'name' => 'Client Reprocess Test',
                'password' => bcrypt('password'),
                'role' => 'client',
                'role_id' => $clientRole?->id ?? 5,
                'is_admin' => false,
            ]
        );

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->actingAs($client)
            ->post(route('admin.payments.webhooks.stuck.requeueOne'), [
                'provider' => 'stripe',
                'id' => $event->id,
                'minutes' => 10,
                'reason' => 'Test',
            ])
            ->assertStatus(403);
    }
}





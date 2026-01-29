<?php

namespace Tests\Feature;

use App\Jobs\ProcessMonetbilCallbackEventJob;
use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebhookDispatchAtomicityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que l'atomic claim Stripe empêche le double-dispatch
     */
    public function test_stripe_atomic_claim_prevents_double_dispatch(): void
    {
        Bus::fake();

        // Créer un événement avec status='received' et dispatched_at=null
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_atomic_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'payload_hash' => hash('sha256', 'test'),
        ]);

        // Simuler 2 appels concurrents en exécutant le même code atomique
        $dispatched1 = false;
        $dispatched2 = false;

        // Appel 1 : Atomic claim
        $rowsAffected1 = DB::table('stripe_webhook_events')
            ->where('id', $event->id)
            ->whereNull('dispatched_at')
            ->update([
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);

        if ($rowsAffected1 === 1) {
            ProcessStripeWebhookEventJob::dispatch($event->id);
            $dispatched1 = true;
        }

        // Appel 2 : Atomic claim (devrait échouer car dispatched_at n'est plus null)
        $rowsAffected2 = DB::table('stripe_webhook_events')
            ->where('id', $event->id)
            ->whereNull('dispatched_at')
            ->update([
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);

        if ($rowsAffected2 === 1) {
            ProcessStripeWebhookEventJob::dispatch($event->id);
            $dispatched2 = true;
        }

        // Vérifications
        $this->assertTrue($dispatched1, 'Le premier appel doit dispatcher');
        $this->assertFalse($dispatched2, 'Le deuxième appel ne doit pas dispatcher (atomic claim échoué)');
        $this->assertEquals(0, $rowsAffected2, 'Le deuxième UPDATE atomique doit affecter 0 lignes');

        // Vérifier que le job a été dispatché exactement 1 fois
        Bus::assertDispatched(ProcessStripeWebhookEventJob::class, 1);

        // Vérifier en DB : dispatched_at non null
        $event->refresh();
        $this->assertNotNull($event->dispatched_at);
    }

    /**
     * Test que l'atomic claim Monetbil empêche le double-dispatch
     */
    public function test_monetbil_atomic_claim_prevents_double_dispatch(): void
    {
        Bus::fake();

        // Créer un événement avec status='received' et dispatched_at=null
        $event = MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'test_atomic_monetbil'),
            'payment_ref' => 'PAY_TEST_ATOMIC',
            'transaction_id' => 'TXN_ATOMIC',
            'status' => 'received',
            'dispatched_at' => null,
            'payload' => ['test' => 'data'],
            'received_at' => now(),
        ]);

        // Simuler 2 appels concurrents
        $dispatched1 = false;
        $dispatched2 = false;

        // Appel 1 : Atomic claim
        $rowsAffected1 = DB::table('monetbil_callback_events')
            ->where('id', $event->id)
            ->whereNull('dispatched_at')
            ->update([
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);

        if ($rowsAffected1 === 1) {
            ProcessMonetbilCallbackEventJob::dispatch($event->id);
            $dispatched1 = true;
        }

        // Appel 2 : Atomic claim (devrait échouer)
        $rowsAffected2 = DB::table('monetbil_callback_events')
            ->where('id', $event->id)
            ->whereNull('dispatched_at')
            ->update([
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);

        if ($rowsAffected2 === 1) {
            ProcessMonetbilCallbackEventJob::dispatch($event->id);
            $dispatched2 = true;
        }

        // Vérifications
        $this->assertTrue($dispatched1, 'Le premier appel doit dispatcher');
        $this->assertFalse($dispatched2, 'Le deuxième appel ne doit pas dispatcher (atomic claim échoué)');
        $this->assertEquals(0, $rowsAffected2, 'Le deuxième UPDATE atomique doit affecter 0 lignes');

        // Vérifier que le job a été dispatché exactement 1 fois
        Bus::assertDispatched(ProcessMonetbilCallbackEventJob::class, 1);

        // Vérifier en DB : dispatched_at non null
        $event->refresh();
        $this->assertNotNull($event->dispatched_at);
    }

    /**
     * Test que la commande requeue les events stuck (dispatched_at null)
     */
    public function test_command_requeues_stuck_events_with_null_dispatched_at(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        // Créer un event received avec dispatched_at=null
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_stuck_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'payload_hash' => hash('sha256', 'test'),
            'created_at' => now()->subMinutes(5),
        ]);

        // Exécuter la commande
        $this->artisan('payments:requeue-stuck-webhooks', [
            '--minutes' => 10,
            '--provider' => 'stripe',
        ])->assertSuccessful();

        // Vérifier que le job a été dispatché
        Bus::assertDispatched(ProcessStripeWebhookEventJob::class, 1);

        // Vérifier que dispatched_at est maintenant set
        $event->refresh();
        $this->assertNotNull($event->dispatched_at);
    }

    /**
     * Test que la commande requeue les events failed anciens
     */
    public function test_command_requeues_failed_old_events(): void
    {
        Bus::fake();
        $now = Carbon::parse('2025-12-15 10:00:00');
        Carbon::setTestNow($now);

        // Créer un event failed avec dispatched_at ancien (> 5 min)
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_failed_old_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'failed',
            'dispatched_at' => $now->copy()->subMinutes(20),
            'payload_hash' => hash('sha256', 'test'),
            'created_at' => $now->copy()->subMinutes(25),
        ]);

        // Exécuter la commande
        $this->artisan('payments:requeue-stuck-webhooks', [
            '--minutes' => 10,
            '--provider' => 'stripe',
        ])->assertSuccessful();

        // Vérifier que le job a été redispatched
        Bus::assertDispatched(ProcessStripeWebhookEventJob::class, 1);

        // Vérifier que dispatched_at a été mis à jour
        $event->refresh();
        $this->assertNotNull($event->dispatched_at);
        $this->assertTrue($event->dispatched_at->gt($now->copy()->subMinutes(20)));
    }

    /**
     * Test que la commande skip les events récents (dispatched_at récent)
     */
    public function test_command_skips_recent_events(): void
    {
        Bus::fake();
        $now = Carbon::parse('2025-12-15 10:00:00');
        Carbon::setTestNow($now);

        // Créer un event received avec dispatched_at récent (< 10 min)
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_recent_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => $now->copy()->subMinutes(1),
            'payload_hash' => hash('sha256', 'test'),
            'created_at' => $now->copy()->subMinutes(5),
        ]);

        // Exécuter la commande
        $this->artisan('payments:requeue-stuck-webhooks', [
            '--minutes' => 10,
            '--provider' => 'stripe',
        ])->assertSuccessful();

        // Vérifier que le job n'a PAS été dispatché
        Bus::assertNothingDispatched();

        // Vérifier que dispatched_at n'a pas changé
        $originalDispatchedAt = $event->dispatched_at;
        $event->refresh();
        $this->assertEquals($originalDispatchedAt->format('Y-m-d H:i:s'), $event->dispatched_at->format('Y-m-d H:i:s'));
    }

    /**
     * Test que la commande fonctionne pour Monetbil
     */
    public function test_command_requeues_monetbil_stuck_events(): void
    {
        Bus::fake();
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        // Créer un event Monetbil stuck
        $event = MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'test_stuck_monetbil'),
            'payment_ref' => 'PAY_STUCK',
            'transaction_id' => 'TXN_STUCK',
            'status' => 'received',
            'dispatched_at' => null,
            'payload' => ['test' => 'data'],
            'received_at' => now()->subMinutes(5),
            'created_at' => now()->subMinutes(5),
        ]);

        // Exécuter la commande
        $this->artisan('payments:requeue-stuck-webhooks', [
            '--minutes' => 10,
            '--provider' => 'monetbil',
        ])->assertSuccessful();

        // Vérifier que le job a été dispatché
        Bus::assertDispatched(ProcessMonetbilCallbackEventJob::class, 1);

        // Vérifier que dispatched_at est maintenant set
        $event->refresh();
        $this->assertNotNull($event->dispatched_at);
    }
}

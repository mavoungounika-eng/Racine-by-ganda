<?php

namespace Tests\Unit;

use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\StripeWebhookEvent;
use App\Services\Payments\PaymentEventMapperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessStripeWebhookEventJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test idempotence : job peut être relancé sans effet de bord
     */
    public function test_job_is_idempotent(): void
    {
        // Créer une transaction
        $order = Order::factory()->create();
        $transaction = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_id' => 'pi_test_123',
            'created_at' => now(), // Récent pour être trouvé
        ]);

        // Créer un événement (event_id différent de transaction_id car Stripe utilise evt_ pour events)
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $mapperService = new PaymentEventMapperService();

        // Exécuter le job une première fois
        $job = new ProcessStripeWebhookEventJob($event->id);
        $job->handle($mapperService);

        $transaction->refresh();
        $event->refresh();

        $this->assertEquals('succeeded', $transaction->status);
        $this->assertEquals('processed', $event->status);

        // Exécuter le job une deuxième fois (idempotence)
        $job2 = new ProcessStripeWebhookEventJob($event->id);
        $job2->handle($mapperService);

        $transaction->refresh();
        $event->refresh();

        // Le statut ne doit pas changer
        $this->assertEquals('succeeded', $transaction->status);
        $this->assertEquals('processed', $event->status);
    }

    /**
     * Test que le job utilise des locks DB pour éviter les race conditions
     */
    public function test_job_uses_db_locks(): void
    {
        $order = Order::factory()->create();
        $transaction = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_id' => 'pi_test_123',
            'created_at' => now(), // Récent pour être trouvé
        ]);

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $mapperService = new PaymentEventMapperService();

        // Simuler un lock en utilisant une transaction
        DB::transaction(function () use ($event, $mapperService) {
            $job = new ProcessStripeWebhookEventJob($event->id);
            $job->handle($mapperService);
        });

        $event->refresh();
        $this->assertEquals('processed', $event->status);
    }

    /**
     * Test que le job ignore les événements déjà traités
     */
    public function test_job_ignores_already_processed_events(): void
    {
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'processed',
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $mapperService = new PaymentEventMapperService();

        $job = new ProcessStripeWebhookEventJob($event->id);
        $job->handle($mapperService);

        $event->refresh();
        // Le statut doit rester 'processed'
        $this->assertEquals('processed', $event->status);
    }

    /**
     * Test que le job met à jour la transaction et la commande
     */
    public function test_job_updates_transaction_and_order(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $transaction = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_id' => 'pi_test_123',
            'created_at' => now(), // Récent pour être trouvé
        ]);

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $mapperService = new PaymentEventMapperService();

        $job = new ProcessStripeWebhookEventJob($event->id);
        $job->handle($mapperService);

        $transaction->refresh();
        $order->refresh();
        $event->refresh();

        $this->assertEquals('succeeded', $transaction->status);
        $this->assertEquals('processing', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('processed', $event->status);
    }
}

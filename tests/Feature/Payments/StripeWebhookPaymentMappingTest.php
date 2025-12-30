<?php

namespace Tests\Feature\Payments;

use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StripeWebhookEvent;
use App\Services\Payments\PaymentEventMapperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class StripeWebhookPaymentMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration pour les tests (dev mode, pas de vérification signature)
        config([
            'services.stripe.webhook_secret' => '',
            'app.env' => 'local', // Mode dev pour éviter la vérification de signature
        ]);
    }

    private function mockStripeConstructEvent(array $eventArray): void
    {
        // Mock alias static: Stripe\Webhook::constructEvent(...)
        $mock = Mockery::mock('alias:Stripe\Webhook');
        $mock->shouldReceive('constructEvent')
            ->andReturn((object) $eventArray);
    }

    /**
     * Test mapping payment_intent_id -> Payment.provider_payment_id
     */
    public function test_stripe_webhook_maps_payment_intent_to_payment(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'channel' => 'card',
            'status' => 'initiated',
            'provider_payment_id' => 'pi_123',
            'external_reference' => 'cs_test_abc',
        ]);

        $event = [
            'id' => 'evt_1',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_123',
                ],
            ],
        ];

        $this->mockStripeConstructEvent($event);

        // 1) Appel endpoint => persist event + dispatch job
        // Envoyer le payload JSON brut (comme Stripe le ferait)
        $res = $this->call('POST', '/api/webhooks/stripe', [], [], [], [], json_encode($event));
        $res->assertStatus(200);

        Queue::assertPushed(ProcessStripeWebhookEventJob::class);

        // 2) Exécuter le job manuellement pour valider l'effet DB
        $webhookEvent = StripeWebhookEvent::where('event_id', 'evt_1')->firstOrFail();
        (new ProcessStripeWebhookEventJob($webhookEvent->id))->handle(app(PaymentEventMapperService::class));

        $payment->refresh();
        $order->refresh();
        $webhookEvent->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);

        $this->assertSame('processed', $webhookEvent->status);
        $this->assertSame($payment->id, $webhookEvent->payment_id);
        $this->assertSame('pi_123', $webhookEvent->payment_intent_id);
    }

    /**
     * Test mapping checkout_session_id -> Payment.external_reference
     */
    public function test_stripe_webhook_maps_checkout_session_to_payment(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'channel' => 'card',
            'status' => 'initiated',
            'provider_payment_id' => null,
            'external_reference' => 'cs_test_999',
        ]);

        $event = [
            'id' => 'evt_2',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_999',
                    'payment_intent' => null,
                ],
            ],
        ];

        $this->mockStripeConstructEvent($event);

        $res = $this->call('POST', '/api/webhooks/stripe', [], [], [], [], json_encode($event));
        $res->assertStatus(200);

        Queue::assertPushed(ProcessStripeWebhookEventJob::class);

        $webhookEvent = StripeWebhookEvent::where('event_id', 'evt_2')->firstOrFail();
        (new ProcessStripeWebhookEventJob($webhookEvent->id))->handle(app(PaymentEventMapperService::class));

        $payment->refresh();
        $order->refresh();
        $webhookEvent->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);

        $this->assertSame('processed', $webhookEvent->status);
        $this->assertSame($payment->id, $webhookEvent->payment_id);
        $this->assertSame('cs_test_999', $webhookEvent->checkout_session_id);
    }

    /**
     * Test idempotence : même event_id 2x => un seul dispatch
     */
    public function test_stripe_webhook_event_idempotent(): void
    {
        Queue::fake();

        $event = [
            'id' => 'evt_same',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_X']],
        ];
        $this->mockStripeConstructEvent($event);

        // 1er call
        $this->call('POST', '/api/webhooks/stripe', [], [], [], [], json_encode($event))->assertStatus(200);

        // 2e call (même event_id)
        $this->call('POST', '/api/webhooks/stripe', [], [], [], [], json_encode($event))->assertStatus(200);

        $this->assertSame(1, StripeWebhookEvent::where('event_id', 'evt_same')->count());

        // Selon ta logique, il ne doit pas redispatch si déjà processed,
        // mais ici l'event n'est pas encore processed (job non exécuté).
        // On vérifie au minimum "pas 2 créations d'event".
        // Si ton controller empêche aussi double dispatch via dispatched_at, on peut tester :
        Queue::assertPushed(ProcessStripeWebhookEventJob::class, 1);
    }
}


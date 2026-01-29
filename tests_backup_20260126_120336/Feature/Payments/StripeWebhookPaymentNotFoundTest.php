<?php

namespace Tests\Feature\Payments;

use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\Order;
use App\Models\StripeWebhookEvent;
use App\Services\Payments\PaymentEventMapperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripeWebhookPaymentNotFoundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration pour les tests (dev mode, pas de vérification signature)
        config([
            'services.stripe.webhook_secret' => '',
            'app.env' => 'local', // Mode dev pour éviter la vérification de signature
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockStripeConstructEvent(array $eventArray): void
    {
        $mock = Mockery::mock('alias:Stripe\Webhook');
        $mock->shouldReceive('constructEvent')->andReturn((object) $eventArray);
    }

    public function test_stripe_webhook_fails_when_payment_not_found(): void
    {
        $order = Order::factory()->create([
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $event = [
            'id' => 'evt_nf',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_DOES_NOT_EXIST',
                ],
            ],
        ];
        $this->mockStripeConstructEvent($event);

        $this->call('POST', '/api/webhooks/stripe', [], [], [], [], json_encode($event))->assertStatus(200);

        $webhookEvent = StripeWebhookEvent::where('event_id', 'evt_nf')->firstOrFail();

        $mapperService = app(PaymentEventMapperService::class);

        // Simuler les 2 premières tentatives (qui vont throw pour retry)
        $job1 = new ProcessStripeWebhookEventJob($webhookEvent->id);
        try {
            $job1->handle($mapperService);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Payment not found', $e->getMessage());
        }

        $job2 = new ProcessStripeWebhookEventJob($webhookEvent->id);
        try {
            $job2->handle($mapperService);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Payment not found', $e->getMessage());
        }

        // Dernière tentative : simuler attempts() === tries
        $job3 = new class($webhookEvent->id) extends ProcessStripeWebhookEventJob {
            public function attempts(): int
            {
                return 3; // Simuler dernière tentative (tries = 3)
            }
        };
        $job3->handle($mapperService);

        $webhookEvent->refresh();
        $order->refresh();

        // Selon ton implémentation actuelle : markAsFailed() -> status = failed
        $this->assertSame('failed', $webhookEvent->status);

        // Order ne doit pas bouger
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $order->status);
    }

    public function test_stripe_webhook_does_not_update_order_when_payment_not_found(): void
    {
        $order = Order::factory()->create([
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $paymentIntentId = 'pi_test_not_found_999';

        $event = [
            'id' => 'evt_test_order_unchanged',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $paymentIntentId,
                    'status' => 'succeeded',
                ],
            ],
        ];

        $this->mockStripeConstructEvent($event);

        // Appeler l'endpoint webhook
        $response = $this->call('POST', '/api/webhooks/stripe', [], [], [], [], json_encode($event));
        $response->assertStatus(200);

        $webhookEvent = StripeWebhookEvent::where('event_id', 'evt_test_order_unchanged')->firstOrFail();

        // Simuler dernière tentative (Payment introuvable)
        $mapperService = app(PaymentEventMapperService::class);
        
        // Créer un job mock qui simule attempts() === tries
        $job = new class($webhookEvent->id) extends ProcessStripeWebhookEventJob {
            public function attempts(): int
            {
                return 3; // Simuler dernière tentative
            }
        };

        // Exécuter
        $job->handle($mapperService);

        // Vérifier que l'Order est inchangée
        $order->refresh();
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals('pending', $order->status);

        // Vérifier que l'événement est failed
        $webhookEvent->refresh();
        $this->assertEquals('failed', $webhookEvent->status);
    }
}

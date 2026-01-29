<?php

namespace Tests\Feature\Security;

use App\Models\Order;
use App\Models\Payment;
use App\Models\StripeWebhookEvent;
use App\Jobs\ProcessStripeWebhookEventJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test qu'un même webhook Stripe reçu deux fois ne valide pas la commande deux fois
     * et ne crée pas de doublons comptables.
     */
    public function test_stripe_webhook_idempotency()
    {
        // 1. Préparation
        $order = Order::factory()->create([
            'total_amount' => 5000,
            'payment_status' => 'pending'
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 5000,
            'status' => 'pending',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123'
        ]);

        $payload = [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 500000, // 5000.00
                    'currency' => 'xaf',
                    'status' => 'succeeded'
                ]
            ]
        ];

        // 2. Premier appel du webhook (simulé via le controller ou directement le job pour plus de précision)
        $requestData = [
            'event_id' => 'evt_test_123',
            'event_type' => 'payment_intent.succeeded',
            'payload_hash' => hash('sha256', json_encode($payload)),
            'payment_intent_id' => 'pi_test_123',
            'status' => 'received'
        ];

        $webhookEvent = StripeWebhookEvent::create($requestData);

        // Lancer le job une première fois (Simule le traitement)
        // Note: On utilise 'paid' au lieu de 'succeeded' car PaymentEventMapperService mappe Stripe succeeded -> paid
        $job = new ProcessStripeWebhookEventJob($webhookEvent->id);
        app()->call([$job, 'handle']);

        $order->refresh();
        $payment->refresh();

        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('processed', $webhookEvent->fresh()->status);

        // 3. Second appel (Simulé) - Même event_id
        // Le controller ferait un firstOrCreate qui retournerait l'existant.
        // On relance le job sur le même événement
        app()->call([$job, 'handle']);

        // Vérifier que rien n'a bougé
        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertCount(1, Payment::all());
        $this->assertEquals('processed', $webhookEvent->fresh()->status);
        
        // On pourrait vérifier ici le nombre d'entrées comptables si le module Accounting est actif
    }
}

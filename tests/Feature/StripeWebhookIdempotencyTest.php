<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests d'idempotence pour les webhooks Stripe
 */
class StripeWebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Order $order;
    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $this->product = Product::factory()->create([
            'stock' => 10,
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'payment_method' => 'card',
            'payment_status' => 'pending',
            'status' => 'pending',
            'total_amount' => 10000,
        ]);

        $this->payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'provider' => 'stripe',
            'channel' => 'card',
            'status' => 'pending',
            'external_reference' => 'cs_test_1234567890',
            'provider_payment_id' => 'pi_test_1234567890',
            'amount' => 10000,
        ]);

        // Configuration Stripe pour les tests
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
    }

    /**
     * Helper pour générer une signature Stripe valide
     */
    private function generateStripeSignature(string $payload, string $secret, int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        return "t={$timestamp},v1={$signature}";
    }

    /**
     * Créer un payload d'événement Stripe
     */
    private function createStripeEventPayload(string $eventId, string $eventType, array $objectData): string
    {
        return json_encode([
            'id' => $eventId,
            'type' => $eventType,
            'data' => [
                'object' => $objectData,
            ],
        ]);
    }

    #[Test]
    public function test_webhook_is_idempotent_for_same_event_id(): void
    {
        $eventId = 'evt_test_1234567890';
        $sessionId = 'cs_test_1234567890';
        
        $payload = $this->createStripeEventPayload($eventId, 'checkout.session.completed', [
            'id' => $sessionId,
            'payment_intent' => 'pi_test_1234567890',
            'payment_status' => 'paid',
        ]);

        $signature = $this->generateStripeSignature($payload, 'whsec_test_secret');

        // Premier appel
        $response1 = $this->call('POST', '/payment/card/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload);

        $response1->assertStatus(200);

        // Vérifier que l'événement a été créé
        $this->assertDatabaseHas('stripe_webhook_events', [
            'event_id' => $eventId,
            'status' => 'processed',
        ]);

        // Vérifier que le Payment a été mis à jour
        $this->payment->refresh();
        $this->assertEquals('paid', $this->payment->status);

        // Compter les événements PaymentCompleted
        Event::fake();
        
        // Deuxième appel avec le même event_id (idempotence)
        $response2 = $this->call('POST', '/payment/card/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload);

        $response2->assertStatus(200);

        // Vérifier qu'il n'y a toujours qu'un seul événement dans la table
        $this->assertEquals(1, StripeWebhookEvent::where('event_id', $eventId)->count());

        // Vérifier que le Payment n'a pas été modifié deux fois
        $this->payment->refresh();
        $this->assertEquals('paid', $this->payment->status);

        // Vérifier que l'événement est toujours marqué comme processed
        $webhookEvent = StripeWebhookEvent::where('event_id', $eventId)->first();
        $this->assertEquals('processed', $webhookEvent->status);
        $this->assertNotNull($webhookEvent->processed_at);
    }

    #[Test]
    public function test_webhook_handles_duplicate_key_gracefully(): void
    {
        $eventId = 'evt_test_duplicate';
        $sessionId = 'cs_test_1234567890';
        
        $payload = $this->createStripeEventPayload($eventId, 'checkout.session.completed', [
            'id' => $sessionId,
            'payment_intent' => 'pi_test_1234567890',
            'payment_status' => 'paid',
        ]);

        $signature = $this->generateStripeSignature($payload, 'whsec_test_secret');

        // Créer l'événement manuellement (simuler un duplicate key)
        StripeWebhookEvent::create([
            'event_id' => $eventId,
            'event_type' => 'checkout.session.completed',
            'status' => 'processed',
            'payment_id' => $this->payment->id,
            'processed_at' => now(),
        ]);

        // Appel avec event_id déjà existant
        $response = $this->call('POST', '/payment/card/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload);

        // Doit retourner 200 (idempotent)
        $response->assertStatus(200);

        // Vérifier qu'il n'y a toujours qu'un seul événement
        $this->assertEquals(1, StripeWebhookEvent::where('event_id', $eventId)->count());
    }

    #[Test]
    public function test_webhook_prevents_double_payment_with_lock(): void
    {
        $eventId = 'evt_test_lock';
        $sessionId = 'cs_test_1234567890';
        
        $payload = $this->createStripeEventPayload($eventId, 'checkout.session.completed', [
            'id' => $sessionId,
            'payment_intent' => 'pi_test_1234567890',
            'payment_status' => 'paid',
        ]);

        $signature = $this->generateStripeSignature($payload, 'whsec_test_secret');

        // Marquer le Payment comme déjà payé
        $this->payment->update(['status' => 'paid']);

        // Appel webhook
        $response = $this->call('POST', '/payment/card/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload);

        $response->assertStatus(200);

        // Vérifier que l'événement est marqué comme ignoré (déjà payé)
        $webhookEvent = StripeWebhookEvent::where('event_id', $eventId)->first();
        $this->assertNotNull($webhookEvent);
        $this->assertEquals('ignored', $webhookEvent->status);
        $this->assertEquals($this->payment->id, $webhookEvent->payment_id);
    }
}






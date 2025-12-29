<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de gestion des événements hors ordre
 * 
 * Vérifie que le système gère correctement les cas où:
 * - Le webhook arrive avant le callback utilisateur
 * - Le callback arrive avant le webhook
 * - Les événements sont traités plusieurs fois
 */
class OutOfOrderEventsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que le webhook traité avant le callback ne cause pas de double transition
     * 
     * Scénario réel: Webhook Stripe arrive avant que l'utilisateur ne soit redirigé
     */
    public function test_webhook_before_callback_maintains_consistency(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'currency' => 'XAF',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_webhook_first',
            'status' => 'pending',
        ]);

        // 1. Webhook arrive en premier
        $webhookPayload = [
            'id' => 'evt_webhook_first',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_webhook_first',
                    'amount' => 10000,
                    'currency' => 'xaf',
                    'status' => 'succeeded',
                ],
            ],
        ];

        $this->postJson('/api/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => 'valid_signature',
        ]);

        // Vérifier que la commande est payée
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        $payment->refresh();
        $this->assertEquals('confirmed', $payment->status);

        // 2. Callback utilisateur arrive ensuite
        $response = $this->actingAs($user)
            ->get(route('checkout.card.success', ['order' => $order->id]));

        // Vérifier qu'il n'y a pas de double transition
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // Vérifier qu'il n'y a toujours qu'un seul paiement
        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());

        // Vérifier que la page de succès s'affiche correctement
        $response->assertStatus(200);
    }

    /**
     * Test que le callback traité avant le webhook ne cause pas de double transition
     */
    public function test_callback_before_webhook_maintains_consistency(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'currency' => 'XAF',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_callback_first',
            'status' => 'pending',
        ]);

        // 1. Callback utilisateur arrive en premier (avec session Stripe valide)
        // Note: Dans la vraie vie, le callback vérifie le payment_intent via l'API Stripe
        $response = $this->actingAs($user)
            ->get(route('checkout.card.success', ['order' => $order->id]));

        // La commande devrait être marquée comme payée
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // 2. Webhook arrive ensuite
        $webhookPayload = [
            'id' => 'evt_callback_first',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_callback_first',
                    'amount' => 10000,
                    'currency' => 'xaf',
                    'status' => 'succeeded',
                ],
            ],
        ];

        $this->postJson('/api/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => 'valid_signature',
        ]);

        // Vérifier qu'il n'y a pas de double transition
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // Vérifier qu'il n'y a toujours qu'un seul paiement
        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());
    }

    /**
     * Test que plusieurs webhooks identiques sont idempotents
     */
    public function test_duplicate_webhooks_are_idempotent(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'currency' => 'XAF',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_duplicate',
            'status' => 'pending',
        ]);

        $webhookPayload = [
            'id' => 'evt_duplicate_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_duplicate',
                    'amount' => 10000,
                    'currency' => 'xaf',
                    'status' => 'succeeded',
                ],
            ],
        ];

        // Envoyer le webhook 3 fois
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/webhooks/stripe', $webhookPayload, [
                'Stripe-Signature' => 'valid_signature',
            ]);
        }

        // Vérifier qu'il n'y a qu'un seul paiement
        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());

        // Vérifier que la commande est payée une seule fois
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // Vérifier que l'événement webhook n'a été traité qu'une fois
        $this->assertEquals(1, \App\Models\PaymentEvent::where('event_id', 'evt_duplicate_123')->count());
    }

    /**
     * Test que les événements contradictoires sont gérés correctement
     */
    public function test_contradictory_events_are_handled_correctly(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'currency' => 'XAF',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_contradictory',
            'status' => 'pending',
        ]);

        // 1. Webhook succeeded
        $successPayload = [
            'id' => 'evt_success',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_contradictory',
                    'amount' => 10000,
                    'currency' => 'xaf',
                    'status' => 'succeeded',
                ],
            ],
        ];

        $this->postJson('/api/webhooks/stripe', $successPayload, [
            'Stripe-Signature' => 'valid_signature',
        ]);

        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // 2. Webhook failed arrive ensuite (retard réseau)
        $failedPayload = [
            'id' => 'evt_failed',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_contradictory',
                    'amount' => 10000,
                    'currency' => 'xaf',
                    'status' => 'failed',
                ],
            ],
        ];

        $this->postJson('/api/webhooks/stripe', $failedPayload, [
            'Stripe-Signature' => 'valid_signature',
        ]);

        // Vérifier que la commande reste payée (succeeded prime sur failed)
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // Vérifier que le paiement reste confirmé
        $payment->refresh();
        $this->assertEquals('confirmed', $payment->status);
    }

    /**
     * Test que le timeout de session n'affecte pas un paiement déjà confirmé
     */
    public function test_session_timeout_does_not_affect_confirmed_payment(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'currency' => 'XAF',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_timeout',
            'status' => 'pending',
        ]);

        // Webhook confirme le paiement
        $webhookPayload = [
            'id' => 'evt_timeout',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_timeout',
                    'amount' => 10000,
                    'currency' => 'xaf',
                    'status' => 'succeeded',
                ],
            ],
        ];

        $this->postJson('/api/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => 'valid_signature',
        ]);

        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // Simuler timeout de session (utilisateur ne revient jamais)
        // La commande doit rester payée
        $this->travel(1)->hours();

        $order->refresh();
        $this->assertEquals('paid', $order->status);

        $payment->refresh();
        $this->assertEquals('confirmed', $payment->status);
    }
}

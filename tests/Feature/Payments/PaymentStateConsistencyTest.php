<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de cohérence des états de paiement
 * 
 * Vérifie que les paiements ne peuvent pas modifier des commandes
 * dans des états terminaux (cancelled, refunded, etc.)
 */
class PaymentStateConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test qu'un paiement confirmé sur une commande annulée ne la ressuscite pas
     * 
     * Scénario réel: Webhook Stripe arrive après annulation manuelle
     */
    public function test_payment_confirmed_on_cancelled_order_does_not_resurrect_it(): void
    {
        // Créer une commande annulée
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelled',
            'total' => 100.00,
        ]);

        // Créer un paiement en attente
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'currency' => 'XAF',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
            'status' => 'pending',
        ]);

        // Simuler webhook Stripe payment_intent.succeeded
        $webhookPayload = [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'amount' => 10000, // 100.00 XAF en centimes
                    'currency' => 'xaf',
                    'status' => 'succeeded',
                ],
            ],
        ];

        // Envoyer le webhook (simuler signature valide)
        $response = $this->postJson('/api/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => 'valid_signature_mock',
        ]);

        // Vérifier que la commande reste annulée
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);

        // Vérifier que le paiement est marqué comme ignoré ou reste pending
        $payment->refresh();
        $this->assertNotEquals('confirmed', $payment->status);

        // Vérifier qu'un log d'audit existe
        // (Adapter selon votre système de logging)
        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $payment->id,
            'event_type' => 'payment_intent.succeeded',
        ]);
    }

    /**
     * Test qu'un paiement confirmé sur une commande déjà payée est idempotent
     */
    public function test_payment_confirmed_on_already_paid_order_is_idempotent(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'paid',
            'total' => 100.00,
        ]);

        // Paiement déjà confirmé
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'currency' => 'XAF',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_456',
            'status' => 'confirmed',
        ]);

        // Webhook duplicate (même payment_intent)
        $webhookPayload = [
            'id' => 'evt_test_456',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_456',
                    'amount' => 10000,
                    'currency' => 'xaf',
                    'status' => 'succeeded',
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => 'valid_signature_mock',
        ]);

        // Vérifier que rien n'a changé
        $order->refresh();
        $this->assertEquals('paid', $order->status);

        $payment->refresh();
        $this->assertEquals('confirmed', $payment->status);

        // Vérifier qu'il n'y a pas de double paiement
        $this->assertEquals(1, Payment::where('order_id', $order->id)->count());
    }

    /**
     * Test qu'un paiement échoué sur une commande en attente la marque comme failed
     */
    public function test_payment_failed_on_pending_order_marks_it_as_failed(): void
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
            'provider_payment_id' => 'pi_test_789',
            'status' => 'pending',
        ]);

        // Webhook payment_intent.payment_failed
        $webhookPayload = [
            'id' => 'evt_test_789',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_789',
                    'amount' => 10000,
                    'currency' => 'xaf',
                    'status' => 'failed',
                    'last_payment_error' => [
                        'message' => 'Card declined',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => 'valid_signature_mock',
        ]);

        // Vérifier que la commande est marquée comme failed
        $order->refresh();
        $this->assertEquals('failed', $order->status);

        // Vérifier que le paiement est marqué comme failed
        $payment->refresh();
        $this->assertEquals('failed', $payment->status);
    }

    /**
     * Test qu'un remboursement sur une commande non-remboursable est rejeté
     */
    public function test_refund_on_non_refundable_order_is_rejected(): void
    {
        $user = User::factory()->create();
        
        // Commande en cours de livraison (non remboursable)
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'shipped',
            'total' => 100.00,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'currency' => 'XAF',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_refund',
            'status' => 'confirmed',
        ]);

        // Tentative de remboursement
        $webhookPayload = [
            'id' => 'evt_test_refund',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_refund',
                    'payment_intent' => 'pi_test_refund',
                    'amount_refunded' => 10000,
                    'refunded' => true,
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/stripe', $webhookPayload, [
            'Stripe-Signature' => 'valid_signature_mock',
        ]);

        // Vérifier que la commande reste shipped
        $order->refresh();
        $this->assertEquals('shipped', $order->status);

        // Vérifier que le paiement reste confirmed (pas refunded)
        $payment->refresh();
        $this->assertEquals('confirmed', $payment->status);
    }
}

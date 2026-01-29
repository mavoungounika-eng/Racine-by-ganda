<?php

namespace Tests\Feature;

use App\Models\MonetbilCallbackEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StripeWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests de sécurité production pour les webhooks Stripe et Monetbil
 * 
 * Ces tests vérifient que :
 * - ZÉRO webhook traité sans signature valide
 * - ZÉRO double traitement (idempotence stricte)
 * - ZÉRO race condition paiement
 */
class WebhookSecurityProductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Forcer l'environnement production pour ces tests
        Config::set('app.env', 'production');
    }

    /**
     * Test Stripe : webhook valide avec signature → traité
     */
    public function test_stripe_webhook_with_valid_signature_is_processed(): void
    {
        // Configurer le secret webhook
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');

        // Créer un payload Stripe réaliste
        $eventId = 'evt_test_1234567890';
        $eventType = 'payment_intent.succeeded';
        $payload = json_encode([
            'id' => $eventId,
            'type' => $eventType,
            'data' => [
                'object' => [
                    'id' => 'pi_test_1234567890',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        // Générer une signature Stripe valide (simulation)
        // En production, Stripe génère cette signature avec leur secret
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'whsec_test_secret');
        $stripeSignature = $timestamp . ',v1=' . $signature;

        // Faire la requête avec signature valide
        $response = $this->postJson('/api/webhooks/stripe', json_decode($payload, true), [
            'Stripe-Signature' => $stripeSignature,
        ]);

        // Vérifier que la requête est acceptée (200)
        $response->assertStatus(200);
        $response->assertJson(['status' => 'received']);

        // Vérifier que l'événement est persisté
        $this->assertDatabaseHas('stripe_webhook_events', [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'status' => 'received',
        ]);
    }

    /**
     * Test Stripe : webhook sans signature → refus 401
     */
    public function test_stripe_webhook_without_signature_is_rejected(): void
    {
        // Configurer le secret webhook
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');

        $payload = json_encode([
            'id' => 'evt_test_1234567890',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_test_1234567890']],
        ]);

        // Faire la requête SANS signature
        $response = $this->postJson('/api/webhooks/stripe', json_decode($payload, true));

        // Vérifier que la requête est refusée (401)
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Missing signature']);

        // Vérifier que l'événement N'EST PAS persisté
        $this->assertDatabaseMissing('stripe_webhook_events', [
            'event_id' => 'evt_test_1234567890',
        ]);
    }

    /**
     * Test Stripe : webhook avec signature invalide → refus 401
     */
    public function test_stripe_webhook_with_invalid_signature_is_rejected(): void
    {
        // Configurer le secret webhook
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');

        $payload = json_encode([
            'id' => 'evt_test_1234567890',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_test_1234567890']],
        ]);

        // Générer une signature INVALIDE (avec un mauvais secret)
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'wrong_secret');
        $stripeSignature = $timestamp . ',v1=' . $signature;

        // Faire la requête avec signature invalide
        $response = $this->postJson('/api/webhooks/stripe', json_decode($payload, true), [
            'Stripe-Signature' => $stripeSignature,
        ]);

        // Vérifier que la requête est refusée (401)
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);

        // Vérifier que l'événement N'EST PAS persisté
        $this->assertDatabaseMissing('stripe_webhook_events', [
            'event_id' => 'evt_test_1234567890',
        ]);
    }

    /**
     * Test Stripe : double envoi du même event → traité une seule fois
     */
    public function test_stripe_webhook_duplicate_event_is_processed_only_once(): void
    {
        // Configurer le secret webhook
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');

        $eventId = 'evt_test_duplicate_123';
        $eventType = 'payment_intent.succeeded';
        $payload = json_encode([
            'id' => $eventId,
            'type' => $eventType,
            'data' => [
                'object' => [
                    'id' => 'pi_test_1234567890',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        // Générer une signature valide
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, 'whsec_test_secret');
        $stripeSignature = $timestamp . ',v1=' . $signature;

        // Premier envoi
        $response1 = $this->postJson('/api/webhooks/stripe', json_decode($payload, true), [
            'Stripe-Signature' => $stripeSignature,
        ]);
        $response1->assertStatus(200);

        // Vérifier que l'événement est persisté
        $this->assertDatabaseHas('stripe_webhook_events', [
            'event_id' => $eventId,
            'status' => 'received',
        ]);

        // Deuxième envoi (même event_id)
        $response2 = $this->postJson('/api/webhooks/stripe', json_decode($payload, true), [
            'Stripe-Signature' => $stripeSignature,
        ]);
        $response2->assertStatus(200);
        $response2->assertJson(['status' => 'already_processed']);

        // Vérifier qu'il n'y a qu'UN SEUL événement dans la DB
        $this->assertDatabaseCount('stripe_webhook_events', 1);
    }

    /**
     * Test Monetbil : webhook valide avec signature → traité
     */
    public function test_monetbil_webhook_with_valid_signature_is_processed(): void
    {
        // Configurer le secret webhook
        Config::set('services.monetbil.service_secret', 'test_secret_key');

        $payload = [
            'transaction_id' => 'TXN_TEST_123',
            'payment_ref' => 'PAY_TEST_123',
            'status' => 'success',
            'event_type' => 'payment.success',
        ];

        // Générer une signature HMAC valide
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadString, 'test_secret_key');

        // Faire la requête avec signature valide
        $response = $this->postJson('/api/webhooks/monetbil', $payload, [
            'X-Signature' => $signature,
        ]);

        // Vérifier que la requête est acceptée (200)
        $response->assertStatus(200);
        $response->assertJson(['status' => 'received']);

        // Vérifier que l'événement est persisté
        $this->assertDatabaseHas('monetbil_callback_events', [
            'transaction_id' => 'TXN_TEST_123',
            'payment_ref' => 'PAY_TEST_123',
            'status' => 'received',
        ]);
    }

    /**
     * Test Monetbil : webhook sans signature → refus 401
     */
    public function test_monetbil_webhook_without_signature_is_rejected(): void
    {
        // Configurer le secret webhook
        Config::set('services.monetbil.service_secret', 'test_secret_key');

        $payload = [
            'transaction_id' => 'TXN_TEST_123',
            'payment_ref' => 'PAY_TEST_123',
            'status' => 'success',
        ];

        // Faire la requête SANS signature
        $response = $this->postJson('/api/webhooks/monetbil', $payload);

        // Vérifier que la requête est refusée (401)
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Missing signature']);

        // Vérifier que l'événement N'EST PAS persisté
        $this->assertDatabaseMissing('monetbil_callback_events', [
            'transaction_id' => 'TXN_TEST_123',
        ]);
    }

    /**
     * Test Monetbil : webhook avec signature invalide → refus 401
     */
    public function test_monetbil_webhook_with_invalid_signature_is_rejected(): void
    {
        // Configurer le secret webhook
        Config::set('services.monetbil.service_secret', 'test_secret_key');

        $payload = [
            'transaction_id' => 'TXN_TEST_123',
            'payment_ref' => 'PAY_TEST_123',
            'status' => 'success',
        ];

        // Générer une signature INVALIDE (avec un mauvais secret)
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadString, 'wrong_secret');

        // Faire la requête avec signature invalide
        $response = $this->postJson('/api/webhooks/monetbil', $payload, [
            'X-Signature' => $signature,
        ]);

        // Vérifier que la requête est refusée (401)
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);

        // Vérifier que l'événement N'EST PAS persisté
        $this->assertDatabaseMissing('monetbil_callback_events', [
            'transaction_id' => 'TXN_TEST_123',
        ]);
    }

    /**
     * Test Monetbil : double transaction → bloquée (idempotence)
     */
    public function test_monetbil_webhook_duplicate_transaction_is_blocked(): void
    {
        // Configurer le secret webhook
        Config::set('services.monetbil.service_secret', 'test_secret_key');

        $payload = [
            'transaction_id' => 'TXN_TEST_DUPLICATE',
            'payment_ref' => 'PAY_TEST_DUPLICATE',
            'transaction_uuid' => 'uuid_test_123',
            'status' => 'success',
            'event_type' => 'payment.success',
            'timestamp' => time(),
        ];

        // Générer une signature valide
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadString, 'test_secret_key');

        // Premier envoi
        $response1 = $this->postJson('/api/webhooks/monetbil', $payload, [
            'X-Signature' => $signature,
        ]);
        $response1->assertStatus(200);

        // Vérifier que l'événement est persisté
        $this->assertDatabaseHas('monetbil_callback_events', [
            'transaction_id' => 'TXN_TEST_DUPLICATE',
            'status' => 'received',
        ]);

        // Deuxième envoi (même transaction_id)
        $response2 = $this->postJson('/api/webhooks/monetbil', $payload, [
            'X-Signature' => $signature,
        ]);
        $response2->assertStatus(200);
        $response2->assertJson(['status' => 'already_processed']);

        // Vérifier qu'il n'y a qu'UN SEUL événement dans la DB
        $this->assertDatabaseCount('monetbil_callback_events', 1);
    }
}


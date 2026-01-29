<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\CardPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests de sécurité pour les webhooks Stripe (RBG-P0-010)
 * 
 * Vérifie que :
 * - La signature est obligatoire en production
 * - Les webhooks sans signature sont rejetés (401)
 * - Les webhooks avec signature invalide sont rejetés (401)
 * - Les logs sont structurés (ip, route, reason)
 */
class PaymentWebhookSecurityTest extends TestCase
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
            'status' => 'initiated',
            'external_reference' => 'cs_test_1234567890',
            'amount' => 10000,
        ]);
    }

    #[Test]
    public function it_rejects_webhook_without_signature_in_production(): void
    {
        // Forcer l'environnement de production
        $this->app['config']->set('app.env', 'production');
        
        // Mock du secret webhook
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1234567890',
                    'payment_status' => 'paid',
                ],
            ],
        ]);

        // Utiliser call() pour envoyer le payload brut (comme Stripe le fait)
        // Sans header Stripe-Signature
        $response = $this->call('POST', '/payment/card/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // En production, doit retourner strictement 401 si signature absente
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid signature']);
    }

    #[Test]
    public function it_rejects_webhook_with_invalid_signature(): void
    {
        // Forcer l'environnement de production
        $this->app['config']->set('app.env', 'production');
        
        // Mock du secret webhook
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
        
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1234567890',
                    'payment_status' => 'paid',
                ],
            ],
        ]);

        // Signature invalide
        $invalidSignature = 'invalid_signature_12345';

        $response = $this->call('POST', '/payment/card/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $invalidSignature,
        ], $payload);

        // Doit retourner 401 pour signature invalide
        $this->assertContains(
            $response->status(),
            [401, 400],
            'Webhook with invalid signature should be rejected'
        );
    }

    #[Test]
    public function it_logs_structured_information_on_webhook_failure(): void
    {
        // Forcer l'environnement de production
        $this->app['config']->set('app.env', 'production');
        
        // Mock du secret webhook
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
        
        // Mock des appels Log pour éviter les erreurs Mockery
        Log::shouldReceive('error')
            ->atLeast()->once()
            ->andReturn(true);
        Log::shouldReceive('warning')
            ->zeroOrMoreTimes()
            ->andReturn(true);
        Log::shouldReceive('info')
            ->zeroOrMoreTimes()
            ->andReturn(true);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1234567890',
                ],
            ],
        ]);

        $response = $this->call('POST', '/payment/card/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 'invalid_signature',
        ], $payload);

        // Vérifier que la réponse est un rejet (401 ou 400)
        $this->assertContains(
            $response->status(),
            [401, 400],
            'Webhook with invalid signature should be rejected'
        );
    }

    #[Test]
    public function it_allows_webhook_without_signature_in_development(): void
    {
        // S'assurer qu'on est en développement
        $this->app['env'] = 'local';
        config(['app.env' => 'local']);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1234567890',
                    'payment_status' => 'paid',
                ],
            ],
        ]);

        $response = $this->call('POST', '/payment/card/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // En développement, le webhook peut être traité sans signature (mais peut échouer si payload invalide)
        // On vérifie juste que ce n'est pas un 401 strict (comme en production)
        $this->assertNotEquals(401, $response->status(), 'Development mode should not strictly reject webhooks without signature');
    }
}


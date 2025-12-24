<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\MonetbilService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests pour les paiements Mobile Money via Monetbil
 */
class MonetbilPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Order $order;

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
            'payment_method' => 'monetbil',
            'payment_status' => 'pending',
            'status' => 'pending',
            'total_amount' => 10000,
            'order_number' => 'ORDER-12345',
        ]);

        // Configuration Monetbil pour les tests
        config([
            'services.monetbil.service_key' => 'test_service_key',
            'services.monetbil.service_secret' => 'test_service_secret',
            'services.monetbil.widget_version' => 'v2.1',
            'services.monetbil.country' => 'CG',
            'services.monetbil.currency' => 'XAF',
            'services.monetbil.notify_url' => 'https://example.com/payment/monetbil/notify',
            'services.monetbil.return_url' => 'https://example.com/checkout/success',
        ]);
    }

    #[Test]
    public function test_notify_rejects_missing_signature_in_production(): void
    {
        // Forcer l'environnement de production
        $this->app['config']->set('app.env', 'production');

        $paymentRef = 'ORDER-12345';
        
        // Créer une transaction
        PaymentTransaction::create([
            'provider' => 'monetbil',
            'order_id' => $this->order->id,
            'payment_ref' => $paymentRef,
            'amount' => 10000,
            'currency' => 'XAF',
            'status' => 'pending',
        ]);

        // Notification SANS signature
        $response = $this->postJson('/payment/monetbil/notify', [
            'payment_ref' => $paymentRef,
            'status' => 'success',
            'transaction_id' => 'TXN-123',
        ]);

        // En production, doit retourner 401 pour signature absente
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Missing signature']);
    }

    #[Test]
    public function test_notify_rejects_invalid_signature_in_production(): void
    {
        // Forcer l'environnement de production
        $this->app['config']->set('app.env', 'production');

        $paymentRef = 'ORDER-12345';
        
        // Créer une transaction
        PaymentTransaction::create([
            'provider' => 'monetbil',
            'order_id' => $this->order->id,
            'payment_ref' => $paymentRef,
            'amount' => 10000,
            'currency' => 'XAF',
            'status' => 'pending',
        ]);

        // Notification avec signature invalide
        $response = $this->postJson('/payment/monetbil/notify', [
            'payment_ref' => $paymentRef,
            'status' => 'success',
            'transaction_id' => 'TXN-123',
            'sign' => 'invalid_signature',
        ]);

        // En production, doit retourner 401 pour signature invalide (aligné avec Stripe)
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid signature']);
    }

    #[Test]
    public function test_notify_returns_400_on_invalid_payload(): void
    {
        // Test 1: Missing payment_ref
        $response1 = $this->postJson('/payment/monetbil/notify', [
            'status' => 'success',
        ]);

        $response1->assertStatus(400);
        $response1->assertJson(['message' => 'Missing payment_ref']);

        // Test 2: Missing status
        $paymentRef = 'ORDER-12345';
        PaymentTransaction::create([
            'provider' => 'monetbil',
            'order_id' => $this->order->id,
            'payment_ref' => $paymentRef,
            'amount' => 10000,
            'currency' => 'XAF',
            'status' => 'pending',
        ]);

        $response2 = $this->postJson('/payment/monetbil/notify', [
            'payment_ref' => $paymentRef,
        ]);

        $response2->assertStatus(400);
        $response2->assertJson(['message' => 'Missing status']);
    }

    #[Test]
    public function test_notify_accepts_success_and_marks_order_paid(): void
    {
        $paymentRef = 'ORDER-12345';
        
        // Créer une transaction
        $transaction = PaymentTransaction::create([
            'provider' => 'monetbil',
            'order_id' => $this->order->id,
            'payment_ref' => $paymentRef,
            'amount' => 10000,
            'currency' => 'XAF',
            'status' => 'pending',
        ]);

        // Calculer une signature valide pour les tests
        $params = [
            'payment_ref' => $paymentRef,
            'status' => 'success',
            'transaction_id' => 'TXN-123',
            'amount' => '10000',
        ];
        ksort($params);
        $values = array_values($params);
        $stringToHash = 'test_service_secret' . implode('', $values);
        $signature = md5($stringToHash);
        $params['sign'] = $signature;

        // Notification de succès
        $response = $this->postJson('/payment/monetbil/notify', $params);

        // Debug: afficher la réponse en cas d'erreur
        if ($response->status() !== 200) {
            dump('Response status: ' . $response->status());
            dump('Response content: ' . $response->getContent());
        }

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Vérifier que la transaction est marquée comme success
        $transaction->refresh();
        $this->assertEquals('success', $transaction->status);
        $this->assertEquals('TXN-123', $transaction->transaction_id);
        $this->assertNotNull($transaction->notified_at);

        // Vérifier que la commande est marquée comme payée
        $this->order->refresh();
        $this->assertEquals('paid', $this->order->payment_status);

        // Vérifier qu'un Payment a été créé
        $payment = $this->order->payments()->where('provider', 'monetbil')->first();
        $this->assertNotNull($payment);
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals(10000, $payment->amount);
    }

    #[Test]
    public function test_notify_is_idempotent(): void
    {
        $paymentRef = 'ORDER-12345';
        
        // Créer une transaction déjà en succès
        $transaction = PaymentTransaction::create([
            'provider' => 'monetbil',
            'order_id' => $this->order->id,
            'payment_ref' => $paymentRef,
            'amount' => 10000,
            'currency' => 'XAF',
            'status' => 'success',
            'transaction_id' => 'TXN-123',
            'notified_at' => now()->subMinutes(5),
        ]);

        // Marquer la commande comme payée
        $this->order->update(['payment_status' => 'paid']);

        // Créer un Payment initial pour simuler un traitement déjà effectué
        $this->order->payments()->create([
            'provider' => 'monetbil',
            'status' => 'paid',
            'amount' => 10000,
            'currency' => 'XAF',
            'external_reference' => 'TXN-123',
        ]);

        // Compter les Payments existants
        $initialPaymentCount = $this->order->payments()->count();

        // Calculer une signature valide
        $params = [
            'payment_ref' => $paymentRef,
            'status' => 'success',
            'transaction_id' => 'TXN-123',
            'amount' => '10000',
        ];
        ksort($params);
        $values = array_values($params);
        $stringToHash = 'test_service_secret' . implode('', $values);
        $signature = md5($stringToHash);
        $params['sign'] = $signature;

        // Premier appel de notification
        $response1 = $this->postJson('/payment/monetbil/notify', $params);
        $response1->assertStatus(200);

        // Deuxième appel de notification (idempotence)
        $response2 = $this->postJson('/payment/monetbil/notify', $params);
        $response2->assertStatus(200);

        // Vérifier qu'aucun nouveau Payment n'a été créé
        $this->order->refresh();
        $finalPaymentCount = $this->order->payments()->count();
        $this->assertEquals($initialPaymentCount, $finalPaymentCount);

        // Vérifier que la transaction n'a pas été modifiée
        $transaction->refresh();
        $this->assertEquals('success', $transaction->status);
        $this->assertEquals('TXN-123', $transaction->transaction_id);
    }

    #[Test]
    public function test_start_creates_payment_transaction_and_redirects(): void
    {
        // Mock de la réponse Monetbil
        Http::fake([
            'api.monetbil.com/*' => Http::response([
                'success' => true,
                'payment_url' => 'https://widget.monetbil.com/pay/test',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('payment.monetbil.start', ['order' => $this->order->id]));

        // Vérifier la redirection
        $response->assertRedirect('https://widget.monetbil.com/pay/test');

        // Vérifier que la transaction a été créée
        $transaction = PaymentTransaction::where('order_id', $this->order->id)
            ->where('payment_ref', $this->order->order_number)
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals(10000, $transaction->amount);
    }
}

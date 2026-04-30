<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\CardPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsAccounting;

class PaymentTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAccounting;

    protected User $user;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccounting();
        
        $this->user = User::factory()->create(['role' => 'client']);
        $this->actingAs($this->user);
        
        $product = Product::factory()->create(['price' => 10000, 'stock' => 10]);
        
        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 10000,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }
    #[Test]
    public function user_can_initiate_card_payment(): void
    {
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'provider' => 'stripe',
            'channel' => 'card',
            'status' => 'initiated',
            'metadata' => ['session_url' => 'https://checkout.stripe.com/c/pay_test_123'],
        ]);

        $this->mock(CardPaymentService::class, function ($mock) use ($payment) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->withArgs(fn (Order $order) => $order->is($this->order))
                ->andReturn($payment);
        });

        $response = $this->post(route('checkout.card.pay'), [
            'order_id' => $this->order->id,
        ]);

        $response->assertRedirect('https://checkout.stripe.com/c/pay_test_123');
        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'channel' => 'card',
            'provider' => 'stripe',
            'status' => 'initiated',
        ]);
    }
    #[Test]
    public function payment_requires_authenticated_user(): void
    {
        auth()->logout();
        
        $response = $this->post(route('checkout.card.pay'), [
            'order_id' => $this->order->id,
        ]);

        $response->assertRedirect(route('login'));
    }
    #[Test]
    public function user_cannot_pay_for_another_users_order(): void
    {
        $otherUser = User::factory()->create(['role' => 'client']);
        $otherOrder = Order::factory()->create([
            'user_id' => $otherUser->id,
            'total_amount' => 5000,
        ]);

        $this->actingAs($this->user);
        
        $response = $this->post(route('checkout.card.pay'), [
            'order_id' => $otherOrder->id,
        ]);

        $response->assertStatus(302);
    }
    #[Test]
    public function payment_cannot_be_initiated_for_already_paid_order(): void
    {
        $this->order->update(['payment_status' => 'paid']);
        
        $response = $this->post(route('checkout.card.pay'), [
            'order_id' => $this->order->id,
        ]);

        $response->assertRedirect(route('checkout.card.success', $this->order));
    }
    #[Test]
    public function webhook_verifies_stripe_signature(): void
    {
        Payment::factory()->create([
            'order_id' => $this->order->id,
            'provider' => 'stripe',
            'status' => 'initiated',
        ]);

        $this->app['config']->set('app.env', 'production');
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $payload = json_encode([
            'id' => 'evt_test_signature_invalid',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1234567890',
                    'payment_intent' => 'pi_test_1234567890',
                ],
            ],
        ]);

        $response = $this->call('POST', route('payment.card.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Missing signature']);
    }
}

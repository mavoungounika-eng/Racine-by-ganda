<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Payments\CardPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        
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
        $response = $this->post(route('checkout.card.pay'), [
            'order_id' => $this->order->id,
        ]);

        $response->assertStatus(302); // Redirection vers Stripe
        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'channel' => 'online',
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

        $response->assertStatus(403);
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
        $payment = Payment::factory()->create([
            'order_id' => $this->order->id,
            'provider' => 'stripe',
            'status' => 'initiated',
        ]);

        // Test avec signature invalide
        $response = $this->post(route('payment.card.webhook'), [], [
            'Stripe-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(401);
    }
}


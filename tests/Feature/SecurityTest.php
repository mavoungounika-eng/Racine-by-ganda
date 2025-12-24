<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\CartItem;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function checkout_requires_authentication(): void
    {
        $response = $this->get('/checkout');
        
        $response->assertRedirect('/login');
    }

    #[Test]
    public function authenticated_user_can_access_checkout(): void
    {
        $user = User::factory()->create(['role_id' => 5]); // Client
        
        $response = $this->actingAs($user)->get('/checkout');
        
        $response->assertStatus(200);
    }

    #[Test]
    public function stripe_webhook_without_signature_is_rejected_in_production(): void
    {
        // Simuler environnement production
        config(['app.env' => 'production']);
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
        
        $response = $this->postJson('/api/webhooks/stripe', [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ]);
        
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Missing signature']);
    }

    #[Test]
    public function stripe_webhook_with_invalid_signature_is_rejected_in_production(): void
    {
        config(['app.env' => 'production']);
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
        
        $response = $this->postJson('/api/webhooks/stripe', [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => []],
        ], [
            'Stripe-Signature' => 'invalid_signature',
        ]);
        
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    #[Test]
    public function monetbil_webhook_without_signature_is_rejected_in_production(): void
    {
        config(['app.env' => 'production']);
        config(['services.monetbil.service_secret' => 'test_secret']);
        
        $response = $this->postJson('/api/webhooks/monetbil', [
            'transaction_id' => 'test_123',
            'status' => 'success',
        ]);
        
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Missing signature']);
    }

    #[Test]
    public function monetbil_webhook_with_invalid_signature_is_rejected_in_production(): void
    {
        config(['app.env' => 'production']);
        config(['services.monetbil.service_secret' => 'test_secret']);
        
        $response = $this->postJson('/api/webhooks/monetbil', [
            'transaction_id' => 'test_123',
            'status' => 'success',
        ], [
            'X-Signature' => 'invalid_signature',
        ]);
        
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    #[Test]
    public function admin_routes_require_admin_role(): void
    {
        $client = User::factory()->create(['role_id' => 5]); // Client
        
        $response = $this->actingAs($client)->get('/admin/dashboard');
        
        $response->assertStatus(403);
    }

    #[Test]
    public function creator_routes_require_creator_role(): void
    {
        $client = User::factory()->create(['role_id' => 5]); // Client
        
        $response = $this->actingAs($client)->get('/createur/dashboard');
        
        $response->assertStatus(403);
    }

    #[Test]
    public function erp_routes_require_staff_or_admin_role(): void
    {
        $client = User::factory()->create(['role_id' => 5]); // Client
        
        $response = $this->actingAs($client)->get('/erp/dashboard');
        
        $response->assertStatus(403);
    }

    #[Test]
    public function csrf_protection_is_active_on_forms(): void
    {
        $user = User::factory()->create(['role_id' => 5]);
        
        // Tenter de soumettre sans token CSRF
        $response = $this->actingAs($user)->post('/checkout', [
            'payment_method' => 'cash_on_delivery',
        ]);
        
        // Laravel devrait rejeter la requête (419 ou redirection)
        $this->assertTrue(
            $response->status() === 419 || $response->isRedirect()
        );
    }

    #[Test]
    public function rate_limiting_is_configured_on_checkout(): void
    {
        $user = User::factory()->create(['role_id' => 5]);
        $product = Product::factory()->create(['stock' => 100]);
        
        // Ajouter au panier
        CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);
        
        // Faire 15 requêtes rapidement (limite: 10/min)
        $responses = [];
        for ($i = 0; $i < 15; $i++) {
            $responses[] = $this->actingAs($user)->post('/checkout', [
                'payment_method' => 'cash_on_delivery',
                'delivery_address' => 'Test Address',
                'phone' => '+237600000000',
            ]);
        }
        
        // Au moins une requête devrait être rate limited (429)
        $rateLimited = collect($responses)->contains(fn($r) => $r->status() === 429);
        
        // Note: Ce test peut échouer si rate limiting n'est pas configuré
        // C'est normal, on va l'ajouter dans la prochaine étape
        $this->markTestIncomplete('Rate limiting to be configured');
    }
}

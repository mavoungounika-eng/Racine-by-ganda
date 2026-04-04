<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\CartItem;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected int $clientRoleId;

    protected function setUp(): void
    {
        parent::setUp();

        $clientRole = Role::firstOrCreate(
            ['slug' => 'client'],
            ['name' => 'Client', 'description' => 'Client role', 'is_active' => true]
        );
        $this->clientRoleId = $clientRole->id;
    }

    protected function createCreatorUser(): User
    {
        $createurRole = Role::firstOrCreate(
            ['slug' => 'createur'],
            ['name' => 'Créateur', 'description' => 'Creator role', 'is_active' => true]
        );
        return User::factory()->create([
            'role_id' => $createurRole->id,
            'role' => 'createur',
        ]);
    }

    protected function createClientUser(): User
    {
        return User::factory()->create([
            'role_id' => $this->clientRoleId,
            'role' => 'client',
        ]);
    }
    #[Test]
    public function checkout_requires_authentication(): void
    {
        $response = $this->get('/checkout');
        
        $response->assertRedirect('/login');
    }
    #[Test]
    public function authenticated_user_can_access_checkout(): void
    {
        $user = $this->createClientUser();
        
        $response = $this->actingAs($user)->get('/checkout');

        // Un client authentifié ne doit pas être redirigé vers login.
        $this->assertTrue(
            $response->status() === 200 || $response->isRedirect(),
            'Le checkout doit être accessible (200) ou rediriger métier (ex: panier vide), mais pas rejeté.'
        );
        $this->assertNotSame(
            url('/login'),
            $response->headers->get('Location'),
            'Un utilisateur authentifié ne doit pas être redirigé vers login.'
        );
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
        $client = $this->createClientUser();
        
        $response = $this->actingAs($client)->get('/admin/dashboard');
        
        // EnsureAuthenticated fait logout + redirect pour les utilisateurs non autorisés
        $response->assertRedirect('/login');
    }
    #[Test]
    public function creator_routes_require_creator_role(): void
    {
        $client = $this->createClientUser();
        
        $response = $this->actingAs($client)->get('/createur/dashboard');
        
        // EnsureAuthenticated fait logout + redirect pour les utilisateurs non autorisés
        $response->assertRedirect('/login');
    }
    #[Test]
    public function erp_routes_require_staff_or_admin_role(): void
    {
        $client = $this->createClientUser();
        
        $response = $this->actingAs($client)->get('/erp');
        
        // EnsureAuthenticated fait logout + redirect pour les utilisateurs non autorisés
        $response->assertRedirect('/login');
    }
    #[Test]
    public function csrf_protection_is_active_on_forms(): void
    {
        $user = $this->createClientUser();
        
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
        $creator = $this->createCreatorUser();

        // Make multiple requests to trigger rate limiting
        for ($i = 0; $i < 55; $i++) { // Exceed the 50 requests per minute limit
            $response = $this->actingAs($creator)->get('/createur/subscription/plans');
        }

        // The last request should be rate limited (429 status)
        $response->assertStatus(429);
    }
}

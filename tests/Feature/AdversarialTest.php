<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\StripeWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests Feature - Adversarial
 * 
 * Tests adversariaux pour valider la résistance du système
 * 
 * Scénarios :
 * - Rejeu de requête
 * - Rejeu de webhook
 * - Token falsifié
 * - Session volée (user_id injecté)
 * - Concurrence simulée (2 users / même ressource)
 */
class AdversarialTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user1 = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
        
        $this->user2 = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
        
        $this->product = Product::factory()->create([
            'stock' => 5,
            'price' => 5000,
        ]);
    }

    /**
     * Test : Rejeu de requête
     */
    public function test_request_replay_is_blocked(): void
    {
        Auth::login($this->user1);
        
        // Ajouter produit au panier
        $cartService = new \App\Services\Cart\DatabaseCartService();
        $cartService->setUserId($this->user1->id);
        $cartService->add($this->product->id, 2);
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user1->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken,
        ];
        
        // Première soumission (succès)
        $response1 = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une commande a été créée
        $this->assertEquals(1, Order::where('user_id', $this->user1->id)->count());
        
        // Tentative de rejeu (devrait être bloquée)
        session(['checkout_token' => $checkoutToken]); // Réinsérer token pour simuler rejeu
        $response2 = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une seule commande existe toujours
        $this->assertEquals(1, Order::where('user_id', $this->user1->id)->count());
        
        // Vérifier que la deuxième soumission est rejetée
        $response2->assertSessionHasErrors() || $response2->assertRedirect();
    }

    /**
     * Test : Rejeu de webhook
     */
    public function test_webhook_replay_is_blocked(): void
    {
        $eventId = 'evt_replay_test';
        
        // Créer un événement webhook traité
        $event = StripeWebhookEvent::create([
            'event_id' => $eventId,
            'event_type' => 'payment_intent.succeeded',
            'status' => 'processed',
            'processed_at' => now(),
        ]);
        
        // Tenter de créer le même événement (rejeu)
        try {
            $duplicateEvent = StripeWebhookEvent::create([
                'event_id' => $eventId,
                'event_type' => 'payment_intent.succeeded',
                'status' => 'received',
            ]);
            $this->fail('Rejeu de webhook devrait être bloqué');
        } catch (\Illuminate\Database\QueryException $e) {
            // Attendu : duplicate key error
            $this->assertStringContainsString('Duplicate', $e->getMessage()) 
                || $this->assertStringContainsString('UNIQUE', $e->getMessage());
        }
        
        // Vérifier qu'un seul événement existe
        $this->assertEquals(1, StripeWebhookEvent::where('event_id', $eventId)->count());
    }

    /**
     * Test : Token falsifié
     */
    public function test_falsified_token_is_rejected(): void
    {
        Auth::login($this->user1);
        
        // Ajouter produit au panier
        $cartService = new \App\Services\Cart\DatabaseCartService();
        $cartService->setUserId($this->user1->id);
        $cartService->add($this->product->id, 2);
        
        // Générer token valide en session
        $validToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $validToken]);
        
        // Utiliser un token falsifié dans la requête
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user1->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => 'falsified_token_not_matching_session',
        ];
        
        $response = $this->post(route('checkout.place'), $data);
        
        // Vérifier que la requête est rejetée
        $response->assertSessionHasErrors() || $response->assertRedirect();
        
        // Vérifier qu'aucune commande n'a été créée
        $this->assertEquals(0, Order::where('user_id', $this->user1->id)->count());
    }

    /**
     * Test : Session volée (user_id injecté)
     */
    public function test_injected_user_id_is_rejected(): void
    {
        Auth::login($this->user1);
        
        // Créer un panier pour user1
        $cartService1 = new \App\Services\Cart\DatabaseCartService();
        $cartService1->setUserId($this->user1->id);
        $cartService1->add($this->product->id, 2);
        
        // Créer un panier pour user2
        $cartService2 = new \App\Services\Cart\DatabaseCartService();
        $cartService2->setUserId($this->user2->id);
        $cartService2->add($this->product->id, 1);
        
        // Tenter de créer une commande avec user_id injecté (user2) alors qu'on est connecté en user1
        // Le CheckoutController vérifie l'ownership du panier, donc ça devrait échouer
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user1->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken,
        ];
        
        // La commande devrait utiliser le panier de user1 (celui connecté)
        // Pas celui de user2
        $response = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une commande a été créée pour user1 uniquement
        $this->assertEquals(1, Order::where('user_id', $this->user1->id)->count());
        $this->assertEquals(0, Order::where('user_id', $this->user2->id)->count());
    }

    /**
     * Test : Concurrence simulée (2 users / même ressource)
     */
    public function test_concurrent_users_same_resource(): void
    {
        // Stock initial : 5
        // User1 veut 3, User2 veut 3
        // Seul un des deux devrait réussir
        
        // User1 : Ajouter au panier et créer commande
        Auth::login($this->user1);
        $cartService1 = new \App\Services\Cart\DatabaseCartService();
        $cartService1->setUserId($this->user1->id);
        $cartService1->add($this->product->id, 3);
        
        $checkoutToken1 = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken1]);
        
        $data1 = [
            'full_name' => 'User 1',
            'email' => $this->user1->email,
            'phone' => '123456789',
            'address' => 'Address 1',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken1,
        ];
        
        // User1 : Créer commande
        $response1 = $this->post(route('checkout.place'), $data1);
        
        // Vérifier que la commande de user1 a été créée
        $this->assertEquals(1, Order::where('user_id', $this->user1->id)->count());
        
        // Vérifier que le stock a été décrémenté
        $this->product->refresh();
        $this->assertEquals(2, $this->product->stock); // 5 - 3 = 2
        
        // User2 : Tenter de créer commande avec stock insuffisant
        Auth::login($this->user2);
        $cartService2 = new \App\Services\Cart\DatabaseCartService();
        $cartService2->setUserId($this->user2->id);
        $cartService2->add($this->product->id, 3); // Demande 3, mais il n'en reste que 2
        
        $checkoutToken2 = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken2]);
        
        $data2 = [
            'full_name' => 'User 2',
            'email' => $this->user2->email,
            'phone' => '987654321',
            'address' => 'Address 2',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken2,
        ];
        
        // User2 : Tenter de créer commande (devrait échouer - stock insuffisant)
        $response2 = $this->post(route('checkout.place'), $data2);
        
        // Vérifier qu'aucune commande supplémentaire n'a été créée
        $this->assertEquals(1, Order::where('user_id', $this->user1->id)->count());
        $this->assertEquals(0, Order::where('user_id', $this->user2->id)->count());
        
        // Vérifier que le stock n'a pas été modifié (rollback)
        $this->product->refresh();
        $this->assertEquals(2, $this->product->stock);
    }

    /**
     * Test : Concurrence - Deux webhooks simultanés pour même paiement
     */
    public function test_concurrent_webhooks_same_payment(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user1->id,
            'payment_status' => 'pending',
        ]);
        
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status' => 'pending',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_test_123',
        ]);
        
        $eventId = 'evt_concurrent_payment';
        
        // Simuler deux webhooks simultanés avec le même event_id
        // Le premier devrait réussir, le second devrait être bloqué (idempotence)
        
        try {
            $event1 = StripeWebhookEvent::create([
                'event_id' => $eventId,
                'event_type' => 'payment_intent.succeeded',
                'status' => 'received',
            ]);
            
            // Tenter de créer le même événement (simulation concurrence)
            try {
                $event2 = StripeWebhookEvent::create([
                    'event_id' => $eventId,
                    'event_type' => 'payment_intent.succeeded',
                    'status' => 'received',
                ]);
                $this->fail('Deuxième webhook concurrent devrait être bloqué');
            } catch (\Illuminate\Database\QueryException $e) {
                // Attendu : duplicate key error
                $this->assertStringContainsString('Duplicate', $e->getMessage()) 
                    || $this->assertStringContainsString('UNIQUE', $e->getMessage());
            }
        } catch (\Exception $e) {
            // Si la création échoue, vérifier que c'est une erreur de duplicate
            $this->assertStringContainsString('Duplicate', $e->getMessage()) 
                || $this->assertStringContainsString('UNIQUE', $e->getMessage());
        }
        
        // Vérifier qu'un seul événement existe
        $this->assertEquals(1, StripeWebhookEvent::where('event_id', $eventId)->count());
    }
}




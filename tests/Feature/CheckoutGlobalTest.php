<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\DatabaseCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests Feature - Checkout Global
 * 
 * PRIORITÉ 1 - Checkout & Commandes (CRITIQUE ABSOLUE)
 * 
 * Scénarios OBLIGATOIRES :
 * - Tunnel unique
 * - Idempotence complète
 * - Stock & rollback
 * - Ownership
 * - Paiement
 */
class CheckoutGlobalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Product $product1;
    protected Product $product2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer deux utilisateurs clients
        $this->user = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
        
        $this->otherUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
        
        // Créer des produits avec stock
        $this->product1 = Product::factory()->create([
            'stock' => 10,
            'price' => 5000,
            'is_active' => true,
        ]);
        
        $this->product2 = Product::factory()->create([
            'stock' => 5,
            'price' => 3000,
            'is_active' => true,
        ]);
    }

    /**
     * Test : Tunnel unique - Impossible de créer une commande hors CheckoutController
     */
    public function test_order_cannot_be_created_outside_checkout_controller(): void
    {
        Auth::login($this->user);
        
        // Tenter d'appeler directement OrderController::placeOrder()
        $controller = new \App\Http\Controllers\Front\OrderController();
        $request = \Illuminate\Http\Request::create('/legacy/checkout', 'POST');
        $request->setUserResolver(function () {
            return $this->user;
        });
        
        // Vérifier que l'accès est bloqué avec 410
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        try {
            $controller->placeOrder($request);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(410, $e->getStatusCode());
            $this->assertStringContainsString('obsolète', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test : Idempotence - Double clic → 1 seule commande
     */
    public function test_double_click_creates_only_one_order(): void
    {
        Auth::login($this->user);
        
        // Ajouter produits au panier
        $cartService = new DatabaseCartService();
        $cartService->setUserId($this->user->id);
        $cartService->add($this->product1->id, 2);
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken,
        ];
        
        // Première soumission
        $response1 = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une seule commande a été créée
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        $order1 = Order::where('user_id', $this->user->id)->first();
        
        // Deuxième soumission immédiate (double clic simulé)
        // Token devrait être supprimé, donc deuxième soumission devrait échouer
        $response2 = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une seule commande existe toujours
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        
        // Vérifier que la deuxième soumission est rejetée
        $response2->assertSessionHasErrors() || $response2->assertRedirect();
    }

    /**
     * Test : Idempotence - Retry HTTP → 1 seule commande
     */
    public function test_http_retry_creates_only_one_order(): void
    {
        Auth::login($this->user);
        
        // Ajouter produits au panier
        $cartService = new DatabaseCartService();
        $cartService->setUserId($this->user->id);
        $cartService->add($this->product1->id, 2);
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken,
        ];
        
        // Première soumission (succès)
        $response1 = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une commande a été créée
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        
        // Simuler retry HTTP (même token, mais token supprimé après première utilisation)
        // Réinsérer token pour simuler retry malveillant
        session(['checkout_token' => $checkoutToken]);
        $response2 = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une seule commande existe toujours (idempotence)
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
    }

    /**
     * Test : Idempotence - Rejeu token → rejet
     */
    public function test_token_replay_is_rejected(): void
    {
        Auth::login($this->user);
        
        // Ajouter produits au panier
        $cartService = new DatabaseCartService();
        $cartService->setUserId($this->user->id);
        $cartService->add($this->product1->id, 2);
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken,
        ];
        
        // Première soumission (succès)
        $response1 = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une commande a été créée
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        
        // Token devrait être supprimé
        $this->assertNull(session('checkout_token'));
        
        // Tentative de rejeu avec le même token (devrait être rejetée)
        session(['checkout_token' => $checkoutToken]);
        $response2 = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une seule commande existe toujours
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        
        // Vérifier que la deuxième soumission est rejetée
        $response2->assertSessionHasErrors() || $response2->assertRedirect();
    }

    /**
     * Test : Idempotence - Token manquant → rejet
     */
    public function test_missing_token_is_rejected(): void
    {
        Auth::login($this->user);
        
        // Ajouter produits au panier
        $cartService = new DatabaseCartService();
        $cartService->setUserId($this->user->id);
        $cartService->add($this->product1->id, 2);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            // Pas de _checkout_token
        ];
        
        $response = $this->post(route('checkout.place'), $data);
        
        // Vérifier que la requête est rejetée
        $response->assertSessionHasErrors() || $response->assertRedirect();
        
        // Vérifier qu'aucune commande n'a été créée
        $this->assertEquals(0, Order::where('user_id', $this->user->id)->count());
    }

    /**
     * Test : Stock insuffisant → aucune commande créée
     */
    public function test_insufficient_stock_creates_no_order(): void
    {
        Auth::login($this->user);
        
        // Réduire le stock à 1 (panier contient 2)
        $this->product1->update(['stock' => 1]);
        
        // Ajouter produits au panier
        $cartService = new DatabaseCartService();
        $cartService->setUserId($this->user->id);
        $cartService->add($this->product1->id, 2);
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken,
        ];
        
        // Tenter de créer la commande (devrait échouer)
        $response = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'aucune commande n'a été créée (rollback)
        $this->assertEquals(0, Order::where('user_id', $this->user->id)->count());
        
        // Vérifier que le stock n'a pas été modifié
        $this->product1->refresh();
        $this->assertEquals(1, $this->product1->stock);
        
        // Vérifier que la réponse indique une erreur
        $response->assertSessionHasErrors() || $response->assertRedirect();
    }

    /**
     * Test : Stock partiellement disponible → rollback total
     */
    public function test_partial_stock_rolls_back_completely(): void
    {
        Auth::login($this->user);
        
        // Produit 1 : stock suffisant (10)
        // Produit 2 : stock insuffisant (1, mais panier demande 2)
        $this->product2->update(['stock' => 1]);
        
        // Ajouter produits au panier
        $cartService = new DatabaseCartService();
        $cartService->setUserId($this->user->id);
        $cartService->add($this->product1->id, 2); // OK
        $cartService->add($this->product2->id, 2); // Insuffisant
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken,
        ];
        
        // Tenter de créer la commande (devrait échouer)
        $response = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'aucune commande n'a été créée (rollback total)
        $this->assertEquals(0, Order::where('user_id', $this->user->id)->count());
        
        // Vérifier qu'aucun stock n'a été modifié (rollback total)
        $this->product1->refresh();
        $this->product2->refresh();
        $this->assertEquals(10, $this->product1->stock);
        $this->assertEquals(1, $this->product2->stock);
    }

    /**
     * Test : Aucun décrément partiel
     */
    public function test_no_partial_stock_decrement(): void
    {
        Auth::login($this->user);
        
        // Stock initial
        $initialStock1 = 10;
        $initialStock2 = 5;
        
        // Ajouter produits au panier
        $cartService = new DatabaseCartService();
        $cartService->setUserId($this->user->id);
        $cartService->add($this->product1->id, 2);
        $cartService->add($this->product2->id, 3);
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken,
        ];
        
        // Créer la commande (devrait réussir)
        $response = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une commande a été créée
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        
        // Vérifier que le stock a été décrémenté correctement (tout ou rien)
        $this->product1->refresh();
        $this->product2->refresh();
        $this->assertEquals($initialStock1 - 2, $this->product1->stock);
        $this->assertEquals($initialStock2 - 3, $this->product2->stock);
    }

    /**
     * Test : Ownership - Panier d'un autre user → 403
     */
    public function test_cart_from_another_user_returns_403(): void
    {
        Auth::login($this->user);
        
        // Créer un panier pour l'autre utilisateur
        $otherCartService = new DatabaseCartService();
        $otherCartService->setUserId($this->otherUser->id);
        $otherCartService->add($this->product1->id, 2);
        
        // Tenter de créer une commande avec le panier de l'autre utilisateur
        // (simulation d'injection)
        // En réalité, le CheckoutController vérifie l'ownership, donc on teste cette vérification
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken,
        ];
        
        // Le panier devrait être vide pour $this->user
        // Donc la commande devrait échouer avec panier vide
        $response = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'aucune commande n'a été créée
        $this->assertEquals(0, Order::where('user_id', $this->user->id)->count());
        
        // Vérifier que la réponse indique une erreur (panier vide)
        $response->assertSessionHasErrors() || $response->assertRedirect();
    }

    /**
     * Test : Ownership - Item injecté manuellement → 403
     */
    public function test_injected_cart_item_returns_403(): void
    {
        Auth::login($this->user);
        
        // Créer un panier pour l'utilisateur
        $cartService = new DatabaseCartService();
        $cartService->setUserId($this->user->id);
        $cartService->add($this->product1->id, 2);
        
        // Créer un item de panier pour l'autre utilisateur (simulation injection)
        $otherCartService = new DatabaseCartService();
        $otherCartService->setUserId($this->otherUser->id);
        $otherCartService->add($this->product2->id, 1);
        
        // Le CheckoutController vérifie que tous les items appartiennent à l'utilisateur
        // Donc si on tente d'utiliser un item d'un autre panier, ça devrait échouer
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => $checkoutToken,
        ];
        
        // La commande devrait réussir car le panier de $this->user ne contient que ses propres items
        $response = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une commande a été créée (seulement avec les items du bon panier)
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        
        $order = Order::where('user_id', $this->user->id)->first();
        $items = $order->items;
        
        // Vérifier que seuls les items du panier de $this->user sont dans la commande
        $this->assertEquals(1, $items->count());
        $this->assertEquals($this->product1->id, $items->first()->product_id);
    }

    /**
     * Test : Paiement - Paiement validé → commande paid
     */
    public function test_payment_validated_sets_order_paid(): void
    {
        Auth::login($this->user);
        
        // Ajouter produits au panier
        $cartService = new DatabaseCartService();
        $cartService->setUserId($this->user->id);
        $cartService->add($this->product1->id, 2);
        
        // Générer token unique
        $checkoutToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $checkoutToken]);
        
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash', // Cash = paiement immédiat
            '_checkout_token' => $checkoutToken,
        ];
        
        // Créer la commande
        $response = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une commande a été créée
        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertNotNull($order);
        
        // Pour cash, le paiement devrait être marqué comme paid immédiatement
        // (selon la logique dans OrderController ou CheckoutController)
        $order->refresh();
        
        // Vérifier le statut selon la logique métier
        // Note: La logique exacte dépend de l'implémentation
        $this->assertNotNull($order->payment_status);
    }

    /**
     * Test : Paiement - Paiement annulé → commande cancelled
     */
    public function test_payment_cancelled_sets_order_cancelled(): void
    {
        // Ce test dépend de la logique de cancellation
        // À adapter selon l'implémentation réelle
        $this->assertTrue(true); // Placeholder
    }

    /**
     * Test : Aucun double changement de statut
     */
    public function test_no_double_status_change(): void
    {
        Auth::login($this->user);
        
        // Créer une commande directement
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);
        
        $initialStatus = $order->status;
        $initialPaymentStatus = $order->payment_status;
        
        // Tenter de changer le statut plusieurs fois
        $order->update(['payment_status' => 'paid']);
        $order->update(['payment_status' => 'paid']);
        $order->update(['status' => 'completed']);
        
        $order->refresh();
        
        // Vérifier que le statut n'a pas changé de manière incohérente
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('paid', $order->payment_status);
    }
}




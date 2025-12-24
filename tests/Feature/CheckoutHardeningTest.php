<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\DatabaseCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests Feature - Checkout Hardening
 * 
 * Module 3 - Final Hardening
 * 
 * Tests obligatoires :
 * - Double soumission checkout → 1 commande
 * - Token manquant → rejet
 * - Token invalide → rejet
 * - Token réutilisé → rejet
 * - Legacy OrderController → rejet
 * - Stock insuffisant en course condition → rollback
 */
class CheckoutHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur client
        $this->user = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
        
        // Créer un produit avec stock
        $this->product = Product::factory()->create([
            'stock' => 10,
            'price' => 5000,
        ]);
        
        // Ajouter produit au panier
        $cartService = new DatabaseCartService();
        $cartService->setUserId($this->user->id);
        $cartService->add($this->product->id, 2);
    }

    /**
     * Test : Double soumission checkout → 1 seule commande
     */
    public function test_double_submission_checkout_creates_only_one_order(): void
    {
        Auth::login($this->user);
        
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
        
        // Vérifier que la commande a été créée
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        $order1 = Order::where('user_id', $this->user->id)->first();
        
        // Deuxième soumission avec le même token (devrait être bloquée)
        $response2 = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une seule commande existe toujours
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        
        // Vérifier que la deuxième soumission retourne une erreur
        $response2->assertSessionHasErrors() || $response2->assertRedirect();
    }

    /**
     * Test : Token manquant → rejet
     */
    public function test_checkout_without_token_is_rejected(): void
    {
        Auth::login($this->user);
        
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
     * Test : Token invalide → rejet
     */
    public function test_checkout_with_invalid_token_is_rejected(): void
    {
        Auth::login($this->user);
        
        // Générer token en session
        $sessionToken = \Illuminate\Support\Str::random(32);
        session(['checkout_token' => $sessionToken]);
        
        // Utiliser un token différent dans la requête
        $data = [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '123456789',
            'address' => 'Test Address',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash',
            '_checkout_token' => 'invalid_token_different_from_session',
        ];
        
        $response = $this->post(route('checkout.place'), $data);
        
        // Vérifier que la requête est rejetée
        $response->assertSessionHasErrors() || $response->assertRedirect();
        
        // Vérifier qu'aucune commande n'a été créée
        $this->assertEquals(0, Order::where('user_id', $this->user->id)->count());
    }

    /**
     * Test : Token réutilisé → rejet
     */
    public function test_checkout_with_reused_token_is_rejected(): void
    {
        Auth::login($this->user);
        
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
        
        // Première soumission (devrait réussir)
        $response1 = $this->post(route('checkout.place'), $data);
        
        // Vérifier que la commande a été créée
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        
        // Token devrait être supprimé après utilisation
        $this->assertNull(session('checkout_token'));
        
        // Deuxième soumission avec le même token (devrait être rejetée)
        session(['checkout_token' => $checkoutToken]); // Réinsérer manuellement pour tester
        $response2 = $this->post(route('checkout.place'), $data);
        
        // Vérifier qu'une seule commande existe toujours
        $this->assertEquals(1, Order::where('user_id', $this->user->id)->count());
        
        // Vérifier que la deuxième soumission retourne une erreur
        $response2->assertSessionHasErrors() || $response2->assertRedirect();
    }

    /**
     * Test : Legacy OrderController → rejet (410 Gone)
     */
    public function test_legacy_order_controller_is_blocked(): void
    {
        Auth::login($this->user);
        
        // Tenter d'accéder à OrderController::placeOrder() (même si aucune route n'y pointe)
        // On simule l'appel direct pour tester le guard
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
            throw $e;
        }
    }

    /**
     * Test : Stock insuffisant en course condition → rollback
     */
    public function test_insufficient_stock_during_checkout_rolls_back(): void
    {
        Auth::login($this->user);
        
        // Réduire le stock à 1 (panier contient 2)
        $this->product->update(['stock' => 1]);
        
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
        $this->product->refresh();
        $this->assertEquals(1, $this->product->stock);
        
        // Vérifier que la réponse indique une erreur
        $response->assertSessionHasErrors() || $response->assertRedirect();
    }
}


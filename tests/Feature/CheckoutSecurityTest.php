<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Tests de sécurité pour le tunnel Checkout & Commandes
 * 
 * Ces tests vérifient que :
 * - ZÉRO commande sans authentification
 * - ZÉRO commande sur le panier d'un autre utilisateur
 * - Un SEUL tunnel officiel (CheckoutController)
 */
class CheckoutSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Checkout sans authentification → refus
     */
    public function test_checkout_without_authentication_is_rejected(): void
    {
        // Tenter d'accéder au checkout sans être connecté
        $response = $this->get(route('checkout.index'));

        // Doit rediriger vers login
        $response->assertRedirect(route('login'));
    }

    /**
     * Test : Tentative checkout avec panier d'un autre user → 403
     */
    public function test_checkout_with_another_user_cart_is_rejected(): void
    {
        // Créer deux utilisateurs
        $user1 = User::factory()->create(['role' => 'client']);
        $user2 = User::factory()->create(['role' => 'client']);

        // Créer un produit
        $product = Product::factory()->create(['stock' => 10]);

        // Créer un panier pour user1
        $cart1 = Cart::create(['user_id' => $user1->id]);
        CartItem::create([
            'cart_id' => $cart1->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1000,
        ]);

        // Se connecter en tant que user2
        $this->actingAs($user2);

        // Tenter d'accéder au checkout (user2 ne devrait pas voir le panier de user1)
        // Le DatabaseCartService devrait créer un panier vide pour user2
        $response = $this->get(route('checkout.index'));

        // Doit rediriger vers le panier car vide
        $response->assertRedirect(route('cart.index'));
    }

    /**
     * Test : Création commande valide → OK
     */
    public function test_valid_order_creation_is_successful(): void
    {
        // Créer un utilisateur client
        $user = User::factory()->create(['role' => 'client', 'status' => 'active']);

        // Créer un produit avec stock
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 5000,
        ]);

        // Créer un panier pour l'utilisateur
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 5000,
        ]);

        // Se connecter
        $this->actingAs($user);

        // Créer une commande via checkout
        $response = $this->post(route('checkout.place'), [
            'full_name' => 'Test User',
            'email' => $user->email,
            'phone' => '+242 06 123 45 67',
            'address_line_1' => '123 Test Street',
            'city' => 'Brazzaville',
            'postal_code' => '12345',
            'country' => 'CG',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash_on_delivery',
        ]);

        // Vérifier que la commande est créée
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'customer_email' => $user->email,
            'payment_method' => 'cash_on_delivery',
            'status' => 'pending',
        ]);

        // Vérifier que le panier est vidé
        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
        ]);
    }

    /**
     * Test : Tentative création commande via route legacy OrderController → bloquée
     * 
     * Note : OrderController est déjà marqué comme @deprecated et aucune route ne l'utilise.
     * Ce test vérifie qu'aucune route ne permet d'accéder à OrderController.
     */
    public function test_legacy_order_controller_routes_do_not_exist(): void
    {
        // Vérifier qu'aucune route n'utilise OrderController pour créer des commandes
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        
        $orderControllerRoutes = [];
        foreach ($routes as $route) {
            $action = $route->getAction();
            if (isset($action['controller'])) {
                if (str_contains($action['controller'], 'OrderController') && 
                    str_contains($action['controller'], 'Front')) {
                    $orderControllerRoutes[] = $route->getName();
                }
            }
        }

        // Aucune route ne doit utiliser OrderController (legacy)
        $this->assertEmpty($orderControllerRoutes, 
            'Aucune route ne doit utiliser OrderController (legacy). Utiliser CheckoutController à la place.');
    }

    /**
     * Test : Double soumission checkout → 1 seule commande
     */
    public function test_double_checkout_submission_creates_only_one_order(): void
    {
        // Créer un utilisateur client
        $user = User::factory()->create(['role' => 'client', 'status' => 'active']);

        // Créer un produit avec stock
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 5000,
        ]);

        // Créer un panier pour l'utilisateur
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 5000,
        ]);

        // Se connecter
        $this->actingAs($user);

        $orderData = [
            'full_name' => 'Test User',
            'email' => $user->email,
            'phone' => '+242 06 123 45 67',
            'address_line_1' => '123 Test Street',
            'city' => 'Brazzaville',
            'postal_code' => '12345',
            'country' => 'CG',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash_on_delivery',
        ];

        // Première soumission
        $response1 = $this->post(route('checkout.place'), $orderData);
        
        // Vérifier qu'une commande est créée
        $this->assertDatabaseCount('orders', 1);

        // Deuxième soumission (panier vide maintenant)
        $response2 = $this->post(route('checkout.place'), $orderData);
        
        // Doit rediriger vers le panier car vide
        $response2->assertRedirect(route('cart.index'));

        // Vérifier qu'il n'y a toujours qu'UNE SEULE commande
        $this->assertDatabaseCount('orders', 1);
    }

    /**
     * Test : Vérification que toutes les routes checkout sont sous auth + throttle
     */
    public function test_all_checkout_routes_are_protected(): void
    {
        $checkoutRoutes = [
            'checkout.index',
            'checkout.place',
            'checkout.success',
            'checkout.cancel',
            'api.checkout.verify-stock',
            'api.checkout.validate-email',
            'api.checkout.validate-phone',
            'api.checkout.apply-promo',
        ];

        foreach ($checkoutRoutes as $routeName) {
            $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($routeName);
            
            $this->assertNotNull($route, "Route {$routeName} doit exister");
            
            $middlewares = $route->middleware();
            
            // Vérifier que auth est présent
            $this->assertContains('auth', $middlewares, 
                "Route {$routeName} doit avoir middleware auth");
            
            // Vérifier que throttle est présent (peut être sur le groupe ou la route)
            $hasThrottle = false;
            foreach ($middlewares as $middleware) {
                if (str_contains($middleware, 'throttle')) {
                    $hasThrottle = true;
                    break;
                }
            }
            $this->assertTrue($hasThrottle, 
                "Route {$routeName} doit avoir middleware throttle");
        }
    }
}


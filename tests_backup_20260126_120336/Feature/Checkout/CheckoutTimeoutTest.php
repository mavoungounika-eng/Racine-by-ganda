<?php

namespace Tests\Feature\Checkout;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests de timeout de checkout
 * 
 * Vérifie que les checkouts abandonnés sont correctement gérés:
 * - Commandes expirées après timeout
 * - Stock libéré
 * - Pas de paiements créés
 */
class CheckoutTimeoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test qu'un checkout abandonné expire après le timeout
     */
    public function test_abandoned_checkout_expires_after_timeout(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 100.00,
        ]);

        // Créer une commande en attente de paiement
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
            'created_at' => now()->subMinutes(35), // 35 minutes ago
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        // Simuler le job de cleanup (normalement exécuté par scheduler)
        $this->artisan('orders:cleanup-abandoned');

        // Vérifier que la commande est expirée
        $order->refresh();
        $this->assertEquals('expired', $order->status);
    }

    /**
     * Test que le stock est libéré quand un checkout expire
     */
    public function test_stock_is_released_when_checkout_expires(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 100.00,
        ]);

        $initialStock = $product->stock;

        // Créer une commande qui a décrémenté le stock
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 200.00,
            'created_at' => now()->subMinutes(35),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        // Décrémenter le stock (simuler comportement checkout)
        $product->decrement('stock', 2);
        $this->assertEquals($initialStock - 2, $product->fresh()->stock);

        // Exécuter le cleanup
        $this->artisan('orders:cleanup-abandoned');

        // Vérifier que le stock est restauré
        $product->refresh();
        $this->assertEquals($initialStock, $product->stock);
    }

    /**
     * Test qu'aucun paiement n'est créé pour un checkout expiré
     */
    public function test_no_payment_created_for_expired_checkout(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 100.00,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
            'created_at' => now()->subMinutes(35),
        ]);

        // Exécuter le cleanup
        $this->artisan('orders:cleanup-abandoned');

        // Vérifier qu'aucun paiement n'existe pour cette commande
        $this->assertEquals(0, Payment::where('order_id', $order->id)->count());

        // Vérifier que la commande est expirée
        $order->refresh();
        $this->assertEquals('expired', $order->status);
    }

    /**
     * Test qu'un checkout récent n'est pas expiré
     */
    public function test_recent_checkout_is_not_expired(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 100.00,
        ]);

        // Créer une commande récente (10 minutes)
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
            'created_at' => now()->subMinutes(10),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        // Exécuter le cleanup
        $this->artisan('orders:cleanup-abandoned');

        // Vérifier que la commande reste pending
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    /**
     * Test qu'une commande avec paiement en cours n'est pas expirée
     */
    public function test_checkout_with_pending_payment_is_not_expired(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 100.00,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
            'created_at' => now()->subMinutes(35),
        ]);

        // Créer un paiement en attente
        Payment::create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'currency' => 'XAF',
            'provider' => 'stripe',
            'provider_payment_id' => 'pi_pending',
            'status' => 'pending',
        ]);

        // Exécuter le cleanup
        $this->artisan('orders:cleanup-abandoned');

        // Vérifier que la commande reste pending (paiement en cours)
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    /**
     * Test que les commandes cash-on-delivery ne sont pas expirées
     */
    public function test_cash_on_delivery_orders_are_not_expired(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 100.00,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_method' => 'cash',
            'total' => 100.00,
            'created_at' => now()->subMinutes(35),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        // Exécuter le cleanup
        $this->artisan('orders:cleanup-abandoned');

        // Vérifier que la commande reste pending (cash on delivery)
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    /**
     * Test que le cleanup gère correctement plusieurs commandes
     */
    public function test_cleanup_handles_multiple_orders_correctly(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 100,
            'price' => 100.00,
        ]);

        // Créer 3 commandes expirées
        $expiredOrders = Order::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
            'created_at' => now()->subMinutes(35),
        ]);

        // Créer 2 commandes récentes
        $recentOrders = Order::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
            'created_at' => now()->subMinutes(10),
        ]);

        // Exécuter le cleanup
        $this->artisan('orders:cleanup-abandoned');

        // Vérifier que les commandes expirées sont bien expirées
        foreach ($expiredOrders as $order) {
            $order->refresh();
            $this->assertEquals('expired', $order->status);
        }

        // Vérifier que les commandes récentes restent pending
        foreach ($recentOrders as $order) {
            $order->refresh();
            $this->assertEquals('pending', $order->status);
        }
    }

    /**
     * Test que l'expiration est loggée pour audit
     */
    public function test_expiration_is_logged_for_audit(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 100.00,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100.00,
            'created_at' => now()->subMinutes(35),
        ]);

        // Exécuter le cleanup
        $this->artisan('orders:cleanup-abandoned');

        // Vérifier qu'un log d'audit existe
        // (Adapter selon votre système de logging)
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'old_status' => 'pending',
            'new_status' => 'expired',
            'reason' => 'checkout_timeout',
        ]);
    }
}

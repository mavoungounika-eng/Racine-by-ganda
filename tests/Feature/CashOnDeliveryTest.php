<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Models\FunnelEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\DatabaseCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\ERP\Models\ErpStockMovement;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashOnDeliveryTest extends TestCase
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
            'price' => 10000,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_creates_order_with_cash_on_delivery(): void
    {
        // Se connecter AVANT d'ajouter au panier (le panier est lié à Auth::id())
        $this->actingAs($this->user);

        // Ajouter un produit au panier (utiliser l'objet Product, pas l'ID)
        $cartService = new DatabaseCartService();
        $cartService->add($this->product, 2);

        // Soumettre le formulaire de checkout avec cash_on_delivery
        $response = $this->post(route('checkout.place'), [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Rue Test',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash_on_delivery',
        ]);

        // Vérifier la redirection vers la page de succès
        $response->assertRedirect();
        $targetUrl = $response->getTargetUrl();
        $this->assertStringContainsString('checkout/success', $targetUrl, 'Should redirect to checkout.success');

        // Vérifier qu'une commande a été créée
        $order = Order::where('user_id', $this->user->id)
            ->where('payment_method', 'cash_on_delivery')
            ->first();

        $this->assertNotNull($order);
        $this->assertEquals('cash_on_delivery', $order->payment_method);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(22000, $order->total_amount); // 2 * 10000 + 2000 livraison
    }

    #[Test]
    public function it_decrements_stock_for_cash_on_delivery(): void
    {
        $initialStock = $this->product->stock;
        $quantity = 2;

        // Se connecter AVANT d'ajouter au panier (le panier est lié à Auth::id())
        $this->actingAs($this->user);

        // Ajouter au panier (utiliser l'objet Product, pas l'ID)
        $cartService = new DatabaseCartService();
        $cartService->add($this->product, $quantity);

        // Créer la commande
        $this->post(route('checkout.place'), [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Rue Test',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash_on_delivery',
        ]);

        // Vérifier que le stock a été décrémenté
        $this->product->refresh();
        $this->assertEquals($initialStock - $quantity, $this->product->stock);

        // Vérifier qu'un mouvement de stock a été créé
        $movement = ErpStockMovement::where('reference_type', Order::class)
            ->where('reference_id', Order::where('user_id', $this->user->id)->first()->id)
            ->where('type', 'out')
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals($quantity, $movement->quantity);
    }

    #[Test]
    public function it_clears_cart_after_order_creation(): void
    {
        // Se connecter AVANT d'ajouter au panier (le panier est lié à Auth::id())
        $this->actingAs($this->user);

        // Ajouter au panier (utiliser l'objet Product, pas l'ID)
        $cartService = new DatabaseCartService();
        $cartService->add($this->product, 2);

        // Vérifier que le panier n'est pas vide
        $this->assertFalse($cartService->getItems()->isEmpty());

        // Créer la commande
        $this->post(route('checkout.place'), [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Rue Test',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash_on_delivery',
        ]);

        // Vérifier que le panier est vidé
        $cartService = new DatabaseCartService();
        $this->assertTrue($cartService->getItems()->isEmpty());
    }

    #[Test]
    public function it_logs_funnel_events_for_cash_on_delivery(): void
    {
        // Ne pas fake les events pour que les listeners s'exécutent et créent le FunnelEvent
        // Se connecter AVANT d'ajouter au panier (le panier est lié à Auth::id())
        $this->actingAs($this->user);

        // Ajouter au panier (utiliser l'objet Product, pas l'ID)
        $cartService = new DatabaseCartService();
        $cartService->add($this->product, 2);

        // Créer la commande
        $this->post(route('checkout.place'), [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Rue Test',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash_on_delivery',
        ]);

        // Vérifier qu'un événement funnel a été enregistré (le listener LogFunnelEvent doit l'avoir créé)
        $funnelEvent = FunnelEvent::where('event_type', 'order_placed')
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNotNull($funnelEvent);
        $this->assertEquals('cash_on_delivery', $funnelEvent->metadata['payment_method'] ?? null);
    }

    #[Test]
    public function it_does_not_create_payment_record_for_cash_on_delivery(): void
    {
        // Se connecter AVANT d'ajouter au panier (le panier est lié à Auth::id())
        $this->actingAs($this->user);

        // Ajouter au panier (utiliser l'objet Product, pas l'ID)
        $cartService = new DatabaseCartService();
        $cartService->add($this->product, 2);

        // Créer la commande
        $this->post(route('checkout.place'), [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Rue Test',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash_on_delivery',
        ]);

        $order = Order::where('user_id', $this->user->id)->first();

        // Vérifier qu'aucun enregistrement Payment n'a été créé
        $this->assertEquals(0, $order->payments()->count());
    }

    #[Test]
    public function it_prevents_double_stock_decrement_for_cash_on_delivery(): void
    {
        $initialStock = $this->product->stock;
        $quantity = 2;

        // Se connecter AVANT d'ajouter au panier (le panier est lié à Auth::id())
        $this->actingAs($this->user);

        // Ajouter au panier (utiliser l'objet Product, pas l'ID)
        $cartService = new DatabaseCartService();
        $cartService->add($this->product, $quantity);

        // Créer la commande
        $this->post(route('checkout.place'), [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Rue Test',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'cash_on_delivery',
        ]);

        $order = Order::where('user_id', $this->user->id)->first();

        // Simuler un changement de statut de paiement (ne devrait pas décrémenter à nouveau)
        $order->update(['payment_status' => 'paid']);

        // Vérifier que le stock n'a été décrémenté qu'une seule fois
        $this->product->refresh();
        $this->assertEquals($initialStock - $quantity, $this->product->stock);

        // Vérifier qu'un seul mouvement de stock existe
        $movements = ErpStockMovement::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', 'out')
            ->count();

        $this->assertEquals(1, $movements);
    }
}


<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\DatabaseCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CheckoutCashOnDeliveryDebugTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $this->product = Product::factory()->create([
            'stock' => 10,
            'price' => 10000,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_creates_order_with_cash_on_delivery_and_redirects(): void
    {
        // Se connecter AVANT d'ajouter au panier (le panier est lié à Auth::id())
        $this->actingAs($this->user);

        // Ajouter au panier (utiliser l'objet Product, pas l'ID)
        $cartService = new DatabaseCartService();
        $cartService->add($this->product, 2);

        // Vérifier que le panier n'est pas vide
        $this->assertFalse($cartService->getItems()->isEmpty(), 'Cart should not be empty');

        // Soumettre le formulaire
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

        // Vérifications de la réponse POST
        $response->assertStatus(302); // Redirection
        $response->assertRedirect();
        
        // Vérifier que la redirection pointe vers checkout.success
        $targetUrl = $response->getTargetUrl();
        $this->assertStringContainsString('checkout/success', $targetUrl, 'Should redirect to checkout.success');

        // Vérifier qu'une commande a été créée en base
        $order = Order::where('user_id', $this->user->id)
            ->where('payment_method', 'cash_on_delivery')
            ->orderBy('created_at', 'desc')
            ->first();

        $this->assertNotNull($order, 'Order should be created in database');
        $this->assertEquals('cash_on_delivery', $order->payment_method, 'Payment method should be cash_on_delivery');
        $this->assertEquals('pending', $order->payment_status, 'Payment status should be pending');
        $this->assertEquals('pending', $order->status, 'Order status should be pending');
        $this->assertNotNull($order->id, 'Order should have an ID');

        // Vérifier que le panier est vidé
        $cartService = new DatabaseCartService();
        $this->assertTrue($cartService->getItems()->isEmpty(), 'Cart should be empty after order creation');

        // Suivre la redirection vers la page de succès
        $successResponse = $this->get($targetUrl);
        
        // Vérifications de la page de succès
        $successResponse->assertStatus(200);
        $successResponse->assertSee('Commande confirmée', false, 'Success page should show confirmation message');
        $successResponse->assertSee('Paiement à la livraison', false, 'Success page should show cash on delivery message');
        $successResponse->assertSessionHas('success', 'Session should have success message');
        
        // Vérifier le contenu du message flash
        $successMessage = session('success');
        $this->assertStringContainsString('enregistrée', $successMessage, 'Success message should mention order is registered');
    }

    #[Test]
    public function it_handles_validation_errors(): void
    {
        $this->actingAs($this->user);

        // Soumettre avec des champs manquants
        $response = $this->post(route('checkout.place'), [
            'payment_method' => 'cash_on_delivery',
            // Champs obligatoires manquants
        ]);

        $response->assertStatus(302); // Redirection back
        $response->assertSessionHasErrors(['full_name', 'email', 'phone']);
    }

    #[Test]
    public function it_handles_empty_cart(): void
    {
        $this->actingAs($this->user);

        // Ne pas ajouter de produits au panier

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

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error');
    }
}


<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\DatabaseCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\ErpStockMovement;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests Feature pour CheckoutController
 * 
 * Teste le tunnel officiel de checkout basé sur CheckoutController.
 * 
 * Scénarios couverts :
 * - Cash on Delivery : flux complet
 * - Paiement par Carte : redirection
 * - Mobile Money : redirection
 * - Validation échoue : gestion erreurs
 * - Panier vide : gestion cas limite
 */
class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected DatabaseCartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur client actif
        $this->user = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        // Créer un produit actif avec stock
        $this->product = Product::factory()->create([
            'stock' => 10,
            'price' => 10000,
            'is_active' => true,
        ]);

        // Instancier le service panier
        $this->cartService = new DatabaseCartService();

        // Important : rattacher le panier à l'utilisateur connecté
        $this->actingAs($this->user);
    }

    /**
     * SCÉNARIO 1 : Cash on Delivery - Flux complet OK
     * 
     * Vérifie que cash_on_delivery :
     * - Crée une commande
     * - Vide le panier
     * - Décrémente le stock immédiatement
     * - Redirige vers la page de succès
     */
    #[Test]
    public function it_creates_order_with_cash_on_delivery_and_redirects_to_success(): void
    {
        // Ajouter un produit au panier (utiliser l'objet Product, pas l'ID)
        // L'utilisateur est déjà connecté dans setUp()
        $this->cartService->add($this->product, 2);

        // Vérifier que le panier n'est pas vide
        $this->assertFalse($this->cartService->getItems()->isEmpty(), 'Cart should not be empty before checkout');

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
        $this->assertEquals($this->user->id, $order->user_id, 'Order should belong to the user');

        // Vérifier les détails de la commande
        $this->assertEquals('Test User', $order->customer_name);
        $this->assertEquals($this->user->email, $order->customer_email);
        $this->assertEquals('home_delivery', $order->shipping_method);
        $this->assertGreaterThan(0, $order->total_amount, 'Total amount should be greater than 0');

        // Vérifier que le stock a été décrémenté immédiatement (pour cash_on_delivery)
        $this->product->refresh();
        $this->assertEquals(8, $this->product->stock, 'Stock should be decremented by 2 (from 10 to 8)');

        // Vérifier qu'un mouvement de stock a été créé
        $stockMovement = ErpStockMovement::where('product_id', $this->product->id)
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->first();
        
        $this->assertNotNull($stockMovement, 'Stock movement should be created');
        $this->assertEquals(-2, $stockMovement->quantity, 'Stock movement quantity should be -2');
        $this->assertEquals('out', $stockMovement->type, 'Stock movement type should be "out"');

        // Vérifier que le panier est vidé
        $this->assertTrue($this->cartService->getItems()->isEmpty(), 'Cart should be empty after order creation');

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

    /**
     * SCÉNARIO 2 : Paiement par Carte - Redirection OK
     * 
     * Vérifie que le flux card :
     * - Crée une commande avec payment_method = 'card'
     * - Redirige vers checkout.card.pay
     * - Ne décrémente PAS le stock immédiatement (attente paiement)
     */
    #[Test]
    public function it_creates_order_with_card_payment_and_redirects_to_card_payment(): void
    {
        // Ajouter un produit au panier (l'utilisateur est déjà connecté dans setUp())
        $this->cartService->add($this->product, 1);

        $initialStock = $this->product->stock;

        // Soumettre le formulaire avec payment_method = 'card'
        $response = $this->post(route('checkout.place'), [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Rue Test',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'card',
        ]);

        // Vérifications de la réponse
        $response->assertStatus(302);
        $response->assertRedirect();
        
        // Vérifier que la redirection pointe vers checkout.card.pay
        $targetUrl = $response->getTargetUrl();
        $this->assertStringContainsString('checkout/card/pay', $targetUrl, 'Should redirect to checkout.card.pay');

        // Vérifier qu'une commande a été créée
        $order = Order::where('user_id', $this->user->id)
            ->where('payment_method', 'card')
            ->orderBy('created_at', 'desc')
            ->first();

        $this->assertNotNull($order, 'Order should be created');
        $this->assertEquals('card', $order->payment_method);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals('pending', $order->status);

        // Vérifier que le stock N'A PAS été décrémenté immédiatement (attente paiement)
        $this->product->refresh();
        $this->assertEquals($initialStock, $this->product->stock, 'Stock should NOT be decremented before payment confirmation');

        // Vérifier qu'aucun mouvement de stock n'a été créé (attente paiement)
        $stockMovement = ErpStockMovement::where('product_id', $this->product->id)
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->first();
        
        $this->assertNull($stockMovement, 'Stock movement should NOT be created before payment confirmation');

        // Vérifier que le panier est vidé
        $this->assertTrue($this->cartService->getItems()->isEmpty(), 'Cart should be empty after order creation');
    }

    /**
     * SCÉNARIO 3 : Mobile Money - Redirection OK
     * 
     * Vérifie que le flux mobile_money :
     * - Crée une commande avec payment_method = 'mobile_money'
     * - Redirige vers checkout.mobile-money.form
     * - Ne décrémente PAS le stock immédiatement (attente paiement)
     */
    #[Test]
    public function it_creates_order_with_mobile_money_payment_and_redirects_to_mobile_money_form(): void
    {
        // Ajouter un produit au panier (l'utilisateur est déjà connecté dans setUp())
        $this->cartService->add($this->product, 1);

        $initialStock = $this->product->stock;

        // Soumettre le formulaire avec payment_method = 'mobile_money'
        $response = $this->post(route('checkout.place'), [
            'full_name' => 'Test User',
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Rue Test',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'mobile_money',
        ]);

        // Vérifications de la réponse
        $response->assertStatus(302);
        $response->assertRedirect();
        
        // Vérifier que la redirection pointe vers checkout.mobile-money.form
        $targetUrl = $response->getTargetUrl();
        $this->assertStringContainsString('checkout/mobile-money', $targetUrl, 'Should redirect to checkout.mobile-money.form');
        $this->assertStringContainsString('/form', $targetUrl, 'Should redirect to mobile money form');

        // Vérifier qu'une commande a été créée
        $order = Order::where('user_id', $this->user->id)
            ->where('payment_method', 'mobile_money')
            ->orderBy('created_at', 'desc')
            ->first();

        $this->assertNotNull($order, 'Order should be created');
        $this->assertEquals('mobile_money', $order->payment_method);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals('pending', $order->status);

        // Vérifier que le stock N'A PAS été décrémenté immédiatement (attente paiement)
        $this->product->refresh();
        $this->assertEquals($initialStock, $this->product->stock, 'Stock should NOT be decremented before payment confirmation');

        // Vérifier qu'aucun mouvement de stock n'a été créé (attente paiement)
        $stockMovement = ErpStockMovement::where('product_id', $this->product->id)
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->first();
        
        $this->assertNull($stockMovement, 'Stock movement should NOT be created before payment confirmation');

        // Vérifier que le panier est vidé
        $this->assertTrue($this->cartService->getItems()->isEmpty(), 'Cart should be empty after order creation');
    }

    /**
     * SCÉNARIO 4 : Validation échoue - Gestion erreurs
     * 
     * Vérifie que si des champs obligatoires manquent :
     * - On revient sur la page checkout avec erreurs
     * - Les erreurs de validation sont présentes dans la session
     * - Aucune commande n'est créée
     */
    #[Test]
    public function it_handles_validation_errors_when_required_fields_are_missing(): void
    {
        // Ajouter un produit au panier (l'utilisateur est déjà connecté dans setUp())
        $this->cartService->add($this->product, 1);

        // Soumettre avec des champs obligatoires manquants
        $response = $this->post(route('checkout.place'), [
            'payment_method' => 'cash_on_delivery',
            // Champs obligatoires manquants : full_name, email, phone, address_line1, city, country, shipping_method
        ]);

        // Vérifications
        $response->assertStatus(302); // Redirection back
        $response->assertRedirect(route('checkout.index'));
        
        // Vérifier les erreurs de validation
        $response->assertSessionHasErrors([
            'full_name',
            'email',
            'phone',
            'address_line1',
            'city',
            'country',
            'shipping_method',
        ]);

        // Vérifier qu'aucune commande n'a été créée
        $orderCount = Order::where('user_id', $this->user->id)->count();
        $this->assertEquals(0, $orderCount, 'No order should be created when validation fails');

        // Vérifier que le panier n'a PAS été vidé
        $this->assertFalse($this->cartService->getItems()->isEmpty(), 'Cart should NOT be empty when validation fails');
    }

    /**
     * SCÉNARIO 5 : Panier vide - Gestion cas limite
     * 
     * Vérifie que :
     * - L'accès à GET /checkout avec panier vide redirige vers cart.index
     * - La tentative de POST /checkout avec panier vide redirige vers cart.index
     * - Un message d'erreur est présent
     */
    #[Test]
    public function it_redirects_to_cart_when_cart_is_empty_on_get_checkout(): void
    {
        // Ne pas ajouter de produits au panier (panier vide)
        // L'utilisateur est déjà connecté dans setUp()

        // Tenter d'accéder à GET /checkout
        $response = $this->get(route('checkout.index'));

        // Vérifications
        $response->assertStatus(302);
        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error', 'Should have error message about empty cart');
    }

    #[Test]
    public function it_redirects_to_cart_when_cart_is_empty_on_post_checkout(): void
    {
        // Ne pas ajouter de produits au panier (panier vide)
        // L'utilisateur est déjà connecté dans setUp()

        // Tenter de soumettre le formulaire avec panier vide
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

        // Vérifications
        $response->assertStatus(302);
        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error', 'Should have error message about empty cart');

        // Vérifier qu'aucune commande n'a été créée
        $orderCount = Order::where('user_id', $this->user->id)->count();
        $this->assertEquals(0, $orderCount, 'No order should be created when cart is empty');
    }

    /**
     * Test supplémentaire : Vérifier que les items de commande sont créés correctement
     */
    #[Test]
    public function it_creates_order_items_correctly(): void
    {
        // Ajouter plusieurs produits au panier (l'utilisateur est déjà connecté dans setUp())
        $product2 = Product::factory()->create([
            'stock' => 5,
            'price' => 5000,
            'is_active' => true,
        ]);

        $this->cartService->add($this->product, 2); // 2 * 10000 = 20000
        $this->cartService->add($product2, 3); // 3 * 5000 = 15000

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

        $response->assertStatus(302);

        // Récupérer la commande créée
        $order = Order::where('user_id', $this->user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $this->assertNotNull($order);

        // Vérifier que les items de commande sont créés
        $orderItems = $order->items;
        $this->assertCount(2, $orderItems, 'Order should have 2 items');

        // Vérifier le premier item
        $item1 = $orderItems->where('product_id', $this->product->id)->first();
        $this->assertNotNull($item1);
        $this->assertEquals(2, $item1->quantity);
        $this->assertEquals(10000, $item1->price);

        // Vérifier le deuxième item
        $item2 = $orderItems->where('product_id', $product2->id)->first();
        $this->assertNotNull($item2);
        $this->assertEquals(3, $item2->quantity);
        $this->assertEquals(5000, $item2->price);

        // Vérifier le total (sous-total + livraison)
        // Sous-total : 20000 + 15000 = 35000
        // Livraison : 2000 (home_delivery)
        // Total : 37000
        $this->assertEquals(37000, $order->total_amount, 'Total amount should be 37000 (35000 + 2000 shipping)');
    }
}


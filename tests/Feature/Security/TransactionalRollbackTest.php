<?php

namespace Tests\Feature\Security;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransactionalRollbackTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que si une exception survient pendant la création de commande,
     * TOUTES les écritures DB sont annulées (Order, Items, etc.)
     */
    public function test_order_creation_rolls_back_entirely_on_failure()
    {
        // 1. Préparation
        $product = Product::factory()->create([
            'stock' => 5,
            'price' => 1000,
            'product_type' => 'brand'
        ]);

        $user = User::factory()->create();

        $cartItems = collect([
            (object)[
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => $product->price,
                'product' => $product
            ]
        ]);

        $formData = [
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'address_line1' => 'Street 1',
            'city' => 'Douala',
            'country' => 'Cameroun',
            'shipping_method' => 'showroom_pickup',
            'payment_method' => 'cash'
        ];

        $orderService = app(OrderService::class);

        // 2. Action : Lancer le checkout avec le header magique pour forcer le rollback
        // On simule une requête avec le header via withHeaders
        try {
            // Pour que request() dans OrderService voie le header, on utilise call() ou on simule l'environnement
            $this->withHeaders(['X-Force-Rollback' => '1'])
                 ->actingAs($user);
            
            // Note: withHeaders s'applique aux requêtes via $this->json() etc. 
            // Ici on appelle le service directement, on va donc injecter le header dans la request globale de Laravel.
            request()->headers->set('X-Force-Rollback', '1');

            $orderService->createOrderFromCart($formData, $cartItems, $user->id);
            $this->fail('L\'exception aurait dû être levée.');
        } catch (\Exception $e) {
            $this->assertEquals('FORCED_ROLLBACK_TEST', $e->getMessage());
        }

        // 3. Vérification : Aucune commande en base, stock intact
        $this->assertCount(0, Order::all());
        $this->assertEquals(5, $product->fresh()->stock);
        
        // Nettoyage du header pour les autres tests
        request()->headers->remove('X-Force-Rollback');
    }
}

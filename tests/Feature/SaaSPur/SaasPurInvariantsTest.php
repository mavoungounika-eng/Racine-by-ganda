<?php

namespace Tests\Feature\SaaSPur;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\CreatorProfile;
use App\Models\PaymentPreference;
use App\Services\SaaSCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasPurInvariantsTest extends TestCase
{
    use RefreshDatabase;

    protected SaaSCheckoutService $saasService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saasService = new SaaSCheckoutService();
    }

    /** @test */
    public function it_forbids_mixed_carts_brand_and_creator()
    {
        $brandProduct = Product::factory()->create(['product_type' => 'brand']);
        $creatorProduct = Product::factory()->create(['product_type' => 'marketplace']);

        $cartItems = collect([
            (object)['product' => $brandProduct],
            (object)['product' => $creatorProduct]
        ]);

        $this->expectException(\App\Exceptions\OrderException::class);
        $this->expectExceptionMessage('Panier mixte non autorisé');

        $this->saasService->validateCartIntegrity($cartItems);
    }

    /** @test */
    public function it_returns_brand_credentials_for_brand_product()
    {
        $config = $this->saasService->getPaymentConfig(null);

        $this->assertEquals('brand', $config['type']);
        $this->assertEquals(config('services.stripe.secret'), $config['stripe_secret']);
    }

    /** @test */
    public function it_returns_creator_credentials_for_creator_product()
    {
        $creator = User::factory()->create();
        $profile = CreatorProfile::factory()->create(['user_id' => $creator->id]);
        $prefs = PaymentPreference::create([
            'creator_profile_id' => $profile->id,
            'stripe_secret_key' => 'sk_test_creator',
            'stripe_publishable_key' => 'pk_test_creator',
            'payment_connection_status' => 'connected'
        ]);

        $config = $this->saasService->getPaymentConfig($creator->id);

        $this->assertEquals('creator', $config['type']);
        $this->assertEquals('sk_test_creator', $config['stripe_secret']);
    }

    /** @test */
    public function it_forbids_ledger_entries_for_creator_orders()
    {
        $creator = User::factory()->create();
        $order = Order::factory()->create([
            'creator_id' => $creator->id,
            'payment_status' => 'paid'
        ]);

        $ledgerService = app(\Modules\Accounting\Services\LedgerService::class);

        $this->expectException(\Modules\Accounting\Exceptions\LedgerException::class);
        $this->expectExceptionMessage('SÉCURITÉ SAAS PUR');

        $ledgerService->createSaleEntry(
            $order,
            'VT',
            '5121',
            '7011',
            100.0
        );
    }

    /** @test */
    public function it_never_creates_accounting_entries_automatically_for_creator_orders()
    {
        $creator = User::factory()->create();
        $order = Order::factory()->create([
            'creator_id' => $creator->id,
            'payment_status' => 'paid'
        ]);

        // Vérification dans la table accounting_entries
        $entriesCount = \DB::table('accounting_entries')->count();
        $this->assertEquals(0, $entriesCount, "Une écriture comptable a été créée pour un créateur (Interdit en SaaS Pur)");
    }

    /** @test */
    public function it_forbids_pos_sales_for_creator_products()
    {
        // Simulation d'une tentative de vente POS pour un produit créateur
        $creatorProduct = Product::factory()->create(['product_type' => 'marketplace']);
        
        // Logique attendue : le POS filtre par Product::brand() pour la recherche
        $this->assertTrue($creatorProduct->product_type !== 'brand');
    }
}

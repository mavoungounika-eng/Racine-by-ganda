<?php

namespace Tests\Feature\Pos;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\PosSession;
use App\Jobs\ProcessPosSale;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

/**
 * Test de validation métier POS
 *
 * Vérifie que ProcessPosSale valide correctement :
 * - Produits existent
 * - Prix cohérents
 * - Paiement complet
 */
class PosValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected PosSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->product = Product::factory()->create([
            'price' => 100.00,
            'name' => 'Test Product'
        ]);

        $this->session = PosSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'machine_id' => Str::uuid()
        ]);
    }

    /** @test */
    public function valide_vente_avec_produits_existants_et_prix_coherents()
    {
        Queue::fake();
        Event::fake();

        $payload = [
            'sale_id' => 1,
            'session_id' => $this->session->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 100.00
                ]
            ],
            'total_amount' => 200.00,
            'payment_method' => 'cash'
        ];

        $job = new ProcessPosSale($payload);
        $job->handle();

        // Si pas d'exception, validation OK
        $this->assertTrue(true);
    }

    /** @test */
    public function rejette_vente_avec_produit_inexistant()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Produits inexistants');

        $payload = [
            'sale_id' => 1,
            'items' => [
                [
                    'product_id' => 99999, // ID inexistant
                    'quantity' => 1,
                    'price' => 100.00
                ]
            ],
            'total_amount' => 100.00
        ];

        $job = new ProcessPosSale($payload);
        $job->handle();
    }

    /** @test */
    public function rejette_vente_avec_prix_incoherent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prix incohérent');

        $payload = [
            'sale_id' => 1,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 150.00 // Prix différent de 100.00
                ]
            ],
            'total_amount' => 150.00
        ];

        $job = new ProcessPosSale($payload);
        $job->handle();
    }

    /** @test */
    public function rejette_vente_avec_total_incoherent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total incohérent');

        $payload = [
            'sale_id' => 1,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 100.00
                ]
            ],
            'total_amount' => 150.00 // Total différent de 100.00
        ];

        $job = new ProcessPosSale($payload);
        $job->handle();
    }
}
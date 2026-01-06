<?php

namespace Tests\Feature\Pos;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\PosSession;
use App\Models\PosSale;
use App\Models\PosPayment;
use App\Models\PosCashMovement;
use App\Models\FinancialIntent;
use App\Services\Pos\PosSessionService;
use App\Services\Pos\PosSaleService;
use Illuminate\Support\Str;

/**
 * POS Invariants Test Suite
 * 
 * Tests critiques validant les 7 invariants POS audit-ready:
 * 1. Pas de vente sans session ouverte
 * 2. Pas de cash "paid" avant clôture
 * 3. POS ≠ autorité comptable
 * 4. Une session = un responsable
 * 5. Toute anomalie = traçable
 * 6. Offline ≠ perte de vérité
 * 7. Fait terrain ≠ écriture comptable
 */
class PosInvariantsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected string $machineId;
    protected PosSessionService $sessionService;
    protected PosSaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->product = Product::factory()->create([
            'price' => 1000,
            'stock' => 100,
        ]);

        $this->machineId = Str::uuid()->toString();
        $this->sessionService = app(PosSessionService::class);
        $this->saleService = app(PosSaleService::class);
        
        // Seed accounting data for FinancialIntent tests
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);
    }

    /**
     * @test
     * INVARIANT 1: Pas de vente sans session ouverte
     */
    public function it_blocks_sale_without_open_session()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Aucune session ouverte');

        $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $this->product->id, 'quantity' => 1]],
            'cash',
            $this->user->id
        );
    }

    /**
     * @test
     * INVARIANT 1: Vente possible avec session ouverte
     */
    public function it_allows_sale_with_open_session()
    {
        // Ouvrir une session
        $session = $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            50000
        );

        // Créer une vente
        $sale = $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $this->product->id, 'quantity' => 1]],
            'cash',
            $this->user->id
        );

        $this->assertNotNull($sale);
        $this->assertEquals($session->id, $sale->session_id);
        $this->assertEquals(PosSale::STATUS_PENDING, $sale->status);
    }

    /**
     * @test
     * INVARIANT 2: Cash payment stays pending after sale
     */
    public function cash_payment_stays_pending_after_sale()
    {
        $session = $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            50000
        );

        $sale = $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $this->product->id, 'quantity' => 2, 'price' => 1000]],
            'cash',
            $this->user->id
        );

        // Vérifier que le paiement est pending
        $payment = $sale->payments->first();
        $this->assertNotNull($payment);
        $this->assertEquals(PosPayment::STATUS_PENDING, $payment->status);
        $this->assertNull($payment->confirmed_at);
    }

    /**
     * @test
     * INVARIANT 2: Cash confirmed only on session close
     */
    public function cash_confirmed_only_on_session_close()
    {
        $session = $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            50000
        );

        $sale = $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $this->product->id, 'quantity' => 1, 'price' => 1000]],
            'cash',
            $this->user->id
        );

        $payment = $sale->payments->first();
        $this->assertEquals(PosPayment::STATUS_PENDING, $payment->status);

        // Fermer la session
        $this->sessionService->closeSession($session, 51000, $this->user->id);

        // Rafraîchir le paiement
        $payment->refresh();
        $this->assertEquals(PosPayment::STATUS_CONFIRMED, $payment->status);
        $this->assertNotNull($payment->confirmed_at);
    }

    /**
     * @test
     * INVARIANT 3: POS sale does not trigger PaymentRecorded
     */
    public function pos_sale_does_not_trigger_payment_recorded()
    {
        $session = $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            50000
        );

        $sale = $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $this->product->id, 'quantity' => 1, 'price' => 1000]],
            'cash',
            $this->user->id
        );

        // Vérifier qu'aucun FinancialIntent classique n'est créé
        $intent = FinancialIntent::where('reference_type', 'order')
            ->where('reference_id', $sale->order_id)
            ->where('intent_type', FinancialIntent::TYPE_PAYMENT)
            ->first();

        $this->assertNull($intent, 'POS should not create standard payment intent');
    }

    /**
     * @test
     * INVARIANT 3: Session closure creates PosCashSettlementIntent
     */
    public function session_closure_creates_pos_cash_settlement_intent()
    {
        $session = $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            50000
        );

        $sale = $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $this->product->id, 'quantity' => 1, 'price' => 1000]],
            'cash',
            $this->user->id
        );

        // Fermer la session (dispatche PosSessionClosed)
        $this->sessionService->closeSession($session, 51000, $this->user->id);

        // Vérifier qu'un PosCashSettlementIntent est créé
        $intent = FinancialIntent::where('reference_type', 'pos_session')
            ->where('reference_id', $session->id)
            ->where('intent_type', FinancialIntent::TYPE_POS_CASH_SETTLEMENT)
            ->first();

        $this->assertNotNull($intent, 'Session closure should create PosCashSettlementIntent');
        $this->assertEquals(1000, $intent->amount);
    }

    /**
     * @test
     * INVARIANT 4: Session has responsible user (opened_by)
     */
    public function session_has_responsible_user()
    {
        $session = $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            50000
        );

        $this->assertEquals($this->user->id, $session->opened_by);
        $this->assertNotNull($session->opener);
    }

    /**
     * @test
     * INVARIANT 5: Cash movements are tracked
     */
    public function cash_movements_are_tracked()
    {
        $session = $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            50000
        );

        // Vérifier mouvement d'ouverture
        $openingMovement = $session->cashMovements()
            ->where('type', PosCashMovement::TYPE_OPENING)
            ->first();

        $this->assertNotNull($openingMovement);
        $this->assertEquals(50000, $openingMovement->amount);
        $this->assertEquals('in', $openingMovement->direction);

        // Créer une vente cash
        $sale = $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $this->product->id, 'quantity' => 1, 'price' => 1000]],
            'cash',
            $this->user->id
        );

        // Vérifier mouvement de vente
        $saleMovement = $session->cashMovements()
            ->where('type', PosCashMovement::TYPE_SALE)
            ->where('pos_sale_id', $sale->id)
            ->first();

        $this->assertNotNull($saleMovement);
        $this->assertEquals(1000, $saleMovement->amount);
    }

    /**
     * @test
     * INVARIANT 5: Cash difference is calculated on close
     */
    public function cash_difference_is_calculated_on_close()
    {
        $session = $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            50000
        );

        $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $this->product->id, 'quantity' => 1, 'price' => 1000]],
            'cash',
            $this->user->id
        );

        // Expected: 50000 + 1000 = 51000
        // Actual: 50500 (500 XAF manquants)
        $this->sessionService->closeSession($session, 50500, $this->user->id);

        $session->refresh();
        $this->assertEquals(51000, $session->expected_cash);
        $this->assertEquals(50500, $session->closing_cash);
        $this->assertEquals(-500, $session->cash_difference);
    }

    /**
     * @test
     * INVARIANT 6: Same machine cannot have two open sessions
     */
    public function same_machine_cannot_have_two_open_sessions()
    {
        $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            50000
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Une session est déjà ouverte');

        $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            60000
        );
    }

    /**
     * @test
     * INVARIANT 7: POS sale creates order with null user_id
     */
    public function pos_sale_creates_order_with_null_user_id()
    {
        $session = $this->sessionService->openSession(
            $this->machineId,
            $this->user->id,
            50000
        );

        $sale = $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $this->product->id, 'quantity' => 1]],
            'cash',
            $this->user->id
        );

        // POS orders have user_id = null to distinguish from e-commerce
        $this->assertNull($sale->order->user_id);
    }
}

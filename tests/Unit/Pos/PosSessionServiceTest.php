<?php

namespace Tests\Unit\Pos;

use App\Events\PosSessionClosed;
use App\Models\PosCashMovement;
use App\Models\PosPayment;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Pos\PosSaleService;
use App\Services\Pos\PosSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PosSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->service = app(PosSessionService::class);
    }

    private function createBrandProduct(int $stock = 10): Product
    {
        return Product::factory()->create([
            'product_type' => 'brand',
            'stock' => $stock,
            'price' => 1000,
        ]);
    }

    public function test_openSession_creates_session_with_open_status(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();

        $session = $this->service->openSession($machineId, $user->id, 5000.00);

        $this->assertEquals(PosSession::STATUS_OPEN, $session->status);
        $this->assertEquals($machineId, $session->machine_id);
        $this->assertEquals($user->id, $session->opened_by);
    }

    public function test_openSession_fails_if_session_already_open_for_user(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();

        $this->service->openSession($machineId, $user->id, 5000.00);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Une session est déjà ouverte');
        $this->service->openSession($machineId, $user->id, 5000.00);
    }

    public function test_openSession_fails_with_clear_error_if_operator_has_active_session_on_another_machine(): void
    {
        // Régression: la contrainte unique DB (opened_by, is_active) est cross-machine.
        // On doit lever une erreur EXPLICITE avant de toucher la couche DB, pas un
        // 409 cryptique "Duplicate entry 'X-1' for key pos_sessions.uq_user_active_session".
        $user = User::factory()->create();
        $machineA = (string) Str::uuid();
        $machineB = (string) Str::uuid();

        $this->service->openSession($machineA, $user->id, 5000.00);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('autre machine');
        $this->service->openSession($machineB, $user->id, 3000.00);
    }

    public function test_openSession_succeeds_on_new_machine_after_previous_session_closed(): void
    {
        // L invariant ne doit PAS bloquer quand la session précédente est clôturée:
        // closeSession() libère is_active (→ NULL), autorisant l ouverture suivante.
        $user = User::factory()->create();
        $machineA = (string) Str::uuid();
        $machineB = (string) Str::uuid();

        $first = $this->service->openSession($machineA, $user->id, 5000.00);
        $this->service->closeSession($first, 5000.00, $user->id);

        $second = $this->service->openSession($machineB, $user->id, 2000.00);

        $this->assertEquals(PosSession::STATUS_OPEN, $second->status);
        $this->assertEquals($machineB, $second->machine_id);
        $this->assertNotEquals($first->id, $second->id);
    }

    public function test_openSession_clears_stale_active_flag_on_closed_operator_sessions(): void
    {
        $user = User::factory()->create();
        $machineA = (string) Str::uuid();
        $machineB = (string) Str::uuid();

        $stale = PosSession::factory()->create([
            'machine_id' => $machineA,
            'opened_by' => $user->id,
            'opened_at' => now()->subHour(),
            'opening_cash' => 5000.00,
            'status' => PosSession::STATUS_CLOSED,
            'is_active' => 1,
            'closed_at' => now(),
            'closed_by' => $user->id,
        ]);

        $session = $this->service->openSession($machineB, $user->id, 2000.00);

        $this->assertEquals(PosSession::STATUS_OPEN, $session->status);
        $this->assertEquals($machineB, $session->machine_id);
        $this->assertNull($stale->fresh()->is_active);
    }

    public function test_openSession_records_opening_cash_amount(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();

        $session = $this->service->openSession($machineId, $user->id, 7500.00);

        $this->assertEquals('7500.00', (string) $session->opening_cash);
        $this->assertDatabaseHas('pos_cash_movements', [
            'session_id' => $session->id,
            'type' => PosCashMovement::TYPE_OPENING,
            'amount' => 7500.00,
        ]);
    }

    public function test_closeSession_confirms_all_pending_cash_payments(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $session = $this->service->openSession($machineId, $user->id, 5000.00);

        $product = $this->createBrandProduct(10);
        $saleService = app(PosSaleService::class);

        $sale = $saleService->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 2, 'price' => 1000]],
            PosPayment::METHOD_CASH,
            $user->id
        );

        $payment = $sale->payments->first();
        $this->assertEquals(PosPayment::STATUS_PENDING, $payment->status);

        $this->service->closeSession($session, 7000.00, $user->id);

        $payment->refresh();
        $this->assertEquals(PosPayment::STATUS_CONFIRMED, $payment->status);
    }

    public function test_closeSession_calculates_cash_discrepancy(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $session = $this->service->openSession($machineId, $user->id, 5000.00);

        $this->service->closeSession($session, 5200.00, $user->id);

        $session->refresh();
        $this->assertEquals('200.00', (string) $session->cash_difference);
    }

    public function test_closeSession_fails_if_session_already_closed(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $session = $this->service->openSession($machineId, $user->id, 5000.00);

        $this->service->closeSession($session, 5000.00, $user->id);

        $this->expectException(\DomainException::class);
        $this->service->closeSession($session, 5000.00, $user->id);
    }

    public function test_closeSession_creates_financial_intent(): void
    {
        $this->markTestSkipped('FinancialIntent is created by PosSessionClosed listener, not PosSessionService.');
    }

    public function test_closeSession_dispatches_PosSessionClosed_event(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $session = $this->service->openSession($machineId, $user->id, 5000.00);

        $this->service->closeSession($session, 5000.00, $user->id);

        Event::assertDispatched(PosSessionClosed::class);
    }

    public function test_preClose_returns_reconciliation_summary(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $session = $this->service->openSession($machineId, $user->id, 5000.00);

        $summary = $this->service->prepareClose($session);

        $this->assertEquals($session->id, $summary['session_id']);
        $this->assertEquals('5000.00', (string) $summary['opening_cash']);
        $this->assertEquals(0, $summary['total_sales']);
    }

    public function test_addCashMovement_creates_movement_record(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $session = $this->service->openSession($machineId, $user->id, 5000.00);

        $movement = $this->service->createAdjustment($session, 250.00, 'in', 'Test adjust', $user->id);

        $this->assertEquals(PosCashMovement::TYPE_ADJUSTMENT, $movement->type);
        $this->assertDatabaseHas('pos_cash_movements', [
            'id' => $movement->id,
            'session_id' => $session->id,
            'amount' => 250.00,
            'direction' => 'in',
        ]);
    }
}

<?php

namespace Tests\Unit\Pos;

use App\Exceptions\Accounting\AccountingNotBootstrappedException;
use App\Models\FinancialIntent;
use App\Models\Order;
use App\Models\PosPayment;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\User;
use App\Services\Financial\AccountingBootstrapService;
use App\Services\Financial\FinancialIntentService;
use App\Services\Pos\PosFinanceIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Modules\Accounting\Services\LedgerService;
use Tests\TestCase;

class PosFinanceIntegrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeSessionWithCashSales(int $count = 2, float $amount = 1000.00): PosSession
    {
        $user = User::factory()->create();
        $session = PosSession::factory()->create([
            'machine_id' => (string) Str::uuid(),
            'opened_by' => $user->id,
            'status' => PosSession::STATUS_CLOSED,
            'closed_by' => $user->id,
            'closed_at' => now(),
            'opening_cash' => 5000,
            'closing_cash' => 7000,
            'expected_cash' => 7000,
            'cash_difference' => 0,
        ]);

        for ($i = 0; $i < $count; $i++) {
            $order = Order::factory()->create([
                'user_id' => null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => 'cash',
                'total_amount' => $amount,
            ]);

            PosSale::create([
                'order_id' => $order->id,
                'machine_id' => $session->machine_id,
                'session_id' => $session->id,
                'total_amount' => $amount,
                'payment_method' => 'cash',
                'status' => PosSale::STATUS_PENDING,
                'created_by' => $user->id,
            ]);
        }

        return $session;
    }

    private function bindBootstrapReady(): void
    {
        $this->app->instance(AccountingBootstrapService::class, new class {
            public function assertReadyForPosSettlement(): void
            {
                // noop for tests
            }
        });
    }

    public function test_creates_financial_intent_on_session_close(): void
    {
        $this->bindBootstrapReady();
        $session = $this->makeSessionWithCashSales();

        $service = app(PosFinanceIntegrationService::class);
        $intent = $service->createCashSettlementIntent($session);

        $this->assertInstanceOf(FinancialIntent::class, $intent);
        $this->assertEquals('pos_cash_settlement', $intent->intent_type);
        $this->assertEquals('pos_session', $intent->reference_type);
        $this->assertEquals($session->id, $intent->reference_id);
    }

    public function test_financial_intent_has_correct_amount(): void
    {
        $this->bindBootstrapReady();
        $session = $this->makeSessionWithCashSales(3, 2000.00);

        $service = app(PosFinanceIntegrationService::class);
        $intent = $service->createCashSettlementIntent($session);

        $this->assertEquals('6000.00', (string) $intent->amount);
    }

    public function test_financial_intent_has_correct_type(): void
    {
        $this->bindBootstrapReady();
        $session = $this->makeSessionWithCashSales();

        $service = app(PosFinanceIntegrationService::class);
        $intent = $service->createCashSettlementIntent($session);

        $this->assertEquals('pos_cash_settlement', $intent->intent_type);
    }

    public function test_financial_intent_is_idempotent(): void
    {
        $this->bindBootstrapReady();
        $session = $this->makeSessionWithCashSales();

        $service = app(PosFinanceIntegrationService::class);
        $intent1 = $service->createCashSettlementIntent($session);
        $intent2 = $service->createCashSettlementIntent($session);

        $this->assertEquals($intent1->id, $intent2->id);
    }

    public function test_fails_gracefully_if_session_has_no_sales(): void
    {
        $this->bindBootstrapReady();
        $user = User::factory()->create();
        $session = PosSession::factory()->create([
            'machine_id' => (string) Str::uuid(),
            'opened_by' => $user->id,
            'status' => PosSession::STATUS_CLOSED,
            'closed_by' => $user->id,
            'closed_at' => now(),
            'opening_cash' => 5000,
            'closing_cash' => 5000,
            'expected_cash' => 5000,
            'cash_difference' => 0,
        ]);

        $service = app(PosFinanceIntegrationService::class);
        $intent = $service->createCashSettlementIntent($session);

        $this->assertEquals('0.00', (string) $intent->amount);
    }

    public function test_skips_accounting_entry_if_not_bootstrapped(): void
    {
        $session = $this->makeSessionWithCashSales();
        $service = app(PosFinanceIntegrationService::class);

        $this->expectException(AccountingNotBootstrappedException::class);
        $service->createCashSettlementIntent($session);
    }

    public function test_commit_intent_creates_accounting_entry_when_bootstrapped(): void
    {
        $this->bindBootstrapReady();
        $session = $this->makeSessionWithCashSales();
        $intent = FinancialIntent::create([
            'intent_type' => 'pos_cash_settlement',
            'reference_type' => 'pos_session',
            'reference_id' => $session->id,
            'amount' => 1000,
            'currency' => 'XAF',
            'status' => FinancialIntent::STATUS_PENDING,
            'idempotency_key' => FinancialIntent::generateIdempotencyKey('pos_session', $session->id),
        ]);

        $intentService = Mockery::mock(FinancialIntentService::class);
        $intentService->shouldReceive('commitIntent')
            ->once()
            ->andReturn(new \Modules\Accounting\Models\AccountingEntry());
        $ledgerService = new LedgerService();
        $service = new PosFinanceIntegrationService($intentService, $ledgerService);
        $entry = $service->commitIntent($intent);

        $this->assertInstanceOf(\Modules\Accounting\Models\AccountingEntry::class, $entry);
    }

    public function test_dispatches_expected_events_after_intent_created(): void
    {
        $this->markTestSkipped('PosFinanceIntegrationService does not dispatch events directly; listeners handle downstream processing.');
    }

    public function test_creates_card_payment_intent_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $session = PosSession::factory()->create([
            'machine_id' => (string) Str::uuid(),
            'opened_by' => $user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);
        $order = Order::factory()->create(['user_id' => null, 'payment_method' => 'card']);
        $sale = PosSale::create([
            'order_id' => $order->id,
            'machine_id' => $session->machine_id,
            'session_id' => $session->id,
            'total_amount' => 1000,
            'payment_method' => 'card',
            'status' => PosSale::STATUS_PENDING,
            'created_by' => $user->id,
        ]);
        $payment = PosPayment::create([
            'pos_sale_id' => $sale->id,
            'method' => 'card',
            'amount' => 1000,
            'status' => PosPayment::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => $user->id,
        ]);

        $service = app(PosFinanceIntegrationService::class);
        $intent1 = $service->createCardPaymentIntent($payment);
        $intent2 = $service->createCardPaymentIntent($payment);

        $this->assertEquals('pos_card_payment', $intent1->intent_type);
        $this->assertEquals($intent1->id, $intent2->id);
    }

    public function test_creates_mobile_payment_intent_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $session = PosSession::factory()->create([
            'machine_id' => (string) Str::uuid(),
            'opened_by' => $user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);
        $order = Order::factory()->create(['user_id' => null, 'payment_method' => 'mobile_money']);
        $sale = PosSale::create([
            'order_id' => $order->id,
            'machine_id' => $session->machine_id,
            'session_id' => $session->id,
            'total_amount' => 1000,
            'payment_method' => 'mobile_money',
            'status' => PosSale::STATUS_PENDING,
            'created_by' => $user->id,
        ]);
        $payment = PosPayment::create([
            'pos_sale_id' => $sale->id,
            'method' => 'mobile_money',
            'amount' => 1000,
            'status' => PosPayment::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => $user->id,
        ]);

        $service = app(PosFinanceIntegrationService::class);
        $intent1 = $service->createMobilePaymentIntent($payment);
        $intent2 = $service->createMobilePaymentIntent($payment);

        $this->assertEquals('pos_mobile_payment', $intent1->intent_type);
        $this->assertEquals($intent1->id, $intent2->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

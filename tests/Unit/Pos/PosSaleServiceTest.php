<?php

namespace Tests\Unit\Pos;

use App\Events\PosCardPaymentConfirmed;
use App\Events\PosMobilePaymentConfirmed;
use App\Models\Order;
use App\Models\PosPayment;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Pos\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosSaleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PosSaleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        Event::fake();
        $this->service = app(PosSaleService::class);
    }

    private function createOpenSession(string $machineId, int $userId): PosSession
    {
        return PosSession::factory()->create([
            'machine_id' => $machineId,
            'opened_by' => $userId,
            'status' => PosSession::STATUS_OPEN,
            'is_active' => 1,
        ]);
    }

    private function createBrandProduct(int $stock = 10): Product
    {
        return Product::factory()->create([
            'product_type' => 'brand',
            'stock' => $stock,
            'price' => 1000,
        ]);
    }

    public function test_createSale_creates_sale_with_pending_status(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $this->createOpenSession($machineId, $user->id);
        $product = $this->createBrandProduct(5);

        $sale = $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id
        );

        $this->assertEquals(PosSale::STATUS_PENDING, $sale->status);
        $this->assertEquals('pending', $sale->order->status);
        $this->assertEquals('pending', $sale->order->payment_status);
    }

    public function test_createSale_creates_associated_payment(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $this->createOpenSession($machineId, $user->id);
        $product = $this->createBrandProduct(5);

        $sale = $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id
        );

        $payment = $sale->payments->first();
        $this->assertNotNull($payment);
        $this->assertEquals(PosPayment::STATUS_PENDING, $payment->status);
        $this->assertEquals(PosPayment::METHOD_CARD, $payment->method);
    }

    public function test_createSale_enforces_idempotency_with_same_uuid(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $this->createOpenSession($machineId, $user->id);
        $product = $this->createBrandProduct(5);
        $key = (string) Str::uuid();

        $sale1 = $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id,
            [],
            $key
        );

        $sale2 = $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id,
            [],
            $key
        );

        $this->assertEquals($sale1->id, $sale2->id);
        $this->assertEquals(1, PosSale::where('session_id', $sale1->session_id)
            ->where('idempotency_key', $key)
            ->count());
    }

    public function test_createSale_fails_if_session_is_closed(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        PosSession::factory()->create([
            'machine_id' => $machineId,
            'opened_by' => $user->id,
            'status' => PosSession::STATUS_CLOSED,
            'is_active' => null,
        ]);
        $product = $this->createBrandProduct(5);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Aucune session ouverte');

        $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id
        );
    }

    public function test_createSale_fails_if_session_does_not_exist(): void
    {
        $user = User::factory()->create();
        $product = $this->createBrandProduct(5);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Aucune session ouverte');

        $this->service->createSale(
            (string) Str::uuid(),
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id
        );
    }

    public function test_confirm_card_payment_confirms_sale_and_payment(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $this->createOpenSession($machineId, $user->id);
        $product = $this->createBrandProduct(5);

        $sale = $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id
        );

        $payment = $sale->payments->first();
        $confirmed = $this->service->confirmCardPayment($payment, $user->id, 'txn_123', 'rcpt_1');

        $confirmed->refresh();
        $sale->refresh();
        $sale->order->refresh();

        $this->assertEquals(PosPayment::STATUS_CONFIRMED, $confirmed->status);
        $this->assertEquals(PosSale::STATUS_FINALIZED, $sale->status);
        $this->assertEquals('paid', $sale->order->payment_status);
        Event::assertDispatched(PosCardPaymentConfirmed::class);
    }

    public function test_confirm_mobile_payment_confirms_sale_and_payment(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $this->createOpenSession($machineId, $user->id);
        $product = $this->createBrandProduct(5);

        $sale = $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_MOBILE,
            $user->id
        );

        $payment = $sale->payments->first();
        $confirmed = $this->service->confirmMobilePayment($payment, 'txn_mobile_1', ['foo' => 'bar']);

        $confirmed->refresh();
        $sale->refresh();
        $sale->order->refresh();

        $this->assertEquals(PosPayment::STATUS_CONFIRMED, $confirmed->status);
        $this->assertEquals(PosSale::STATUS_FINALIZED, $sale->status);
        $this->assertEquals('paid', $sale->order->payment_status);
        Event::assertDispatched(PosMobilePaymentConfirmed::class);
    }

    public function test_confirm_card_payment_fails_if_already_confirmed(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $this->createOpenSession($machineId, $user->id);
        $product = $this->createBrandProduct(5);

        $sale = $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id
        );

        $payment = $sale->payments->first();
        $this->service->confirmCardPayment($payment, $user->id, 'txn_123', 'rcpt_1');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Ce paiement n'est pas en attente de confirmation");
        $this->service->confirmCardPayment($payment, $user->id, 'txn_123', 'rcpt_1');
    }

    public function test_cancelSale_cancels_sale_and_payment(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $this->createOpenSession($machineId, $user->id);
        $product = $this->createBrandProduct(5);

        $sale = $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id
        );

        $cancelled = $this->service->cancelSale($sale, $user->id, 'timeout');
        $cancelled->refresh();

        $this->assertEquals(PosSale::STATUS_CANCELLED, $cancelled->status);
        $this->assertEquals(PosPayment::STATUS_CANCELLED, $cancelled->payments->first()->status);
    }

    public function test_cancelSale_fails_if_sale_already_confirmed(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $this->createOpenSession($machineId, $user->id);
        $product = $this->createBrandProduct(5);

        $sale = $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id
        );

        $payment = $sale->payments->first();
        $this->service->confirmCardPayment($payment, $user->id, 'txn_123', 'rcpt_1');
        $sale->refresh();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Seules les ventes pending peuvent être annulées');
        $this->service->cancelSale($sale, $user->id, 'late');
    }

    public function test_createSale_dispatches_expected_events(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $this->createOpenSession($machineId, $user->id);
        $product = $this->createBrandProduct(5);

        $this->service->createSale(
            $machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id
        );

        Event::assertNotDispatched(PosCardPaymentConfirmed::class);
        Event::assertNotDispatched(PosMobilePaymentConfirmed::class);
    }

    public function test_createSale_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $machineId = (string) Str::uuid();
        $this->createOpenSession($machineId, $user->id);

        $this->expectException(\Throwable::class);
        $this->service->createSale(
            $machineId,
            [['quantity' => 1, 'price' => 1000]],
            PosPayment::METHOD_CARD,
            $user->id
        );
    }
}

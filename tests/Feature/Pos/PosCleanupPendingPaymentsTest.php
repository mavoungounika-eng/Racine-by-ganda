<?php

namespace Tests\Feature\Pos;

use App\Jobs\CleanupPendingPosPayments;
use App\Models\Order;
use App\Models\PosPayment;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosCleanupPendingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function createSaleWithPayment(string $method, string $status, \DateTimeInterface $createdAt): PosPayment
    {
        $user = User::factory()->create();
        $session = PosSession::factory()->create([
            'opened_by' => $user->id,
            'status' => PosSession::STATUS_OPEN,
        ]);

        $order = Order::factory()->create([
            'user_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => $method,
        ]);

        $sale = PosSale::create([
            'order_id' => $order->id,
            'machine_id' => $session->machine_id ?? (string) Str::uuid(),
            'session_id' => $session->id,
            'total_amount' => 10000,
            'payment_method' => $method,
            'status' => PosSale::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        $payment = PosPayment::create([
            'pos_sale_id' => $sale->id,
            'method' => $method,
            'amount' => 10000,
            'status' => $status,
        ]);

        $payment->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $payment->fresh();
    }

    public function test_stale_card_payment_is_cancelled_after_threshold(): void
    {
        $payment = $this->createSaleWithPayment(
            PosPayment::METHOD_CARD,
            PosPayment::STATUS_PENDING,
            now()->subMinutes(31)
        );

        (new CleanupPendingPosPayments(30))->handle();

        $payment->refresh();
        $payment->sale->refresh();

        $this->assertEquals(PosPayment::STATUS_CANCELLED, $payment->status);
        $this->assertEquals('timeout', $payment->cancel_reason);
        $this->assertEquals(PosSale::STATUS_CANCELLED, $payment->sale->status);
    }

    public function test_stale_mobile_payment_is_cancelled_after_threshold(): void
    {
        $payment = $this->createSaleWithPayment(
            PosPayment::METHOD_MOBILE,
            PosPayment::STATUS_PENDING,
            now()->subMinutes(31)
        );

        (new CleanupPendingPosPayments(30))->handle();

        $payment->refresh();
        $payment->sale->refresh();

        $this->assertEquals(PosPayment::STATUS_CANCELLED, $payment->status);
        $this->assertEquals('timeout', $payment->cancel_reason);
        $this->assertEquals(PosSale::STATUS_CANCELLED, $payment->sale->status);
    }

    public function test_recent_pending_payment_is_not_cancelled(): void
    {
        $payment = $this->createSaleWithPayment(
            PosPayment::METHOD_CARD,
            PosPayment::STATUS_PENDING,
            now()->subMinutes(5)
        );

        (new CleanupPendingPosPayments(30))->handle();

        $payment->refresh();

        $this->assertEquals(PosPayment::STATUS_PENDING, $payment->status);
    }

    public function test_cash_payment_is_never_cancelled(): void
    {
        $payment = $this->createSaleWithPayment(
            PosPayment::METHOD_CASH,
            PosPayment::STATUS_PENDING,
            now()->subMinutes(120)
        );

        (new CleanupPendingPosPayments(30))->handle();

        $payment->refresh();

        $this->assertEquals(PosPayment::STATUS_PENDING, $payment->status);
    }

    public function test_already_confirmed_payment_is_not_affected(): void
    {
        $payment = $this->createSaleWithPayment(
            PosPayment::METHOD_CARD,
            PosPayment::STATUS_CONFIRMED,
            now()->subMinutes(120)
        );

        (new CleanupPendingPosPayments(30))->handle();

        $payment->refresh();

        $this->assertEquals(PosPayment::STATUS_CONFIRMED, $payment->status);
    }
}

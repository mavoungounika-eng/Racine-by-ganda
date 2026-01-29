<?php

namespace Tests\Feature\Payments;

use App\Jobs\ProcessMonetbilCallbackEventJob;
use App\Models\MonetbilCallbackEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentEventMapperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MonetbilWebhookPaymentMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_monetbil_webhook_maps_transaction_id_to_payment(): void
    {
        Queue::fake();

        $order = Order::factory()->create([
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'channel' => 'mobile_money',
            'provider' => 'mtn_momo',
            'status' => 'initiated',
            'external_reference' => 'MB_TX_001',
        ]);

        $payload = [
            'transaction_id' => 'MB_TX_001',
            'status' => 'success',
            'payment_ref' => 'ref_1',
            'transaction_uuid' => 'uuid_1',
            'timestamp' => now()->timestamp,
        ];

        $res = $this->postJson('/api/webhooks/monetbil', $payload);
        $res->assertStatus(200);

        Queue::assertPushed(ProcessMonetbilCallbackEventJob::class);

        $event = MonetbilCallbackEvent::query()->latest('id')->firstOrFail();
        (new ProcessMonetbilCallbackEventJob($event->id))->handle(app(PaymentEventMapperService::class));

        $payment->refresh();
        $order->refresh();
        $event->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertSame('processed', $event->status);
    }

    public function test_monetbil_webhook_fails_when_payment_not_found(): void
    {
        $order = Order::factory()->create([
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);

        $payload = [
            'transaction_id' => 'MB_TX_DOES_NOT_EXIST',
            'status' => 'success',
            'payment_ref' => 'ref_1',
            'transaction_uuid' => 'uuid_1',
            'timestamp' => now()->timestamp,
        ];

        $res = $this->postJson('/api/webhooks/monetbil', $payload);
        $res->assertStatus(200);

        $event = MonetbilCallbackEvent::query()->latest('id')->firstOrFail();
        $mapperService = app(PaymentEventMapperService::class);

        // Simuler les 2 premières tentatives (qui vont throw pour retry)
        $job1 = new ProcessMonetbilCallbackEventJob($event->id);
        try {
            $job1->handle($mapperService);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Payment not found', $e->getMessage());
        }

        $job2 = new ProcessMonetbilCallbackEventJob($event->id);
        try {
            $job2->handle($mapperService);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Payment not found', $e->getMessage());
        }

        // Dernière tentative : simuler attempts() === tries
        $job3 = new class($event->id) extends ProcessMonetbilCallbackEventJob {
            public function attempts(): int
            {
                return 3; // Simuler dernière tentative (tries = 3)
            }
        };
        $job3->handle($mapperService);

        $event->refresh();
        $order->refresh();

        // Vérifier que l'événement est marqué comme failed
        $this->assertSame('failed', $event->status);

        // Order ne doit pas bouger
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $order->status);
    }
}

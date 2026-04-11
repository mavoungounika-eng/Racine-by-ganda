<?php

namespace Tests\Unit;

use App\Jobs\ProcessMonetbilCallbackEventJob;
use App\Models\MonetbilCallbackEvent;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payments\PaymentEventMapperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessMonetbilCallbackEventJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test idempotence : job peut être relancé sans effet de bord
     */
    public function test_job_is_idempotent(): void
    {
        $order = Order::factory()->create();
        $transaction = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'provider' => 'monetbil',
            'status' => 'pending',
            'payment_ref' => 'PAY_123',
        ]);

        $event = MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'PAY_123'),
            'payment_ref' => 'PAY_123',
            'status' => 'received',
            'payload' => ['status' => 'success'],
            'received_at' => now(),
        ]);

        $mapperService = new PaymentEventMapperService();

        // Exécuter le job une première fois
        $job = new ProcessMonetbilCallbackEventJob($event->id);
        $job->handle($mapperService);

        $transaction->refresh();
        $event->refresh();

        $this->assertEquals('succeeded', $transaction->status);
        $this->assertEquals('processed', $event->status);

        // Exécuter le job une deuxième fois (idempotence)
        $job2 = new ProcessMonetbilCallbackEventJob($event->id);
        $job2->handle($mapperService);

        $transaction->refresh();
        $event->refresh();

        // Le statut ne doit pas changer
        $this->assertEquals('succeeded', $transaction->status);
        $this->assertEquals('processed', $event->status);
    }

    /**
     * Test que le job utilise des locks DB
     */
    public function test_job_uses_db_locks(): void
    {
        $order = Order::factory()->create();
        $transaction = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'provider' => 'monetbil',
            'status' => 'pending',
            'payment_ref' => 'PAY_123',
        ]);

        $event = MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'PAY_123'),
            'payment_ref' => 'PAY_123',
            'status' => 'received',
            'payload' => ['status' => 'success'],
            'received_at' => now(),
        ]);

        $mapperService = new PaymentEventMapperService();

        DB::transaction(function () use ($event, $mapperService) {
            $job = new ProcessMonetbilCallbackEventJob($event->id);
            $job->handle($mapperService);
        });

        $event->refresh();
        $this->assertEquals('processed', $event->status);
    }

    /**
     * Test que le job ignore les événements déjà traités
     */
    public function test_job_ignores_already_processed_events(): void
    {
        $event = MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'PAY_123'),
            'payment_ref' => 'PAY_123',
            'status' => 'processed',
            'payload' => ['status' => 'success'],
            'processed_at' => now(),
        ]);

        $mapperService = new PaymentEventMapperService();

        $job = new ProcessMonetbilCallbackEventJob($event->id);
        $job->handle($mapperService);

        $event->refresh();
        $this->assertEquals('processed', $event->status);
    }

    /**
     * Test que le job met à jour la transaction et la commande
     */
    public function test_job_updates_transaction_and_order(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $transaction = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'provider' => 'monetbil',
            'status' => 'pending',
            'payment_ref' => 'PAY_123',
        ]);

        $event = MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'PAY_123'),
            'payment_ref' => 'PAY_123',
            'status' => 'received',
            'payload' => ['status' => 'success'],
            'received_at' => now(),
        ]);

        $mapperService = new PaymentEventMapperService();

        $job = new ProcessMonetbilCallbackEventJob($event->id);
        $job->handle($mapperService);

        $transaction->refresh();
        $order->refresh();
        $event->refresh();

        $this->assertEquals('succeeded', $transaction->status);
        $this->assertEquals('processing', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('processed', $event->status);
    }
}

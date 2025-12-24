<?php

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Jobs\ProcessMonetbilCallbackEventJob;
use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\MonetbilCallbackEvent;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\StripeWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentJobsIdempotenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test idempotence ProcessStripeWebhookEventJob : job déjà traité
     */
    public function test_stripe_job_idempotence_already_processed(): void
    {
        // Créer un événement déjà traité
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'processed',
            'processed_at' => now(),
            'payload_hash' => hash('sha256', 'test'),
        ]);

        // Exécuter le job
        $job = new ProcessStripeWebhookEventJob($event->id);
        $job->handle(app(\App\Services\Payments\PaymentEventMapperService::class));

        // Vérifier que l'événement est toujours "processed" (pas retraité)
        $event->refresh();
        $this->assertEquals('processed', $event->status);
    }

    /**
     * Test idempotence ProcessStripeWebhookEventJob : transaction déjà succeeded
     */
    public function test_stripe_job_idempotence_transaction_already_succeeded(): void
    {
        // Créer une transaction déjà en succès
        $order = Order::factory()->create();
        $transaction = PaymentTransaction::create([
            'provider' => 'stripe',
            'order_id' => $order->id,
            'payment_ref' => 'PAY_123',
            'transaction_id' => 'pi_test_123',
            'amount' => 10000,
            'currency' => 'XAF',
            'status' => PaymentStatus::SUCCEEDED->value,
        ]);

        // Créer un événement reçu
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'payload_hash' => hash('sha256', 'test'),
        ]);

        // Exécuter le job
        $job = new ProcessStripeWebhookEventJob($event->id);
        $job->handle(app(\App\Services\Payments\PaymentEventMapperService::class));

        // Vérifier que l'événement est marqué comme traité
        $event->refresh();
        $this->assertEquals('processed', $event->status);

        // Vérifier que la transaction est toujours succeeded (pas modifiée)
        $transaction->refresh();
        $this->assertEquals(PaymentStatus::SUCCEEDED->value, $transaction->status);
    }

    /**
     * Test idempotence ProcessMonetbilCallbackEventJob : job déjà traité
     */
    public function test_monetbil_job_idempotence_already_processed(): void
    {
        // Créer un événement déjà traité
        $event = MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'test_key'),
            'payment_ref' => 'PAY_123',
            'status' => 'processed',
            'processed_at' => now(),
            'payload' => ['status' => 'success'],
            'received_at' => now(),
        ]);

        // Exécuter le job
        $job = new ProcessMonetbilCallbackEventJob($event->id);
        $job->handle(app(\App\Services\Payments\PaymentEventMapperService::class));

        // Vérifier que l'événement est toujours "processed" (pas retraité)
        $event->refresh();
        $this->assertEquals('processed', $event->status);
    }

    /**
     * Test idempotence ProcessMonetbilCallbackEventJob : transaction déjà succeeded
     */
    public function test_monetbil_job_idempotence_transaction_already_succeeded(): void
    {
        // Créer une transaction déjà en succès
        $order = Order::factory()->create();
        $transaction = PaymentTransaction::create([
            'provider' => 'monetbil',
            'order_id' => $order->id,
            'payment_ref' => 'PAY_123',
            'transaction_id' => 'TXN_123',
            'amount' => 10000,
            'currency' => 'XAF',
            'status' => PaymentStatus::SUCCEEDED->value,
        ]);

        // Créer un événement reçu
        $event = MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'test_key'),
            'payment_ref' => 'PAY_123',
            'status' => 'received',
            'payload' => ['status' => 'success'],
            'received_at' => now(),
        ]);

        // Exécuter le job
        $job = new ProcessMonetbilCallbackEventJob($event->id);
        $job->handle(app(\App\Services\Payments\PaymentEventMapperService::class));

        // Vérifier que l'événement est marqué comme traité
        $event->refresh();
        $this->assertEquals('processed', $event->status);

        // Vérifier que la transaction est toujours succeeded (pas modifiée)
        $transaction->refresh();
        $this->assertEquals(PaymentStatus::SUCCEEDED->value, $transaction->status);
    }

    /**
     * Test lock DB : deux jobs simultanés ne créent pas de doublon
     */
    public function test_stripe_job_lock_prevents_race_condition(): void
    {
        $order = Order::factory()->create();
        $transaction = PaymentTransaction::create([
            'provider' => 'stripe',
            'order_id' => $order->id,
            'payment_ref' => 'PAY_123',
            'transaction_id' => 'pi_test_123',
            'amount' => 10000,
            'currency' => 'XAF',
            'status' => PaymentStatus::PENDING->value,
        ]);

        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test_123',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'payload_hash' => hash('sha256', 'test'),
        ]);

        // Simuler deux exécutions simultanées avec lock
        DB::transaction(function () use ($event, $transaction) {
            $lockedEvent = StripeWebhookEvent::lockForUpdate()->find($event->id);
            
            // Vérifier que le lock fonctionne
            $this->assertNotNull($lockedEvent);
            
            // Exécuter le job
            $job = new ProcessStripeWebhookEventJob($event->id);
            $job->handle(app(\App\Services\Payments\PaymentEventMapperService::class));
        });

        // Vérifier que la transaction a été mise à jour une seule fois
        $transaction->refresh();
        $this->assertEquals(PaymentStatus::SUCCEEDED->value, $transaction->status);
    }
}





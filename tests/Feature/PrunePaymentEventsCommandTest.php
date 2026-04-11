<?php

namespace Tests\Feature;

use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrunePaymentEventsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function createStripeEvent(array $attributes, int $daysAgo): void
    {
        $event = StripeWebhookEvent::create($attributes);
        $timestamp = now()->subDays($daysAgo);

        $event->timestamps = false;
        $event->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveQuietly();
    }

    protected function createMonetbilEvent(array $attributes, int $daysAgo): void
    {
        $event = MonetbilCallbackEvent::create($attributes);
        $timestamp = now()->subDays($daysAgo);

        $event->timestamps = false;
        $event->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveQuietly();
    }

    /**
     * Test que le dry-run ne supprime rien
     */
    public function test_prune_events_dry_run_does_not_delete_anything(): void
    {
        // Créer des événements anciens
        $this->createStripeEvent([
            'event_id' => 'evt_test_1',
            'event_type' => 'checkout.session.completed',
            'status' => 'processed',
        ], 100);

        $this->createMonetbilEvent([
            'event_key' => 'test_key_1',
            'status' => 'processed',
            'payload' => [],
        ], 100);

        $this->assertDatabaseCount('stripe_webhook_events', 1);
        $this->assertDatabaseCount('monetbil_callback_events', 1);

        // Exécuter en dry-run
        $this->artisan('payments:prune-events --days=90 --dry-run')
            ->expectsOutput('Mode DRY-RUN : aucune suppression ne sera effectuée.')
            ->assertSuccessful();

        // Vérifier que rien n'a été supprimé
        $this->assertDatabaseCount('stripe_webhook_events', 1);
        $this->assertDatabaseCount('monetbil_callback_events', 1);
    }

    /**
     * Test que la purge supprime bien les événements anciens
     */
    public function test_prune_events_deletes_old_events(): void
    {
        // Créer des événements anciens (> 90 jours)
        $this->createStripeEvent([
            'event_id' => 'evt_old_1',
            'event_type' => 'checkout.session.completed',
            'status' => 'processed',
        ], 100);

        $this->createMonetbilEvent([
            'event_key' => 'old_key_1',
            'status' => 'processed',
            'payload' => [],
        ], 100);

        // Créer des événements récents (< 90 jours)
        $this->createStripeEvent([
            'event_id' => 'evt_recent_1',
            'event_type' => 'checkout.session.completed',
            'status' => 'processed',
        ], 30);

        $this->createMonetbilEvent([
            'event_key' => 'recent_key_1',
            'status' => 'processed',
            'payload' => [],
        ], 30);

        $this->assertDatabaseCount('stripe_webhook_events', 2);
        $this->assertDatabaseCount('monetbil_callback_events', 2);

        // Exécuter la purge
        $this->artisan('payments:prune-events --days=90')
            ->assertSuccessful();

        // Vérifier que seuls les anciens ont été supprimés
        $this->assertDatabaseCount('stripe_webhook_events', 1);
        $this->assertDatabaseCount('monetbil_callback_events', 1);
        $this->assertDatabaseHas('stripe_webhook_events', ['event_id' => 'evt_recent_1']);
        $this->assertDatabaseHas('monetbil_callback_events', ['event_key' => 'recent_key_1']);
    }

    /**
     * Test que les événements failed sont conservés si keep_failed=true
     */
    public function test_prune_events_keeps_failed_events_when_enabled(): void
    {
        // Créer un événement failed ancien
        $this->createStripeEvent([
            'event_id' => 'evt_failed_old',
            'event_type' => 'checkout.session.completed',
            'status' => 'failed',
        ], 100);

        // Créer un événement processed ancien
        $this->createStripeEvent([
            'event_id' => 'evt_processed_old',
            'event_type' => 'checkout.session.completed',
            'status' => 'processed',
        ], 100);

        // Configurer keep_failed = true
        config(['payments.events.keep_failed' => true]);

        $this->artisan('payments:prune-events --days=90')
            ->assertSuccessful();

        // Vérifier que failed est conservé, processed supprimé
        $this->assertDatabaseHas('stripe_webhook_events', ['event_id' => 'evt_failed_old']);
        $this->assertDatabaseMissing('stripe_webhook_events', ['event_id' => 'evt_processed_old']);
    }
}










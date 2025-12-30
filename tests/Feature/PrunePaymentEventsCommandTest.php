<?php

namespace Tests\Feature;

use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrunePaymentEventsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que le dry-run ne supprime rien
     */
    public function test_prune_events_dry_run_does_not_delete_anything(): void
    {
        // Créer des événements anciens
        StripeWebhookEvent::create([
            'event_id' => 'evt_test_1',
            'event_type' => 'checkout.session.completed',
            'status' => 'processed',
            'created_at' => now()->subDays(100),
        ]);

        MonetbilCallbackEvent::create([
            'event_key' => 'test_key_1',
            'status' => 'processed',
            'payload' => [],
            'created_at' => now()->subDays(100),
        ]);

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
        StripeWebhookEvent::create([
            'event_id' => 'evt_old_1',
            'event_type' => 'checkout.session.completed',
            'status' => 'processed',
            'created_at' => now()->subDays(100),
        ]);

        MonetbilCallbackEvent::create([
            'event_key' => 'old_key_1',
            'status' => 'processed',
            'payload' => [],
            'created_at' => now()->subDays(100),
        ]);

        // Créer des événements récents (< 90 jours)
        StripeWebhookEvent::create([
            'event_id' => 'evt_recent_1',
            'event_type' => 'checkout.session.completed',
            'status' => 'processed',
            'created_at' => now()->subDays(30),
        ]);

        MonetbilCallbackEvent::create([
            'event_key' => 'recent_key_1',
            'status' => 'processed',
            'payload' => [],
            'created_at' => now()->subDays(30),
        ]);

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
        StripeWebhookEvent::create([
            'event_id' => 'evt_failed_old',
            'event_type' => 'checkout.session.completed',
            'status' => 'failed',
            'created_at' => now()->subDays(100),
        ]);

        // Créer un événement processed ancien
        StripeWebhookEvent::create([
            'event_id' => 'evt_processed_old',
            'event_type' => 'checkout.session.completed',
            'status' => 'processed',
            'created_at' => now()->subDays(100),
        ]);

        // Configurer keep_failed = true
        config(['payments.events.keep_failed' => true]);

        $this->artisan('payments:prune-events --days=90')
            ->assertSuccessful();

        // Vérifier que failed est conservé, processed supprimé
        $this->assertDatabaseHas('stripe_webhook_events', ['event_id' => 'evt_failed_old']);
        $this->assertDatabaseMissing('stripe_webhook_events', ['event_id' => 'evt_processed_old']);
    }
}





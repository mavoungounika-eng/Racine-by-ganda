<?php

namespace Tests\Feature;

use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookRetentionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que le prune supprime bien les vieux processed (> 30j)
     */
    public function test_prune_deletes_old_processed_events(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $oldDate = Carbon::parse('2025-12-15 10:00:00')->subDays(35);
        $recentDate = Carbon::parse('2025-12-15 10:00:00')->subDays(10);

        // Event ancien processed (à supprimer) - utiliser DB::table pour forcer created_at
        $oldProcessedId = \DB::table('stripe_webhook_events')->insertGetId([
            'event_id' => 'evt_old_processed',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'processed',
            'processed_at' => $oldDate,
            'payload_hash' => hash('sha256', 'test'),
            'created_at' => $oldDate,
            'updated_at' => $oldDate,
        ]);

        // Event récent processed (à garder)
        $recentProcessedId = \DB::table('stripe_webhook_events')->insertGetId([
            'event_id' => 'evt_recent_processed',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'processed',
            'processed_at' => $recentDate,
            'payload_hash' => hash('sha256', 'test2'),
            'created_at' => $recentDate,
            'updated_at' => $recentDate,
        ]);

        $this->artisan('payments:prune-webhook-events', ['--days' => 30])
            ->assertSuccessful();

        $this->assertDatabaseMissing('stripe_webhook_events', ['id' => $oldProcessedId]);
        $this->assertDatabaseHas('stripe_webhook_events', ['id' => $recentProcessedId]);
    }

    /**
     * Test que le prune NE supprime PAS les received/failed même vieux
     */
    public function test_prune_keeps_received_failed_even_old(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        // Event ancien received (à garder)
        $oldReceived = StripeWebhookEvent::create([
            'event_id' => 'evt_old_received',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'payload_hash' => hash('sha256', 'test'),
            'created_at' => now()->subDays(35),
        ]);

        // Event ancien failed (à garder)
        $oldFailed = StripeWebhookEvent::create([
            'event_id' => 'evt_old_failed',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'failed',
            'payload_hash' => hash('sha256', 'test2'),
            'created_at' => now()->subDays(35),
        ]);

        $this->artisan('payments:prune-webhook-events', ['--days' => 30])
            ->assertSuccessful();

        $this->assertDatabaseHas('stripe_webhook_events', ['id' => $oldReceived->id]);
        $this->assertDatabaseHas('stripe_webhook_events', ['id' => $oldFailed->id]);
    }

    /**
     * Test que le prune fonctionne pour Monetbil aussi
     */
    public function test_prune_works_for_monetbil(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $oldDate = Carbon::parse('2025-12-15 10:00:00')->subDays(35);

        // Event ancien processed Monetbil (à supprimer) - utiliser DB::table pour forcer created_at
        $oldProcessedId = \DB::table('monetbil_callback_events')->insertGetId([
            'event_key' => 'monetbil_old_processed',
            'status' => 'processed',
            'processed_at' => $oldDate,
            'payload' => json_encode([]),
            'created_at' => $oldDate,
            'updated_at' => $oldDate,
        ]);

        $this->artisan('payments:prune-webhook-events', ['--days' => 30])
            ->assertSuccessful();

        $this->assertDatabaseMissing('monetbil_callback_events', ['id' => $oldProcessedId]);
    }

    /**
     * Test dry-run ne supprime rien
     */
    public function test_prune_dry_run_does_not_delete(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        $oldProcessed = StripeWebhookEvent::create([
            'event_id' => 'evt_dry_run',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'processed',
            'processed_at' => now()->subDays(35),
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->artisan('payments:prune-webhook-events', ['--days' => 30, '--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutput('Mode DRY-RUN : aucune suppression ne sera effectuée');

        $this->assertDatabaseHas('stripe_webhook_events', ['id' => $oldProcessed->id]);
    }
}





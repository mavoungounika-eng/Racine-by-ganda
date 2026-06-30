<?php

namespace Tests\Feature\Webhooks;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Webhooks\CircuitBreakerService;
use App\Services\Webhooks\WebhookDeduplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MonetbilWebhookResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration pour les tests
        config(['services.monetbil.service_secret' => 'test_secret']);
        config(['services.monetbil.allowed_ips' => null]); // Désactive IP whitelist pour les tests
        
        // Nettoyage caches
        app(WebhookDeduplicationService::class)->clearCache();
        app(CircuitBreakerService::class)->resetAll();
        \App\Models\ProcessedWebhook::query()->delete();
    }

    /** @test */
    public function it_deduplicates_identical_monetbil_webhooks()
    {
        $creator = User::factory()->create(['role' => 'createur']);
        $profile = \App\Models\CreatorProfile::factory()->create(['user_id' => $creator->id]);
        \App\Models\PaymentPreference::factory()->create([
            'creator_profile_id' => $profile->id,
            'payment_connection_status' => 'connected',
            'momo_provider' => 'monetbil',
            'momo_api_key' => 'test_momo_key'
        ]);

        $order = Order::factory()->create(['creator_id' => $creator->id, 'total_amount' => 1000]);
        $transaction = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'payment_ref' => 'monet_ref_123',
            'amount' => 1000,
            'status' => 'pending'
        ]);

        $payload = [
            'payment_ref' => 'monet_ref_123',
            'status' => 'success',
            'amount' => 1000,
            'transaction_id' => 'MB_123',
            'sign' => 'fake_sign' // verifySignature est mocké ou ignoré en dev
        ];

        // 2. First request - should be processed
        $response1 = $this->postJson('/payment/monetbil/notify', $payload);
        $response1->assertStatus(200);
        $response1->assertJson(['status' => 'success']);

        // Verify database state
        $this->assertEquals('success', $transaction->fresh()->status);
        $this->assertDatabaseHas('processed_webhooks', [
            'provider' => 'monetbil',
            'external_id' => 'monet_ref_123'
        ]);

        // 3. Second request - identical payload (should be skipped by infra)
        $response2 = $this->postJson('/payment/monetbil/notify', $payload);
        $response2->assertStatus(200);
        $response2->assertJson(['message' => 'Already processed (infra)']);
    }

    /** @test */
    public function it_opens_circuit_breaker_after_consecutive_failures()
    {
        $creator = User::factory()->create(['role' => 'createur']);
        $profile = \App\Models\CreatorProfile::factory()->create(['user_id' => $creator->id]);
        \App\Models\PaymentPreference::factory()->create([
            'creator_profile_id' => $profile->id,
            'payment_connection_status' => 'connected',
            'momo_provider' => 'monetbil',
            'momo_api_key' => 'test_momo_key'
        ]);

        $order = Order::factory()->create(['creator_id' => $creator->id]);
        $transaction = PaymentTransaction::factory()->create([
            'order_id' => $order->id,
            'payment_ref' => 'fail_ref',
            'status' => 'pending'
        ]);

        // Provoquer une erreur dans le traitement (en supprimant la transaction juste avant par exemple)
        $payload = [
            'payment_ref' => 'fail_ref',
            'status' => 'success',
            'sign' => 'fake'
        ];

        // Mock failure logic or just use a ref that doesn't exist to cause error 
        // Wait, if transaction not found it returns 404, not an exception that triggers Circuit Breaker recorded failure in my implementation?
        // Let's re-check MonetbilController.
        // It records failure in catch(\Exception $e).
        
        // I will force an exception by mocking the DB transaction or something?
        // Or simply, since I use the RetryService, I can mock the handler? No, the handler is anonymous.

        // Actually, if I delete the transaction after validation but before processing, it might fail.
        
        // Let's use a simpler way: recordFailure manually to test the breaker
        $breaker = app(CircuitBreakerService::class);
        for ($i = 0; $i < 6; $i++) {
            $breaker->recordFailure('monetbil', 'Simulated failure');
        }

        // Now the circuit should be OPEN
        $response = $this->postJson('/payment/monetbil/notify', $payload);
        $response->assertStatus(503);
        $response->assertJson(['status' => 'circuit_open']);
    }

    /** @test */
    public function it_records_webhook_failure_in_database()
    {
        // 1. Setup Data with a missing order to trigger skip or error
        $creator = User::factory()->create(['role' => 'createur']);
        $profile = \App\Models\CreatorProfile::factory()->create(['user_id' => $creator->id]);
        \App\Models\PaymentPreference::factory()->create([
            'creator_profile_id' => $profile->id,
            'payment_connection_status' => 'connected',
            'momo_provider' => 'monetbil',
            'momo_api_key' => 'test_momo_key'
        ]);

        // Create a valid order then delete it to cause issues or just keep it but force another error
        $order = Order::factory()->create(['creator_id' => $creator->id]);
        
        $transaction = PaymentTransaction::factory()->create([
            'payment_ref' => 'ref_failure',
            'order_id' => $order->id,
            'status' => 'pending'
        ]);

        // Delete order to trigger OrderNotFound or null pointer if we didn't check
        $order->delete();

        $payload = [
            'payment_ref' => 'ref_failure',
            'status' => 'success',
            'sign' => 'fake'
        ];

        // 2. Request - should fail during processing but return 200 (DLQ handled)
        $response = $this->postJson('/payment/monetbil/notify', $payload);
        
        // Controller returns 200 because retryService handles DLQ and we don't want provider to retry
        $response->assertStatus(200);

        // 3. Verify it was recorded in webhook_failures
        $this->assertDatabaseHas('webhook_failures', [
            'provider' => 'monetbil',
            'external_id' => 'ref_failure'
        ]);
        
        $failure = \App\Models\WebhookFailure::where('external_id', 'ref_failure')->first();
        $this->assertNotNull($failure->error_message);
    }
}

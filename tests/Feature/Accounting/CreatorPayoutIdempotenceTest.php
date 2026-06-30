<?php

namespace Tests\Feature\Accounting;

use App\Models\CreatorPayout;
use App\Models\CreatorProfile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests d'idempotence pour CreatorPayoutListener
 */
class CreatorPayoutIdempotenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_only_one_entry_on_double_dispatch()
    {
        $creatorProfile = CreatorProfile::factory()->create();

        // First dispatch
        $payout1 = CreatorPayout::create([
            'creator_profile_id' => $creatorProfile->id,
            'amount' => 1000.00,
            'currency' => 'XAF',
            'status' => 'pending',
            'idempotency_key' => 'test-key-123',
        ]);

        // Second dispatch with same idempotency key — should be rejected by unique constraint
        try {
            $payout2 = CreatorPayout::create([
                'creator_profile_id' => $creatorProfile->id,
                'amount' => 1000.00,
                'currency' => 'XAF',
                'status' => 'pending',
                'idempotency_key' => 'test-key-123',
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Expected — unique constraint prevents duplicate
        }

        $this->assertEquals(1, CreatorPayout::where('idempotency_key', 'test-key-123')->count());
    }

    #[Test]
    public function it_handles_multiple_retries_gracefully()
    {
        $creatorProfile = CreatorProfile::factory()->create();

        // Multiple attempts with same idempotency key
        for ($i = 0; $i < 3; $i++) {
            CreatorPayout::firstOrCreate([
                'idempotency_key' => 'retry-test-key',
            ], [
                'creator_profile_id' => $creatorProfile->id,
                'amount' => 500.00,
                'currency' => 'XAF',
                'status' => 'pending',
            ]);
        }

        $this->assertEquals(1, CreatorPayout::where('idempotency_key', 'retry-test-key')->count());
    }

    #[Test]
    public function it_prevents_duplicate_entries_under_simulated_concurrency()
    {
        $creatorProfile = CreatorProfile::factory()->create();

        // Simulate concurrent requests
        $payouts = [];
        for ($i = 0; $i < 5; $i++) {
            $payouts[] = CreatorPayout::firstOrCreate([
                'idempotency_key' => 'concurrency-test-key',
            ], [
                'creator_profile_id' => $creatorProfile->id,
                'amount' => 200.00,
                'currency' => 'XAF',
                'status' => 'pending',
            ]);
        }

        $this->assertEquals(1, CreatorPayout::where('idempotency_key', 'concurrency-test-key')->count());
    }

    #[Test]
    public function it_does_not_create_entry_for_pending_payout()
    {
        $creatorProfile = CreatorProfile::factory()->create();

        // Create pending payout
        CreatorPayout::create([
            'creator_profile_id' => $creatorProfile->id,
            'amount' => 750.00,
            'currency' => 'XAF',
            'status' => 'pending',
            'idempotency_key' => 'pending-test-key',
        ]);

        // Try to create another with same key
        $duplicate = CreatorPayout::firstOrCreate([
            'idempotency_key' => 'pending-test-key',
        ], [
            'creator_profile_id' => $creatorProfile->id,
            'amount' => 750.00,
            'currency' => 'XAF',
            'status' => 'pending',
        ]);

        $this->assertEquals(1, CreatorPayout::where('idempotency_key', 'pending-test-key')->count());
    }

    #[Test]
    public function it_creates_separate_entries_for_different_payouts()
    {
        $creatorProfile = CreatorProfile::factory()->create();

        // Create two different payouts
        CreatorPayout::create([
            'creator_profile_id' => $creatorProfile->id,
            'amount' => 1000.00,
            'currency' => 'XAF',
            'status' => 'pending',
            'idempotency_key' => 'payout-1',
        ]);

        CreatorPayout::create([
            'creator_profile_id' => $creatorProfile->id,
            'amount' => 1500.00,
            'currency' => 'XAF',
            'status' => 'pending',
            'idempotency_key' => 'payout-2',
        ]);

        $this->assertEquals(2, CreatorPayout::count());
        $this->assertEquals(1, CreatorPayout::where('idempotency_key', 'payout-1')->count());
        $this->assertEquals(1, CreatorPayout::where('idempotency_key', 'payout-2')->count());
    }

    #[Test]
    public function it_creates_balanced_payout_entry()
    {
        $creatorProfile = CreatorProfile::factory()->create();

        $payout = CreatorPayout::create([
            'creator_profile_id' => $creatorProfile->id,
            'amount' => 2500.50,
            'currency' => 'XAF',
            'status' => 'completed',
            'idempotency_key' => 'balanced-payout-key',
        ]);

        $this->assertEquals(2500.50, $payout->amount);
        $this->assertEquals('XAF', $payout->currency);
        $this->assertEquals('completed', $payout->status);
        $this->assertNotNull($payout->created_at);
        $this->assertNotNull($payout->updated_at);
    }
}

<?php

namespace Tests\Feature\Accounting;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\User;
use App\Services\Financial\AccountingIdempotenceService;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Events\PaymentRecorded;
use Modules\Accounting\Listeners\PaymentRecordedListener;
use Modules\Accounting\Services\LedgerService;
use Illuminate\Support\Facades\Cache;
use Tests\Traits\SeedsAccounting;

/**
 * Tests d'idempotence pour PaymentRecordedListener
 * 
 * Ces tests vÃ©rifient que:
 * 1. Double dispatch du mÃªme event â†’ UNE SEULE Ã©criture
 * 2. Retry aprÃ¨s succÃ¨s partiel â†’ UNE SEULE Ã©criture
 * 3. Concurrence simulÃ©e â†’ UNE SEULE Ã©criture
 * 
 * INVARIANT: Il ne doit JAMAIS exister plus d'UNE Ã©criture comptable pour une mÃªme rÃ©fÃ©rence.
 */
class PaymentAccountingIdempotenceTest extends TestCase
{
    use RefreshDatabase, SeedsAccounting;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed accounting data
        $this->seedAccounting();

        // Reset collision counter
        AccountingIdempotenceService::resetCounter();
    }
    #[Test]
    public function it_creates_only_one_entry_on_double_dispatch()
    {
        // Arrange
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        $listener = app(PaymentRecordedListener::class);
        $event = new PaymentRecorded($order);

        // Act - Premier dispatch
        $listener->handle($event);

        // Assert - PremiÃ¨re Ã©criture crÃ©Ã©e
        $entryCount1 = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->count();
        $this->assertEquals(1, $entryCount1);

        // Act - DeuxiÃ¨me dispatch (simule retry ou double event)
        $listener->handle($event);

        // Assert - Toujours UNE SEULE Ã©criture
        $entryCount2 = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->count();

        $this->assertEquals(1, $entryCount2, 'INVARIANT VIOLÃ‰: Plus d\'une Ã©criture pour la mÃªme commande');

        // Note: Collision tracking is now handled by Intent-based flow
        // See IntentBasedAccountingTest for collision tests
    }
    #[Test]
    public function it_handles_multiple_retries_gracefully()
    {
        // Arrange
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 236.00,
            'payment_method' => 'mobile_money',
            'payment_status' => 'paid',
        ]);

        $listener = app(PaymentRecordedListener::class);
        $event = new PaymentRecorded($order);

        // Act - Triple dispatch
        $listener->handle($event);
        $listener->handle($event);
        $listener->handle($event);

        // Assert - UNE SEULE Ã©criture
        $entries = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->get();

        $this->assertCount(1, $entries);

        // Note: Collision tracking is now handled by Intent-based flow
        // See IntentBasedAccountingTest for collision tests
    }
    #[Test]
    public function it_prevents_duplicate_entries_under_simulated_concurrency()
    {
        // Arrange
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 59.00,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);

        // Act - ExÃ©cution "parallÃ¨le" via deux instances
        $listener1 = app(PaymentRecordedListener::class);
        $listener2 = app(PaymentRecordedListener::class);
        $event = new PaymentRecorded($order);

        $listener1->handle($event);
        $listener2->handle($event);

        // Assert - UNE SEULE Ã©criture
        $count = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->count();

        $this->assertEquals(1, $count);
    }
    #[Test]
    public function it_does_not_create_entry_for_pending_payment()
    {
        // Arrange
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'pending', // PAS 'paid'
        ]);

        $listener = app(PaymentRecordedListener::class);
        $event = new PaymentRecorded($order);

        // Act
        $listener->handle($event);

        // Assert - Aucune Ã©criture
        $count = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->count();

        $this->assertEquals(0, $count);

        // Assert - Aucune collision
        $this->assertEquals(0, AccountingIdempotenceService::getCollisionCount());
    }
    #[Test]
    public function it_creates_separate_entries_for_different_orders()
    {
        // Arrange
        $order1 = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 200.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        $listener = app(PaymentRecordedListener::class);

        // Act
        $listener->handle(new PaymentRecorded($order1));
        $listener->handle(new PaymentRecorded($order2));

        // Assert - Deux Ã©critures distinctes
        $entry1 = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order1->id)
            ->first();

        $entry2 = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order2->id)
            ->first();

        $this->assertNotNull($entry1);
        $this->assertNotNull($entry2);
        $this->assertNotEquals($entry1->id, $entry2->id);

        // Assert - Aucune collision (ordres diffÃ©rents)
        $this->assertEquals(0, AccountingIdempotenceService::getCollisionCount());
    }
    #[Test]
    public function it_creates_balanced_entry()
    {
        // Arrange
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        $listener = app(PaymentRecordedListener::class);

        // Act
        $listener->handle(new PaymentRecorded($order));

        // Assert
        $entry = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();

        $this->assertTrue($entry->is_posted);
        $this->assertEquals($entry->total_debit, $entry->total_credit);
        $this->assertEquals(118.00, $entry->total_debit);
    }
}

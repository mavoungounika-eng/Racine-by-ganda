<?php

namespace Tests\Feature\Accounting;

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

/**
 * Tests d'idempotence pour PaymentRecordedListener
 * 
 * Ces tests vérifient que:
 * 1. Double dispatch du même event → UNE SEULE écriture
 * 2. Retry après succès partiel → UNE SEULE écriture
 * 3. Concurrence simulée → UNE SEULE écriture
 * 
 * INVARIANT: Il ne doit JAMAIS exister plus d'UNE écriture comptable pour une même référence.
 */
class PaymentAccountingIdempotenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed accounting data
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);

        // Reset collision counter
        AccountingIdempotenceService::resetCounter();
    }

    /**
     * @test
     * SCÉNARIO: Double dispatch du même event
     * ATTENDU: Une seule écriture comptable créée
     */
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

        // Assert - Première écriture créée
        $entryCount1 = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->count();
        $this->assertEquals(1, $entryCount1);

        // Act - Deuxième dispatch (simule retry ou double event)
        $listener->handle($event);

        // Assert - Toujours UNE SEULE écriture
        $entryCount2 = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->count();

        $this->assertEquals(1, $entryCount2, 'INVARIANT VIOLÉ: Plus d\'une écriture pour la même commande');

        // Note: Collision tracking is now handled by Intent-based flow
        // See IntentBasedAccountingTest for collision tests
    }

    /**
     * @test
     * SCÉNARIO: Trois dispatches consécutifs (simule multiples retries)
     * ATTENDU: Une seule écriture, deux collisions enregistrées
     */
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

        // Assert - UNE SEULE écriture
        $entries = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->get();

        $this->assertCount(1, $entries);

        // Note: Collision tracking is now handled by Intent-based flow
        // See IntentBasedAccountingTest for collision tests
    }

    /**
     * @test
     * SCÉNARIO: Concurrence simulée (deux appels "parallèles")
     * ATTENDU: Une seule écriture grâce au guard + contrainte DB
     */
    public function it_prevents_duplicate_entries_under_simulated_concurrency()
    {
        // Arrange
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 59.00,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);

        // Act - Exécution "parallèle" via deux instances
        $listener1 = app(PaymentRecordedListener::class);
        $listener2 = app(PaymentRecordedListener::class);
        $event = new PaymentRecorded($order);

        $listener1->handle($event);
        $listener2->handle($event);

        // Assert - UNE SEULE écriture
        $count = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * @test
     * SCÉNARIO: Paiement non confirmé ne crée pas d'écriture
     * ATTENDU: Zéro écriture, zéro collision
     */
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

        // Assert - Aucune écriture
        $count = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->count();

        $this->assertEquals(0, $count);

        // Assert - Aucune collision
        $this->assertEquals(0, AccountingIdempotenceService::getCollisionCount());
    }

    /**
     * @test
     * SCÉNARIO: Plusieurs commandes différentes
     * ATTENDU: Chaque commande a sa propre écriture unique
     */
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

        // Assert - Deux écritures distinctes
        $entry1 = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order1->id)
            ->first();

        $entry2 = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order2->id)
            ->first();

        $this->assertNotNull($entry1);
        $this->assertNotNull($entry2);
        $this->assertNotEquals($entry1->id, $entry2->id);

        // Assert - Aucune collision (ordres différents)
        $this->assertEquals(0, AccountingIdempotenceService::getCollisionCount());
    }

    /**
     * @test
     * SCÉNARIO: Vérifier équilibre de l'écriture créée
     * ATTENDU: total_debit = total_credit
     */
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

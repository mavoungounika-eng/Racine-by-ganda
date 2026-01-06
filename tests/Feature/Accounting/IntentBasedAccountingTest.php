<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\User;
use App\Models\FinancialIntent;
use App\Services\Financial\FinancialIntentService;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Exceptions\LedgerException;

/**
 * Tests pour l'architecture Intent-Based
 * 
 * Ces tests vérifient que:
 * 1. Intent requis pour création d'écriture
 * 2. Double commit idempotent
 * 3. Status transitions correctes
 */
class IntentBasedAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected FinancialIntentService $intentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed accounting data
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);

        $this->intentService = app(FinancialIntentService::class);
    }

    /**
     * @test
     * RÈGLE: Un intent peut être créé pour une commande
     */
    public function it_creates_payment_intent_for_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        $intent = $this->intentService->createPaymentIntent($order);

        $this->assertInstanceOf(FinancialIntent::class, $intent);
        $this->assertEquals('order', $intent->reference_type);
        $this->assertEquals($order->id, $intent->reference_id);
        $this->assertEquals(FinancialIntent::STATUS_PENDING, $intent->status);
        $this->assertEquals(118.00, $intent->amount);
    }

    /**
     * @test
     * RÈGLE: Créer un intent pour la même commande retourne l'existant (idempotent)
     */
    public function it_returns_existing_intent_on_duplicate_creation()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 236.00,
            'payment_method' => 'mobile_money',
            'payment_status' => 'paid',
        ]);

        $intent1 = $this->intentService->createPaymentIntent($order);
        $intent2 = $this->intentService->createPaymentIntent($order);

        $this->assertEquals($intent1->id, $intent2->id);
        
        // Vérifier qu'il n'y a qu'un seul intent
        $count = FinancialIntent::forReference('order', $order->id)->count();
        $this->assertEquals(1, $count);
    }

    /**
     * @test
     * RÈGLE: Commiter un intent crée une écriture comptable
     */
    public function it_creates_accounting_entry_on_commit()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        $intent = $this->intentService->createPaymentIntent($order);
        
        $ledgerService = app(LedgerService::class);
        
        $entry = $this->intentService->commitIntent($intent, function ($intent, $ledger) use ($order) {
            return $ledger->createSaleEntry(
                order: $order,
                journalCode: 'VTE',
                debitAccount: '5112',
                creditAccount: '7011',
                totalTTC: $order->total_amount,
                vatRate: 18.0
            );
        });

        // Vérifier écriture créée
        $this->assertInstanceOf(AccountingEntry::class, $entry);
        $this->assertTrue($entry->is_posted);

        // Vérifier intent commis
        $intent->refresh();
        $this->assertEquals(FinancialIntent::STATUS_COMMITTED, $intent->status);
        $this->assertEquals($entry->id, $intent->accounting_entry_id);
        $this->assertNotNull($intent->committed_at);
    }

    /**
     * @test
     * RÈGLE: Double commit retourne l'écriture existante (idempotent)
     */
    public function it_returns_existing_entry_on_double_commit()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 59.00,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);

        $intent = $this->intentService->createPaymentIntent($order);
        
        $entryCreator = function ($intent, $ledger) use ($order) {
            return $ledger->createSaleEntry(
                order: $order,
                journalCode: 'VTE',
                debitAccount: '5700',
                creditAccount: '7011',
                totalTTC: $order->total_amount,
                vatRate: 18.0
            );
        };

        // Premier commit
        $entry1 = $this->intentService->commitIntent($intent, $entryCreator);
        
        // Rafraîchir l'intent
        $intent->refresh();
        
        // Deuxième commit (doit retourner la même écriture)
        $entry2 = $this->intentService->commitIntent($intent, $entryCreator);

        $this->assertEquals($entry1->id, $entry2->id);

        // Vérifier qu'il n'y a qu'une seule écriture
        $count = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->count();
        $this->assertEquals(1, $count);
    }

    /**
     * @test
     * RÈGLE: Intent commis ne peut pas être re-traité
     */
    public function committed_intent_cannot_be_processed()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        $intent = $this->intentService->createPaymentIntent($order);
        
        // Marquer comme commis manuellement
        $intent->update(['status' => FinancialIntent::STATUS_COMMITTED]);

        $this->assertFalse($intent->canProcess());
    }

    /**
     * @test
     * RÈGLE: L'idempotency_key est un hash unique
     */
    public function it_generates_unique_idempotency_key()
    {
        $key1 = FinancialIntent::generateIdempotencyKey('order', 1);
        $key2 = FinancialIntent::generateIdempotencyKey('order', 2);
        $key3 = FinancialIntent::generateIdempotencyKey('payout', 1);

        // Clés différentes pour références différentes
        $this->assertNotEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);

        // Même clé pour même référence
        $key1bis = FinancialIntent::generateIdempotencyKey('order', 1);
        $this->assertEquals($key1, $key1bis);
    }
}

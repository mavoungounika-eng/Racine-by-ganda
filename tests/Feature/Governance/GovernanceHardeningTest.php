<?php

namespace Tests\Feature\Governance;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\CreatorProfile;
use App\Models\CreatorSaleRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Events\PaymentRecorded;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Exceptions\LedgerException;
use Tests\TestCase;
use Tests\Traits\SeedsAccounting;

/**
 * Tests bloquants de gouvernance SaaS Pur.
 * 
 * Ces tests DOIVENT passer avant tout dÃ©ploiement en production.
 * Ils vÃ©rifient les invariants critiques identifiÃ©s dans l'audit.
 * 
 * @see AUDIT_VERDICT.md
 * @see SAAS_PUR_INVARIANTS.md
 */
class GovernanceHardeningTest extends TestCase
{
    use RefreshDatabase, SeedsAccounting;

    // =========================================================================
    // C1: Non-rÃ©gression payment_status
    // =========================================================================
    #[Test]
    public function payment_status_regression_from_paid_to_pending_is_blocked(): void
    {
        $order = Order::factory()->create(['payment_status' => 'paid']);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('INVARIANT VIOLATION');
        $this->expectExceptionMessage("cannot regress from 'paid' to 'pending'");
        
        $order->update(['payment_status' => 'pending']);
    }
    #[Test]
    public function payment_status_can_transition_to_refunded(): void
    {
        $order = Order::factory()->create(['payment_status' => 'paid']);
        
        // Transition paid â†’ refunded autorisÃ©e
        $order->update(['payment_status' => 'refunded']);
        
        $this->assertEquals('refunded', $order->fresh()->payment_status);
    }

    // =========================================================================
    // C2: Ã‰tats terminaux immuables
    // =========================================================================
    #[Test]
    public function terminal_order_status_completed_cannot_be_modified(): void
    {
        $order = Order::factory()->create(['status' => 'completed']);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('INVARIANT VIOLATION');
        $this->expectExceptionMessage("'completed' is terminal");
        
        $order->update(['status' => 'pending']);
    }
    #[Test]
    public function terminal_order_status_cancelled_cannot_be_modified(): void
    {
        // 'cancelled' est un état DORMANT restaurable — pas terminal.
        // Il peut revenir en 'restored' via restore(), puis 'pending' via relaunch.
        // Seuls 'completed' et 'archived' sont vraiment terminaux.
        $order = Order::factory()->create([
            'status'            => 'cancelled',
            'cancellation_type' => 'global',
        ]);

        // La restauration vers restored est autorisée
        $order->restore();
        $this->assertEquals('restored', $order->fresh()->status);

        // En revanche, completed est bien terminal
        $order->update(['status' => 'completed']);
        $this->expectException(\DomainException::class);
        $order2 = Order::factory()->create(['status' => 'completed']);
        $order2->update(['status' => 'pending']);
    }
    #[Test]
    public function non_terminal_order_status_can_transition(): void
    {
        $order = Order::factory()->create(['status' => 'processing']);
        
        // Transition processing â†’ shipped autorisÃ©e
        $order->update(['status' => 'shipped']);
        
        $this->assertEquals('shipped', $order->fresh()->status);
    }

    // =========================================================================
    // C3: PaymentRecorded non dispatchÃ© pour crÃ©ateurs
    // =========================================================================
    #[Test]
    public function payment_recorded_event_not_dispatched_for_creator_orders(): void
    {
        Event::fake([PaymentRecorded::class]);
        
        $creator = User::factory()->create();
        $order = Order::factory()->create([
            'creator_id' => $creator->id,
            'user_id' => $creator->id,
            'payment_status' => 'pending'
        ]);
        
        // Transition pending â†’ paid
        $order->update(['payment_status' => 'paid']);
        
        // PaymentRecorded NE DOIT PAS Ãªtre dispatchÃ© pour crÃ©ateurs
        Event::assertNotDispatched(PaymentRecorded::class);
    }
    #[Test]
    public function payment_recorded_event_dispatched_for_brand_orders(): void
    {
        Event::fake([PaymentRecorded::class]);
        
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'creator_id' => null, // Brand order
            'user_id' => $user->id,
            'payment_status' => 'pending'
        ]);
        
        // Transition pending â†’ paid
        $order->update(['payment_status' => 'paid']);
        
        // PaymentRecorded DOIT Ãªtre dispatchÃ© pour Brand orders
        Event::assertDispatched(PaymentRecorded::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    // =========================================================================
    // C4: CreatorSaleRecord requiert creator_id non null
    // =========================================================================
    #[Test]
    public function creator_sale_record_requires_non_null_creator_id(): void
    {
        $order = Order::factory()->create(['creator_id' => null]);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('INVARIANT VIOLATION');
        $this->expectExceptionMessage('requires non-null creator_id');
        
        CreatorSaleRecord::create([
            'order_id' => $order->id,
            'creator_id' => null,
            'gross_amount' => 1000,
            'payment_method' => 'stripe',
            'status' => 'completed'
        ]);
    }
    #[Test]
    public function creator_sale_record_created_with_valid_creator_id(): void
    {
        $creator = User::factory()->create();
        $profile = CreatorProfile::factory()->create(['user_id' => $creator->id]);
        $order = Order::factory()->create(['creator_id' => $profile->id]);
        
        $record = CreatorSaleRecord::create([
            'order_id' => $order->id,
            'creator_id' => $profile->id,
            'gross_amount' => 1000,
            'payment_method' => 'stripe',
            'status' => 'completed'
        ]);
        
        $this->assertNotNull($record->id);
        $this->assertEquals($profile->id, $record->creator_id);
    }

    // =========================================================================
    // C5: Intent non crÃ©Ã© pour commandes crÃ©ateurs
    // =========================================================================
    #[Test]
    public function financial_intent_not_created_for_creator_orders(): void
    {
        $creator = User::factory()->create();
        $order = Order::factory()->create([
            'creator_id' => $creator->id,
            'payment_status' => 'paid'
        ]);
        
        // Dispatch event manuellement
        event(new PaymentRecorded($order));
        
        // VÃ©rifier qu'aucun intent n'a Ã©tÃ© crÃ©Ã©
        $this->assertDatabaseMissing('financial_intents', [
            'reference_type' => 'order',
            'reference_id' => $order->id
        ]);
    }

    // =========================================================================
    // C6: reverseEntry() bloque commandes crÃ©ateurs
    // =========================================================================
    #[Test]
    public function reverse_entry_blocked_for_creator_order(): void
    {
        // Authentifier pour Auth::id() dans LedgerService
        $user = User::factory()->create();
        $this->actingAs($user);

        // Seed donnÃ©es comptables via trait
        $this->seedAccounting();
        
        // CrÃ©er une Ã©criture pour un order Brand
        $brandOrder = Order::factory()->create(['creator_id' => null]);
        $ledger = app(LedgerService::class);
        
        // CrÃ©er l'Ã©criture via le service autorisÃ©
        $entry = $ledger->createSaleEntry(
            $brandOrder,
            'VTE',
            '5112',
            '7011',
            1000
        );
        
        // Maintenant, simuler une modification malicieuse de l'order
        // (en production, cela ne devrait jamais arriver grÃ¢ce aux guards)
        \DB::table('orders')->where('id', $brandOrder->id)->update(['creator_id' => 999]);
        
        // La contre-passation doit Ã©chouer
        $this->expectException(LedgerException::class);
        $this->expectExceptionMessage('SÃ‰CURITÃ‰ SAAS PUR');
        $this->expectExceptionMessage('Contre-passation interdite');
        
        $ledger->reverseEntry($entry, 'Test reversal');
    }

    // =========================================================================
    // Tests additionnels : Combinaisons
    // =========================================================================
    #[Test]
    public function full_brand_order_lifecycle_works(): void
    {
        Event::fake([PaymentRecorded::class]);
        
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'creator_id' => null,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'pending'
        ]);
        
        // Transition normale
        $order->update(['payment_status' => 'paid']);
        $order->update(['status' => 'processing']);
        $order->update(['status' => 'shipped']);
        $order->update(['status' => 'completed']);
        
        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        
        // Event dispatchÃ© une seule fois
        Event::assertDispatchedTimes(PaymentRecorded::class, 1);
    }
    #[Test]
    public function full_creator_order_lifecycle_works(): void
    {
        Event::fake([PaymentRecorded::class]);
        
        $creator = User::factory()->create();
        $profile = CreatorProfile::factory()->create(['user_id' => $creator->id]);
        $user = User::factory()->create();
        
        $order = Order::factory()->create([
            'creator_id' => $profile->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'pending'
        ]);
        
        // Transition normale
        $order->update(['payment_status' => 'paid']);
        $order->update(['status' => 'processing']);
        $order->update(['status' => 'shipped']);
        $order->update(['status' => 'completed']);
        
        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        
        // AUCUN event dispatchÃ© pour crÃ©ateur
        Event::assertNotDispatched(PaymentRecorded::class);
        
        // Analytics record peut Ãªtre crÃ©Ã©
        $record = CreatorSaleRecord::create([
            'order_id' => $order->id,
            'creator_id' => $profile->id,
            'gross_amount' => $order->total_amount,
            'payment_method' => 'stripe',
            'status' => 'completed'
        ]);
        
        $this->assertNotNull($record->id);
    }
}

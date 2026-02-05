<?php

namespace Tests\Feature\Governance;

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
 * Ces tests DOIVENT passer avant tout déploiement en production.
 * Ils vérifient les invariants critiques identifiés dans l'audit.
 * 
 * @see AUDIT_VERDICT.md
 * @see SAAS_PUR_INVARIANTS.md
 */
class GovernanceHardeningTest extends TestCase
{
    use RefreshDatabase, SeedsAccounting;

    // =========================================================================
    // C1: Non-régression payment_status
    // =========================================================================

    /** @test */
    public function payment_status_regression_from_paid_to_pending_is_blocked(): void
    {
        $order = Order::factory()->create(['payment_status' => 'paid']);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('INVARIANT VIOLATION');
        $this->expectExceptionMessage("cannot regress from 'paid' to 'pending'");
        
        $order->update(['payment_status' => 'pending']);
    }

    /** @test */
    public function payment_status_can_transition_to_refunded(): void
    {
        $order = Order::factory()->create(['payment_status' => 'paid']);
        
        // Transition paid → refunded autorisée
        $order->update(['payment_status' => 'refunded']);
        
        $this->assertEquals('refunded', $order->fresh()->payment_status);
    }

    // =========================================================================
    // C2: États terminaux immuables
    // =========================================================================

    /** @test */
    public function terminal_order_status_completed_cannot_be_modified(): void
    {
        $order = Order::factory()->create(['status' => 'completed']);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('INVARIANT VIOLATION');
        $this->expectExceptionMessage("'completed' is terminal");
        
        $order->update(['status' => 'pending']);
    }

    /** @test */
    public function terminal_order_status_cancelled_cannot_be_modified(): void
    {
        $order = Order::factory()->create(['status' => 'cancelled']);
        
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('INVARIANT VIOLATION');
        $this->expectExceptionMessage("'cancelled' is terminal");
        
        $order->update(['status' => 'processing']);
    }

    /** @test */
    public function non_terminal_order_status_can_transition(): void
    {
        $order = Order::factory()->create(['status' => 'processing']);
        
        // Transition processing → shipped autorisée
        $order->update(['status' => 'shipped']);
        
        $this->assertEquals('shipped', $order->fresh()->status);
    }

    // =========================================================================
    // C3: PaymentRecorded non dispatché pour créateurs
    // =========================================================================

    /** @test */
    public function payment_recorded_event_not_dispatched_for_creator_orders(): void
    {
        Event::fake([PaymentRecorded::class]);
        
        $creator = User::factory()->create();
        $order = Order::factory()->create([
            'creator_id' => $creator->id,
            'user_id' => $creator->id,
            'payment_status' => 'pending'
        ]);
        
        // Transition pending → paid
        $order->update(['payment_status' => 'paid']);
        
        // PaymentRecorded NE DOIT PAS être dispatché pour créateurs
        Event::assertNotDispatched(PaymentRecorded::class);
    }

    /** @test */
    public function payment_recorded_event_dispatched_for_brand_orders(): void
    {
        Event::fake([PaymentRecorded::class]);
        
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'creator_id' => null, // Brand order
            'user_id' => $user->id,
            'payment_status' => 'pending'
        ]);
        
        // Transition pending → paid
        $order->update(['payment_status' => 'paid']);
        
        // PaymentRecorded DOIT être dispatché pour Brand orders
        Event::assertDispatched(PaymentRecorded::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    // =========================================================================
    // C4: CreatorSaleRecord requiert creator_id non null
    // =========================================================================

    /** @test */
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

    /** @test */
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
    // C5: Intent non créé pour commandes créateurs
    // =========================================================================

    /** @test */
    public function financial_intent_not_created_for_creator_orders(): void
    {
        $creator = User::factory()->create();
        $order = Order::factory()->create([
            'creator_id' => $creator->id,
            'payment_status' => 'paid'
        ]);
        
        // Dispatch event manuellement
        event(new PaymentRecorded($order));
        
        // Vérifier qu'aucun intent n'a été créé
        $this->assertDatabaseMissing('financial_intents', [
            'reference_type' => 'order',
            'reference_id' => $order->id
        ]);
    }

    // =========================================================================
    // C6: reverseEntry() bloque commandes créateurs
    // =========================================================================

    /** @test */
    public function reverse_entry_blocked_for_creator_order(): void
    {
        // Seed données comptables via trait
        $this->seedAccounting();
        
        // Créer une écriture pour un order Brand
        $brandOrder = Order::factory()->create(['creator_id' => null]);
        $ledger = app(LedgerService::class);
        
        // Créer l'écriture via le service autorisé
        $entry = $ledger->createSaleEntry(
            $brandOrder,
            'VTE',
            '5112',
            '7011',
            1000
        );
        
        // Maintenant, simuler une modification malicieuse de l'order
        // (en production, cela ne devrait jamais arriver grâce aux guards)
        \DB::table('orders')->where('id', $brandOrder->id)->update(['creator_id' => 999]);
        
        // La contre-passation doit échouer
        $this->expectException(LedgerException::class);
        $this->expectExceptionMessage('SÉCURITÉ SAAS PUR');
        $this->expectExceptionMessage('Contre-passation interdite');
        
        $ledger->reverseEntry($entry, 'Test reversal');
    }

    // =========================================================================
    // Tests additionnels : Combinaisons
    // =========================================================================

    /** @test */
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
        
        // Event dispatché une seule fois
        Event::assertDispatchedTimes(PaymentRecorded::class, 1);
    }

    /** @test */
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
        
        // AUCUN event dispatché pour créateur
        Event::assertNotDispatched(PaymentRecorded::class);
        
        // Analytics record peut être créé
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

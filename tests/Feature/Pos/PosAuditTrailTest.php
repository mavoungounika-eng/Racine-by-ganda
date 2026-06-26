<?php

namespace Tests\Feature\Pos;

use App\Models\PosOperatorAuditLog;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\User;
use App\Services\Pos\PosSaleService;
use App\Services\Pos\PosSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\Traits\SeedsAccounting;

class PosAuditTrailTest extends TestCase
{
    use RefreshDatabase, SeedsAccounting;

    protected User $operator;
    protected string $machineId;
    protected PosSessionService $sessionService;
    protected PosSaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed accounting data (required for bootstrap check in closeSession)
        $this->seedAccounting();
        $this->artisan('db:seed', ['--class' => 'AccountingBootstrapSeeder']);
        
        $this->operator = User::factory()->create();
        // Optionnel : s'assurer que c'est un user autorisé (si nécessaire dans l'avenir)
        // $this->operator->assignRole('staff'); 

        $this->machineId = 'test-machine-audit';
        $this->sessionService = app(PosSessionService::class);
        $this->saleService = app(PosSaleService::class);

        // Désactiver les évènements de clôture complexes qui tentent d'écrire en Compta (AccountingNotBootstrappedException)
        Event::fake([
            \App\Events\PosSessionClosed::class,
            \App\Events\PosCardPaymentConfirmed::class,
            \App\Events\PosMobilePaymentConfirmed::class,
        ]);
    }

    public function test_log_ouverture_session()
    {
        // Simulation de connexion de l'opérateur (pour les logs applicatifs request()->user())
        $this->actingAs($this->operator);

        $session = $this->sessionService->openSession($this->machineId, $this->operator->id, 100.00);

        // Vérification de la DB
        $this->assertDatabaseHas('pos_operator_audit_logs', [
            'action' => PosOperatorAuditLog::ACTION_SESSION_OPEN,
            'user_id' => $this->operator->id,
            'pos_session_id' => $session->id,
        ]);
    }

    public function test_log_cloture_session()
    {
        $this->actingAs($this->operator);

        $session = $this->sessionService->openSession($this->machineId, $this->operator->id, 100.00);
        $this->sessionService->prepareClose($session);
        $this->sessionService->closeSession($session, 100.00, $this->operator->id);

        $this->assertDatabaseHas('pos_operator_audit_logs', [
            'action' => PosOperatorAuditLog::ACTION_SESSION_CLOSE,
            'user_id' => $this->operator->id,
            'pos_session_id' => $session->id,
        ]);
    }

    public function test_log_vente()
    {
        $this->actingAs($this->operator);

        $session = $this->sessionService->openSession($this->machineId, $this->operator->id, 100.00);
        
        // Produit de la marque (RACINE)
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 50,
            'product_type' => 'brand'
        ]);

        $sale = $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $product->id, 'quantity' => 1]],
            PosSale::PAYMENT_CASH,
            $this->operator->id
        );

        $this->assertDatabaseHas('pos_operator_audit_logs', [
            'action' => PosOperatorAuditLog::ACTION_SALE_CREATED,
            'user_id' => $this->operator->id,
            'pos_session_id' => $session->id,
        ]);
    }

    public function test_log_annulation()
    {
        $this->actingAs($this->operator);

        $session = $this->sessionService->openSession($this->machineId, $this->operator->id, 100.00);
        
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 50,
            'product_type' => 'brand'
        ]);

        $sale = $this->saleService->createSale(
            $this->machineId,
            [['product_id' => $product->id, 'quantity' => 1]],
            PosSale::PAYMENT_CARD, // pending
            $this->operator->id
        );

        // Annulation
        $this->saleService->cancelSale($sale, $this->operator->id, "Customer changed mind");

        $this->assertDatabaseHas('pos_operator_audit_logs', [
            'action' => PosOperatorAuditLog::ACTION_SALE_CANCELLED,
            'user_id' => $this->operator->id,
            'pos_session_id' => $session->id,
        ]);
    }

    public function test_audit_ne_bloque_pas_la_vente_si_db_fail()
    {
        $this->actingAs($this->operator);

        $session = $this->sessionService->openSession($this->machineId, $this->operator->id, 100.00);
        
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 50,
            'product_type' => 'brand'
        ]);

        // Simuler une erreur de base de données uniquement sur la table d'audit
        // En renommant temporairement la table pour forcer une exception SQL (ou en droppant)
        // Note : RefreshDatabase remettra tout d'équerre au procahain test.
        Schema::rename('pos_operator_audit_logs', 'pos_operator_audit_logs_broken');

        $exceptionThrown = false;
        try {
            $sale = $this->saleService->createSale(
                $this->machineId,
                [['product_id' => $product->id, 'quantity' => 1]],
                PosSale::PAYMENT_CASH,
                $this->operator->id
            );
            $saleCreated = true;
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $saleCreated = false;
        }

        // Restaurer
        Schema::rename('pos_operator_audit_logs_broken', 'pos_operator_audit_logs');

        // La vente doit avoir réussi malgré l'erreur d'audit (car catchée dans le trait)
        $this->assertTrue($saleCreated);
        $this->assertFalse($exceptionThrown);
        $this->assertDatabaseHas('pos_sales', ['id' => $sale->id]);
    }
}

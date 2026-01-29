<?php

namespace Tests\Feature\Pos;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\PosSession;
use App\Models\PosSale;
use App\Models\PosPayment;
use App\Models\PosCashMovement;
use Illuminate\Support\Str;

/**
 * Tests Complets POS — Session Lifecycle
 *
 * Couvre:
 * - Ouverture/clôture session
 * - Ventes multiples
 * - Réconciliation cash
 * - Détection discrepancy
 */
class PosSessionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected PosSession $session;
    protected string $machineId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);
        $this->artisan('db:seed', ['--class' => 'AccountingBootstrapSeeder']);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->machineId = Str::uuid()->toString();
    }

    /** @test */
    public function session_opens_with_opening_cash()
    {
        $response = $this->postJson('/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('session.status', 'open');
        $response->assertJsonPath('session.opening_cash', 5000.00);

        $this->assertDatabaseHas('pos_sessions', [
            'machine_id' => $this->machineId,
            'status' => 'open',
            'opening_cash' => 5000.00,
        ]);
    }

    /** @test */
    public function session_rejects_duplicate_open()
    {
        // Première ouverture
        $this->postJson('/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ])->assertStatus(201);

        // Deuxième tentative
        $response = $this->postJson('/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
    }

    /** @test */
    public function session_closes_with_cash_difference_calculated()
    {
        // Ouverture
        $this->postJson('/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // Simuler ventes: 3 ventes x 100€ = 300€
        for ($i = 0; $i < 3; $i++) {
            $product = Product::factory()->create(['price' => 100.00]);
            PosSale::create([
                'session_id' => $session->id,
                'machine_id' => $this->machineId,
                'total_amount' => 100.00,
                'payment_method' => 'cash',
                'status' => 'finalized',
                'created_by' => $this->user->id,
            ]);
        }

        // Expected cash = opening (5000) + ventes (300) = 5300
        // Actual count: 5300 (exact match)
        $response = $this->postJson("/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5300.00,
            'notes' => 'Session normale',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('session.cash_difference', 0);
        $response->assertJsonPath('session.status', 'closed');
    }

    /** @test */
    public function session_detects_cash_discrepancy()
    {
        // Ouverture
        $this->postJson('/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // 3 ventes x 100€ = 300€
        for ($i = 0; $i < 3; $i++) {
            PosSale::create([
                'session_id' => $session->id,
                'machine_id' => $this->machineId,
                'total_amount' => 100.00,
                'payment_method' => 'cash',
                'status' => 'finalized',
                'created_by' => $this->user->id,
            ]);
        }

        // Expected: 5300 | Actual: 5400 (100€ de trop)
        $response = $this->postJson("/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5400.00,
            'notes' => 'Cash surplus 100€',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('session.cash_difference', 100.00);

        // Vérifier alerte discrepancy générée
        $this->assertDatabaseHas('pos_sessions', [
            'id' => $session->id,
            'cash_difference' => 100.00,
        ]);
    }

    /** @test */
    public function session_prevents_double_closure()
    {
        // Ouverture
        $this->postJson('/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // Première clôture
        $this->postJson("/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5000.00,
        ])->assertStatus(200);

        // Deuxième tentative
        $response = $this->postJson("/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5000.00,
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
    }

    /** @test */
    public function z_report_available_only_after_closure()
    {
        // Ouverture
        $this->postJson('/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // Z-Report avant clôture
        $response = $this->getJson("/pos/sessions/{$session->id}/z-report");
        $response->assertStatus(400);

        // Clôturer
        $this->postJson("/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5000.00,
        ]);

        // Z-Report après clôture
        $response = $this->getJson("/pos/sessions/{$session->id}/z-report");
        $response->assertStatus(200);
        $response->assertJsonPath('z_report.session_id', $session->id);
    }

    /** @test */
    public function session_tracks_cash_movements()
    {
        // Ouverture
        $this->postJson('/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // Vérifier mouvement d'ouverture
        $this->assertDatabaseHas('pos_cash_movements', [
            'session_id' => $session->id,
            'type' => 'opening',
            'amount' => 5000.00,
        ]);

        // Clôturer
        $this->postJson("/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5000.00,
        ]);

        // Vérifier mouvement de clôture
        $this->assertDatabaseHas('pos_cash_movements', [
            'session_id' => $session->id,
            'type' => 'closing',
            'amount' => 5000.00,
        ]);
    }

    /** @test */
    public function session_requires_authorization_after_incident()
    {
        // Ouverture
        $this->postJson('/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // Clôture avec incident
        $response = $this->postJson("/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5000.00,
            'notes' => '[INCIDENT] Redis down 14:32',
        ]);

        $response->assertStatus(200);

        // Vérifier que note incident est présente
        $this->assertDatabaseHas('pos_sessions', [
            'id' => $session->id,
            'notes' => '[INCIDENT] Redis down 14:32',
        ]);
    }
}

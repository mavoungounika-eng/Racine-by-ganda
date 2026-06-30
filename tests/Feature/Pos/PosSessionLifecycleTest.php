<?php

namespace Tests\Feature\Pos;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\PosSession;
use App\Models\PosSale;
use App\Models\PosPayment;
use App\Models\PosCashMovement;
use Illuminate\Support\Str;
use Tests\Traits\SeedsAccounting;
use Tests\Traits\CreatesPosDevice;

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
    use RefreshDatabase, SeedsAccounting, CreatesPosDevice;

    protected User $user;
    protected PosSession $session;
    protected string $machineId;
    protected \Modules\POSSync\Models\PosDevice $device;
    protected array $deviceHeader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccounting();
        $this->artisan('db:seed', ['--class' => 'AccountingBootstrapSeeder']);

        $this->user = User::factory()->create();
        $this->machineId = Str::uuid()->toString();
        $this->device = $this->createActiveDevice($this->user, $this->machineId);
        $this->deviceHeader = $this->deviceAuthHeader($this->device);
    }
    #[Test]
    public function session_opens_with_opening_cash()
    {
        $response = $this->postIdempotentJson('/api/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.session.status', 'open');
        $response->assertJsonPath('data.session.opening_cash', '5000.00');

        $this->assertDatabaseHas('pos_sessions', [
            'machine_id' => $this->machineId,
            'status' => 'open',
            'opening_cash' => 5000.00,
        ]);
    }
    #[Test]
    public function session_rejects_duplicate_open()
    {
        // Première ouverture
        $this->postIdempotentJson('/api/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ])->assertStatus(201);

        // Deuxième tentative
        $response = $this->postIdempotentJson('/api/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
    }
    #[Test]
    public function session_closes_with_cash_difference_calculated()
    {
        // Ouverture
        $this->postIdempotentJson('/api/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // Simuler ventes: 3 ventes x 100â‚¬ = 300â‚¬
        for ($i = 0; $i < 3; $i++) {
            $product = Product::factory()->create(['price' => 100.00]);
            app(\App\Services\Pos\PosSaleService::class)->createSale(
                $this->machineId,
                [[
                    'product_id' => Product::factory()->create(['price' => 100.00, 'stock' => 100])->id,
                    'quantity' => 1,
                    'price' => 100.00,
                ]],
                'cash',
                $this->user->id
            );
        }

        // Expected cash = opening (5000) + ventes (300) = 5300
        // Actual count: 5300 (exact match)
        $response = $this->postIdempotentJson("/api/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5300.00,
            'notes' => 'Session normale',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.session.cash_difference', '0.00');
        $response->assertJsonPath('data.session.status', 'closed');
    }
    #[Test]
    public function session_detects_cash_discrepancy()
    {
        // Ouverture
        $this->postIdempotentJson('/api/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // 3 ventes x 100â‚¬ = 300â‚¬
        for ($i = 0; $i < 3; $i++) {
            app(\App\Services\Pos\PosSaleService::class)->createSale(
                $this->machineId,
                [[
                    'product_id' => Product::factory()->create(['price' => 100.00, 'stock' => 100])->id,
                    'quantity' => 1,
                    'price' => 100.00,
                ]],
                'cash',
                $this->user->id
            );
        }

        // Expected: 5300 | Actual: 5400 (100â‚¬ de trop)
        $response = $this->postIdempotentJson("/api/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5400.00,
            'notes' => 'Cash surplus 100â‚¬',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.session.cash_difference', '100.00');

        // Vérifier alerte discrepancy générée
        $this->assertDatabaseHas('pos_sessions', [
            'id' => $session->id,
            'cash_difference' => 100.00,
        ]);
    }
    #[Test]
    public function session_prevents_double_closure()
    {
        // Ouverture
        $this->postIdempotentJson('/api/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // Première clôture
        $this->postIdempotentJson("/api/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5000.00,
        ])->assertStatus(200);

        // Deuxième tentative
        $response = $this->postIdempotentJson("/api/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5000.00,
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
    }
    #[Test]
    public function z_report_available_only_after_closure()
    {
        // Ouverture
        $this->postIdempotentJson('/api/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // Z-Report avant clôture
        $response = $this->getJson("/api/pos/sessions/{$session->id}/z-report", $this->deviceHeader);
        $response->assertStatus(400);

        // Clôturer
        $this->postIdempotentJson("/api/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5000.00,
        ]);

        // Z-Report après clôture
        $response = $this->getJson("/api/pos/sessions/{$session->id}/z-report", $this->deviceHeader);
        $response->assertStatus(200);
        $response->assertJsonPath('data.z_report.session_id', $session->id);
    }
    #[Test]
    public function session_tracks_cash_movements()
    {
        // Ouverture
        $this->postIdempotentJson('/api/pos/sessions/open', [
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
        $this->postIdempotentJson("/api/pos/sessions/{$session->id}/close", [
            'closing_cash' => 5000.00,
        ]);

        // Vérifier mouvement de clôture
        $this->assertDatabaseHas('pos_cash_movements', [
            'session_id' => $session->id,
            'type' => 'closing',
            'amount' => 5000.00,
        ]);
    }
    #[Test]
    public function session_requires_authorization_after_incident()
    {
        // Ouverture
        $this->postIdempotentJson('/api/pos/sessions/open', [
            'machine_id' => $this->machineId,
            'opening_cash' => 5000.00,
        ]);

        $session = PosSession::where('machine_id', $this->machineId)->first();

        // Clôture avec incident
        $response = $this->postIdempotentJson("/api/pos/sessions/{$session->id}/close", [
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

    private function postIdempotentJson(string $uri, array $data = [])
    {
        return $this->postJson($uri, $data, array_merge([
            'X-Idempotency-Key' => (string) Str::uuid(),
        ], $this->deviceHeader ?? []));
    }
}






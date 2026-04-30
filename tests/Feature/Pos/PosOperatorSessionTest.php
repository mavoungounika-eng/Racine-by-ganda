<?php

namespace Tests\Feature\Pos;

use App\Models\PosSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\POSSync\Models\PosDevice;
use Modules\POSSync\Services\DeviceAuthService;
use Tests\TestCase;
use Tests\Traits\SeedsAccounting;

/**
 * Regression suite pour le flux dual-auth POS au niveau HTTP:
 *   Authorization: Bearer <device-token>   (JWT appareil)
 *   X-Operator-Token: <sanctum-token>       (token opérateur)
 *
 * Ces tests verrouillent précisément le bug identifié par le testeur E2E:
 * les stores Pinia (session/cart/products) oubliaient de propager
 * X-Operator-Token, donc /api/pos/sessions/open répondait 400
 * POS_USER_REQUIRED alors que l opérateur était bien loggué.
 *
 * Les tests unitaires du service contournent la middleware HTTP; seule une
 * feature de bout-en-bout peut garantir que:
 *   - le middleware PosDeviceAuth lit l en-tête X-Operator-Token,
 *   - attache $request->posOperator,
 *   - et que le controller utilise posOperator->id pour opened_by.
 */
class PosOperatorSessionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAccounting;

    protected function setUp(): void
    {
        parent::setUp();

        // Le close session déclenche PosSessionClosedListener → FinancialIntent
        // → AccountingBootstrapService qui exige un exercice fiscal actif.
        // On seed le socle comptable pour que le path complet puisse s exécuter.
        $this->seedAccounting();
        $this->artisan('db:seed', ['--class' => 'AccountingBootstrapSeeder']);

        // Queue sync: le listener doit tourner inline dans le test HTTP.
        config(['queue.default' => 'sync']);
    }

    private function operator(string $roleSlug = 'staff'): User
    {
        Role::firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst($roleSlug), 'slug' => $roleSlug]
        );

        return User::factory()->create([
            'role' => $roleSlug,
            'role_id' => Role::where('slug', $roleSlug)->value('id'),
        ]);
    }

    private function deviceWithoutUserMetadata(): PosDevice
    {
        // Important: metadata SANS user_id — c est le cas production où
        // l appareil est partagé entre plusieurs caissiers; l identité
        // opérateur DOIT venir du header X-Operator-Token.
        return PosDevice::create([
            'machine_id' => (string) Str::uuid(),
            'name' => 'POS-Shared-Counter',
            'machine_secret' => 'secret-key',
            'status' => 'active',
            'metadata' => [],
        ]);
    }

    private function deviceToken(PosDevice $device): string
    {
        return app(DeviceAuthService::class)->generateToken($device->machine_id);
    }

    private function operatorToken(User $user): string
    {
        return $user->createToken('pos-operator', ['pos:operate'])->plainTextToken;
    }

    public function test_open_session_succeeds_with_device_bearer_plus_operator_token(): void
    {
        $operator = $this->operator();
        $device = $this->deviceWithoutUserMetadata();

        $response = $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 5000.00,
        ], [
            'Authorization' => 'Bearer ' . $this->deviceToken($device),
            'X-Operator-Token' => $this->operatorToken($operator),
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.session.machine_id', $device->machine_id);
        $response->assertJsonPath('data.session.status', 'open');

        $this->assertDatabaseHas('pos_sessions', [
            'machine_id' => $device->machine_id,
            'opened_by' => $operator->id,
            'status' => PosSession::STATUS_OPEN,
            'is_active' => 1,
        ]);
    }

    public function test_open_session_fails_without_operator_token_when_device_has_no_user_metadata(): void
    {
        // Régression directe du bug tester E2E: sans X-Operator-Token et sans
        // metadata.user_id côté device, le controller DOIT renvoyer 400
        // POS_USER_REQUIRED (pas 500, pas 201 avec opened_by=null).
        $device = $this->deviceWithoutUserMetadata();

        $response = $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 5000.00,
        ], [
            'Authorization' => 'Bearer ' . $this->deviceToken($device),
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'POS_USER_REQUIRED');
    }

    public function test_open_session_rejects_invalid_operator_token(): void
    {
        $device = $this->deviceWithoutUserMetadata();

        $response = $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 5000.00,
        ], [
            'Authorization' => 'Bearer ' . $this->deviceToken($device),
            'X-Operator-Token' => 'totally-invalid-token',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_open_session_rejects_operator_token_without_pos_scope(): void
    {
        $user = $this->operator();
        $tokenWithoutScope = $user->createToken('some-other-app', [])->plainTextToken;
        $device = $this->deviceWithoutUserMetadata();

        $response = $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 5000.00,
        ], [
            'Authorization' => 'Bearer ' . $this->deviceToken($device),
            'X-Operator-Token' => $tokenWithoutScope,
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_create_sale_uses_operator_identity_from_header(): void
    {
        $operator = $this->operator();
        $device = $this->deviceWithoutUserMetadata();
        $headers = [
            'Authorization' => 'Bearer ' . $this->deviceToken($device),
            'X-Operator-Token' => $this->operatorToken($operator),
        ];

        $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 1000.00,
        ], $headers)->assertStatus(201);

        $product = \App\Models\Product::factory()->create(['price' => 100.00, 'stock' => 50]);

        $response = $this->postJson('/api/pos/sales', [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 100.00,
            ]],
            'payment_method' => 'cash',
        ], $headers);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.sale.payment_method', 'cash');

        $saleId = $response->json('data.sale.id');
        $this->assertDatabaseHas('pos_sales', [
            'id' => $saleId,
            'created_by' => $operator->id,
        ]);
    }

    public function test_close_session_uses_correct_route_with_session_id_in_path(): void
    {
        // Régression: le testeur E2E a rapporté un 404 sur /api/pos/sessions/close.
        // La route correcte est /api/pos/sessions/{session}/close — ce test fige
        // la signature pour prévenir toute future régression de routing.
        $operator = $this->operator();
        $device = $this->deviceWithoutUserMetadata();
        $headers = [
            'Authorization' => 'Bearer ' . $this->deviceToken($device),
            'X-Operator-Token' => $this->operatorToken($operator),
        ];

        $openResponse = $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 5000.00,
        ], $headers);
        $openResponse->assertStatus(201);
        $sessionId = $openResponse->json('data.session.id');

        // La vieille forme sans ID doit rester 404 (confirme que le testeur
        // tapait simplement la mauvaise URL).
        $this->postJson('/api/pos/sessions/close', [], $headers)->assertStatus(404);

        // La bonne route accepte la clôture.
        $response = $this->postJson("/api/pos/sessions/{$sessionId}/close", [
            'closing_cash' => 5000.00,
        ], $headers);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('pos_sessions', [
            'id' => $sessionId,
            'status' => PosSession::STATUS_CLOSED,
            'closed_by' => $operator->id,
        ]);
    }

    public function test_open_session_fails_with_clear_error_if_operator_already_active_on_another_machine(): void
    {
        // Deuxième couche de la régression: le check préventif cross-machine
        // ajouté dans PosSessionService doit remonter jusqu au client HTTP
        // (pas de 500 cryptique sur la contrainte DB uq_user_active_session).
        $operator = $this->operator();
        $opToken = $this->operatorToken($operator);

        $deviceA = $this->deviceWithoutUserMetadata();
        $deviceB = $this->deviceWithoutUserMetadata();

        $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 5000.00,
        ], [
            'Authorization' => 'Bearer ' . $this->deviceToken($deviceA),
            'X-Operator-Token' => $opToken,
        ])->assertStatus(201);

        $response = $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 3000.00,
        ], [
            'Authorization' => 'Bearer ' . $this->deviceToken($deviceB),
            'X-Operator-Token' => $opToken,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'SESSION_OPEN_FAILED');
        $this->assertStringContainsString('autre machine', $response->json('error.message'));
    }
}

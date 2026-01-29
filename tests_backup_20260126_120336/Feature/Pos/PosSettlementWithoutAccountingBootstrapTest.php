<?php

namespace Tests\Feature\Pos;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\PosSession;
use App\Models\FinancialIntent;
use App\Services\Pos\PosSessionService;
use App\Exceptions\Accounting\AccountingNotBootstrappedException;
use Illuminate\Support\Str;

/**
 * Test critique: Empêcher settlement POS sans bootstrap comptable
 * 
 * RÈGLE: Aucune clôture ne doit créer d'intent si l'environnement comptable n'est pas prêt
 */
class PosSettlementWithoutAccountingBootstrapTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected PosSessionService $sessionService;

    protected function setUp(): void
    {
        parent::setUp();

        // NE PAS seed les données comptables (c'est le test)
        // Créer uniquement l'utilisateur
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->sessionService = app(PosSessionService::class);
    }

    /**
     * @test
     * CRITIQUE: Bloquer clôture si bootstrap comptable manquant
     */
    public function it_blocks_settlement_without_accounting_bootstrap()
    {
        // Ouvrir session POS
        $session = $this->sessionService->openSession(
            Str::uuid()->toString(),
            $this->user->id,
            50000
        );

        $this->assertEquals(PosSession::STATUS_OPEN, $session->status);

        // Tenter clôture (doit échouer)
        $this->expectException(AccountingNotBootstrappedException::class);
        $this->expectExceptionMessage('Accounting environment not bootstrapped. POS settlement blocked');

        $this->sessionService->closeSession($session, 50000, $this->user->id);
    }

    /**
     * @test
     * CRITIQUE: Vérifier que session reste OPEN après échec
     */
    public function it_keeps_session_open_after_bootstrap_failure()
    {
        $session = $this->sessionService->openSession(
            Str::uuid()->toString(),
            $this->user->id,
            50000
        );

        try {
            $this->sessionService->closeSession($session, 50000, $this->user->id);
        } catch (AccountingNotBootstrappedException $e) {
            // Exception attendue
        }

        // Vérifier que session est toujours OPEN
        $session->refresh();
        $this->assertEquals(PosSession::STATUS_OPEN, $session->status);
    }

    /**
     * @test
     * CRITIQUE: Vérifier qu'aucun FinancialIntent n'est créé
     */
    public function it_creates_no_intent_without_bootstrap()
    {
        $session = $this->sessionService->openSession(
            Str::uuid()->toString(),
            $this->user->id,
            50000
        );

        try {
            $this->sessionService->closeSession($session, 50000, $this->user->id);
        } catch (AccountingNotBootstrappedException $e) {
            // Exception attendue
        }

        // Vérifier qu'aucun intent n'a été créé
        $intentCount = FinancialIntent::where('reference_type', 'pos_session')
            ->where('reference_id', $session->id)
            ->count();

        $this->assertEquals(0, $intentCount, 'Aucun FinancialIntent ne doit être créé sans bootstrap');
    }
}

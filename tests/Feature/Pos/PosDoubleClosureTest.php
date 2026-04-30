<?php

namespace Tests\Feature\Pos;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\PosSession;
use App\Services\Pos\PosSessionService;
use Illuminate\Support\Str;
use Tests\Traits\SeedsAccounting;

/**
 * Test critique: Empêcher double clôture de session
 * 
 * CORRECTION 1: Verrou transactionnel
 */
class PosDoubleClosureTest extends TestCase
{
    use RefreshDatabase, SeedsAccounting;

    protected User $user;
    protected PosSessionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccounting();
        $this->artisan('db:seed', ['--class' => 'AccountingBootstrapSeeder']);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->service = app(PosSessionService::class);
    }
    #[Test]
    public function it_prevents_double_session_closure()
    {
        // Créer et ouvrir une session
        $session = $this->service->openSession(
            Str::uuid()->toString(),
            $this->user->id,
            50000
        );

        // Première clôture (OK)
        $closedSession = $this->service->closeSession($session, 100000, $this->user->id);
        
        $this->assertEquals(PosSession::STATUS_CLOSED, $closedSession->status);

        // Deuxième clôture (DOIT échouer)
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Session already closed');

        $this->service->closeSession($session, 100000, $this->user->id);
    }
    #[Test]
    public function it_prevents_concurrent_closure_attempts()
    {
        $session = $this->service->openSession(
            Str::uuid()->toString(),
            $this->user->id,
            50000
        );

        // Simuler tentative concurrente en rafraîchissant la session
        $session1 = PosSession::find($session->id);
        $session2 = PosSession::find($session->id);

        // Première clôture réussit
        $this->service->closeSession($session1, 100000, $this->user->id);

        // Deuxième clôture échoue (session déjà fermée)
        $this->expectException(\DomainException::class);
        $this->service->closeSession($session2, 100000, $this->user->id);
    }
}





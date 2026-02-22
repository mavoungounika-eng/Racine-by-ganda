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
 * Test critique: EmpÃƒÂªcher double clÃƒÂ´ture de session
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
        // CrÃƒÂ©er et ouvrir une session
        $session = $this->service->openSession(
            Str::uuid()->toString(),
            $this->user->id,
            50000
        );

        // PremiÃƒÂ¨re clÃƒÂ´ture (OK)
        $closedSession = $this->service->closeSession($session, 100000, $this->user->id);
        
        $this->assertEquals(PosSession::STATUS_CLOSED, $closedSession->status);

        // DeuxiÃƒÂ¨me clÃƒÂ´ture (DOIT ÃƒÂ©chouer)
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

        // Simuler tentative concurrente en rafraÃƒÂ®chissant la session
        $session1 = PosSession::find($session->id);
        $session2 = PosSession::find($session->id);

        // PremiÃƒÂ¨re clÃƒÂ´ture rÃƒÂ©ussit
        $this->service->closeSession($session1, 100000, $this->user->id);

        // DeuxiÃƒÂ¨me clÃƒÂ´ture ÃƒÂ©choue (session dÃƒÂ©jÃƒÂ  fermÃƒÂ©e)
        $this->expectException(\DomainException::class);
        $this->service->closeSession($session2, 100000, $this->user->id);
    }
}





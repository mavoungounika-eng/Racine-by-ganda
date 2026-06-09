<?php

namespace Tests\Feature\Audit;

use Tests\TestCase;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class AuditComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = app(AuditService::class);
    }

    /**
     * Test mapping of Request ID in logs
     */
    public function test_request_id_correlation()
    {
        $user = User::factory()->create();
        
        // Simuler une requête avec le middleware AssignRequestId
        $this->actingAs($user)->get('/api/health');
        $requestId = request()->get('_request_id');

        $this->auditService->log('test_action', 'Test', 1, $user, ['foo' => 'bar']);

        $log = AuditLog::latest()->first();
        $this->assertEquals($requestId, $log->request_id);
    }

    /**
     * Test PII masking in metadata
     */
    public function test_pii_masking()
    {
        $this->auditService->log('user_updated', 'User', 1, null, [
            'email' => 'admin@example.com',
            'password' => 'secret123',
            'card_number' => '1234-5678-9012-3456',
            'unrelated' => 'visible'
        ]);

        $log = AuditLog::latest()->first();
        
        $this->assertEquals('a***n@example.com', $log->metadata['email']);
        $this->assertEquals('[REDACTED]', $log->metadata['password']);
        $this->assertEquals('[REDACTED]', $log->metadata['card_number']);
        $this->assertEquals('visible', $log->metadata['unrelated']);
    }

    /**
     * Test hash chaining and integrity verification
     */
    public function test_audit_integrity_chaining()
    {
        // 1. Créer plusieurs logs
        $this->auditService->log('action_1', 'Test', 1);
        $this->auditService->log('action_2', 'Test', 2);
        $this->auditService->log('action_3', 'Test', 3);

        // 2. Vérifier via la commande Artisan (doit passer)
        $exitCode = Artisan::call('audit:verify');
        $this->assertEquals(0, $exitCode);

        // 3. Simuler une altération manuelle
        $tamperedLog = AuditLog::orderBy('id')->skip(1)->first();
        $tamperedLog->update(['metadata' => ['tampered' => 'true']]);

        // 4. Vérifier à nouveau (doit échouer)
        // Note: Comme on chaîne les hashes, l'erreur devrait être détectée à partir du log 2
        $exitCodeAfterTampering = Artisan::call('audit:verify');
        $this->assertEquals(1, $exitCodeAfterTampering);
    }
}

<?php

namespace Tests\Feature;

use App\Models\PaymentAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrunePaymentAuditLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que le dry-run ne supprime rien
     */
    public function test_prune_audit_logs_dry_run_does_not_delete_anything(): void
    {
        // Créer un utilisateur pour les tests
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role_id' => 1,
            ]
        );

        // Créer des logs anciens
        PaymentAuditLog::create([
            'user_id' => $user->id,
            'action' => 'provider.toggle',
            'target_type' => 'PaymentProvider',
            'target_id' => 1,
            'created_at' => now()->subDays(400),
        ]);

        $this->assertDatabaseCount('payment_audit_logs', 1);

        // Exécuter en dry-run
        $this->artisan('payments:prune-audit-logs --days=365 --dry-run')
            ->expectsOutput('Mode DRY-RUN : aucune suppression ne sera effectuée.')
            ->assertSuccessful();

        // Vérifier que rien n'a été supprimé
        $this->assertDatabaseCount('payment_audit_logs', 1);
    }

    /**
     * Test que la purge supprime bien les logs anciens
     */
    public function test_prune_audit_logs_deletes_old_logs(): void
    {
        // Créer un utilisateur pour les tests
        $user = User::firstOrCreate(
            ['email' => 'test_audit2@example.com'],
            [
                'name' => 'Test Audit User 2',
                'password' => bcrypt('password'),
                'role_id' => 1,
            ]
        );

        // Créer des logs anciens (> 365 jours)
        PaymentAuditLog::create([
            'user_id' => $user->id,
            'action' => 'provider.toggle',
            'target_type' => 'PaymentProvider',
            'target_id' => 1,
            'created_at' => now()->subDays(400),
        ]);

        // Créer des logs récents (< 365 jours)
        PaymentAuditLog::create([
            'user_id' => $user->id,
            'action' => 'provider.update',
            'target_type' => 'PaymentProvider',
            'target_id' => 1,
            'created_at' => now()->subDays(100),
        ]);

        $this->assertDatabaseCount('payment_audit_logs', 2);

        // Exécuter la purge
        $this->artisan('payments:prune-audit-logs --days=365')
            ->assertSuccessful();

        // Vérifier que seul l'ancien a été supprimé
        $this->assertDatabaseCount('payment_audit_logs', 1);
        $this->assertDatabaseHas('payment_audit_logs', ['action' => 'provider.update']);
    }
}





<?php

namespace Tests\Feature\AuditTrail;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AuditLog;

class GlobalAuditObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_creation_creates_audit_log()
    {
        $user = User::factory()->create([
            'email' => 'test_audit_create@example.com',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'entity_type' => 'User',
            'entity_id' => $user->id,
        ]);
        
        $log = AuditLog::where('entity_type', 'User')->where('entity_id', $user->id)->first();
        $this->assertNotNull($log->metadata['new_attributes']['email']);
    }

    public function test_model_update_creates_audit_log_with_changes()
    {
        $user = User::factory()->create(['email' => 'old_email@example.com']);
        AuditLog::truncate(); // Clear creation log

        $user->update(['email' => 'new_email@example.com']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'entity_type' => 'User',
            'entity_id' => $user->id,
        ]);

        $log = AuditLog::first();
        $this->assertStringContainsString('@', $log->metadata['old_attributes']['email']);
        $this->assertStringContainsString('@', $log->metadata['new_attributes']['email']);
    }

    public function test_model_deletion_creates_audit_log()
    {
        $user = User::factory()->create();
        AuditLog::truncate();

        $user->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'entity_type' => 'User',
            'entity_id' => $user->id,
        ]);
    }

    public function test_sensitive_attributes_are_hidden_in_audit_logs()
    {
        AuditLog::truncate();
        
        $user = User::factory()->create([
            'email' => 'secure_user@example.com',
            'password' => bcrypt('SuperSecretPassword123!'),
        ]);

        $log = AuditLog::where('action', 'created')
            ->where('entity_type', 'User')
            ->where('entity_id', $user->id)
            ->first();
        
        $this->assertNotNull($log);
        $this->assertEquals('[REDACTED]', $log->metadata['new_attributes']['password']);
        $this->assertStringContainsString('@', $log->metadata['new_attributes']['email']);
    }

    public function test_role_creation_is_audited()
    {
        AuditLog::truncate();
        
        $role = \App\Models\Role::firstOrCreate(
            ['slug' => 'test-audit-role'],
            ['name' => 'Audited Role', 'description' => 'Test role for audit trail']
        );

        $log = AuditLog::where('action', 'created')
            ->where('entity_type', 'Role')
            ->where('entity_id', $role->id)
            ->first();
            
        $this->assertNotNull($log);
        $this->assertEquals('Audited Role', $log->metadata['new_attributes']['name']);
    }
}

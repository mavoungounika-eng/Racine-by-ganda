<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests d'intégrité RBAC avec auth_version
 *
 * Valide que l'architecture auth_version actuelle fonctionne correctement:
 * - auth_version s'incrémente sur changements de rôle/permissions
 * - Les sessions sont invalidées via auth_version
 * - Pas de cache RBAC (architecture simplifiée)
 */
class RbacCacheIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'description' => 'Client role', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'description' => 'Admin role', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'description' => 'Staff role', 'is_active' => true]);

        $clientRole = Role::where('slug', 'client')->first();
        $this->user = User::factory()->create([
            'role_id' => $clientRole->id,
            'auth_version' => 1,
            'status' => 'active',
        ]);
    }

    /**
     * Test que auth_version s'incrémente après changement de rôle
     */
    public function test_auth_version_increments_after_role_change(): void
    {
        $oldVersion = $this->user->auth_version;

        $adminRole = Role::where('slug', 'admin')->first();
        $this->user->update(['role_id' => $adminRole->id]);

        $this->user->refresh();
        $this->assertGreaterThan($oldVersion, $this->user->auth_version);
    }

    /**
     * Test que auth_version s'incrémente après activation 2FA
     */
    public function test_auth_version_increments_after_2fa_enabled(): void
    {
        $oldVersion = $this->user->auth_version;

        $this->user->update([
            'two_factor_secret' => 'secret',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->user->refresh();
        $this->assertGreaterThan($oldVersion, $this->user->auth_version);
    }

    /**
     * Test que auth_version s'incrémente après changement de mot de passe
     */
    public function test_auth_version_increments_after_password_change(): void
    {
        $oldVersion = $this->user->auth_version;

        $this->user->update(['password' => bcrypt('newpassword')]);

        $this->user->refresh();
        $this->assertGreaterThan($oldVersion, $this->user->auth_version);
    }

    /**
     * Test que auth_version s'incrémente après changement de statut
     */
    public function test_auth_version_increments_after_status_change(): void
    {
        $oldVersion = $this->user->auth_version;

        $this->user->update(['status' => 'suspended']);

        $this->user->refresh();
        $this->assertGreaterThan($oldVersion, $this->user->auth_version);
    }

    /**
     * Test que auth_version reste stable pour changements non-sécurisés
     */
    public function test_auth_version_stable_for_non_security_changes(): void
    {
        $oldVersion = $this->user->auth_version;

        $this->user->update(['name' => 'New Name']);

        $this->user->refresh();
        $this->assertEquals($oldVersion, $this->user->auth_version);
    }

    /**
     * Test que auth_version est unique par utilisateur
     */
    public function test_auth_version_unique_per_user(): void
    {
        $user2 = User::factory()->create([
            'auth_version' => 1,
            'status' => 'active',
        ]);

        // Changer user1
        $this->user->update(['role_id' => Role::where('slug', 'admin')->first()->id]);
        $user1Version = $this->user->fresh()->auth_version;

        // Changer user2
        $user2->update(['role_id' => Role::where('slug', 'staff')->first()->id]);
        $user2Version = $user2->fresh()->auth_version;

        // Versions peuvent être identiques car indépendantes
        // Mais chaque user a sa propre séquence
        $this->assertIsInt($user1Version);
        $this->assertIsInt($user2Version);
        $this->assertGreaterThan(1, $user1Version);
        $this->assertGreaterThan(1, $user2Version);
    }

    /**
     * Test que auth_version commence à 1 pour nouveaux utilisateurs
     */
    public function test_auth_version_starts_at_one_for_new_users(): void
    {
        $newUser = User::factory()->create(['status' => 'active']);

        $this->assertEquals(1, $newUser->auth_version);
    }

    /**
     * Test que auth_version s'incrémente de 1 à chaque changement
     */
    public function test_auth_version_increments_by_one(): void
    {
        $initial = $this->user->auth_version;

        $this->user->update(['name' => 'Change 1']);
        $this->assertEquals($initial, $this->user->fresh()->auth_version); // Pas de changement

        $this->user->update(['role_id' => Role::where('slug', 'admin')->first()->id]);
        $this->assertEquals($initial + 1, $this->user->fresh()->auth_version);

        $this->user->update(['two_factor_secret' => 'secret']);
        $this->assertEquals($initial + 2, $this->user->fresh()->auth_version);
    }
}

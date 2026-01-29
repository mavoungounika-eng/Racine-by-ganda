<?php

namespace Tests\Unit\Auth;

use App\DTOs\Auth\UserContext;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * UserContext DTO Test
 * 
 * Tests the immutable UserContext DTO and its helper methods.
 */
class UserContextTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_all_properties()
    {
        $frozenAt = Carbon::now();
        
        $context = new UserContext(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
            role: 'admin',
            creatorStatus: null,
            permissions: ['view_dashboard', 'manage_users'],
            requires2FA: true,
            has2FAEnabled: true,
            authVersion: 1,
            frozenAt: $frozenAt,
        );

        $this->assertEquals(1, $context->userId);
        $this->assertEquals('test@example.com', $context->email);
        $this->assertEquals('Test User', $context->name);
        $this->assertEquals('admin', $context->role);
        $this->assertNull($context->creatorStatus);
        $this->assertEquals(['view_dashboard', 'manage_users'], $context->permissions);
        $this->assertTrue($context->requires2FA);
        $this->assertTrue($context->has2FAEnabled);
        $this->assertEquals(1, $context->authVersion);
        $this->assertEquals($frozenAt, $context->frozenAt);
    }

    /** @test */
    public function it_can_check_specific_role()
    {
        $context = $this->createContext(role: 'admin');

        $this->assertTrue($context->hasRole('admin'));
        $this->assertFalse($context->hasRole('client'));
    }

    /** @test */
    public function it_can_check_multiple_roles()
    {
        $context = $this->createContext(role: 'staff');

        $this->assertTrue($context->hasAnyRole(['admin', 'staff']));
        $this->assertFalse($context->hasAnyRole(['admin', 'client']));
    }

    /** @test */
    public function it_identifies_admin_roles()
    {
        $superAdmin = $this->createContext(role: 'super_admin');
        $admin = $this->createContext(role: 'admin');
        $staff = $this->createContext(role: 'staff');
        $client = $this->createContext(role: 'client');

        $this->assertTrue($superAdmin->isAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($staff->isAdmin());
        $this->assertFalse($client->isAdmin());
    }

    /** @test */
    public function it_identifies_team_members()
    {
        $superAdmin = $this->createContext(role: 'super_admin');
        $admin = $this->createContext(role: 'admin');
        $staff = $this->createContext(role: 'staff');
        $creator = $this->createContext(role: 'createur');
        $client = $this->createContext(role: 'client');

        $this->assertTrue($superAdmin->isTeamMember());
        $this->assertTrue($admin->isTeamMember());
        $this->assertTrue($staff->isTeamMember());
        $this->assertFalse($creator->isTeamMember());
        $this->assertFalse($client->isTeamMember());
    }

    /** @test */
    public function it_identifies_creators()
    {
        $createur = $this->createContext(role: 'createur');
        $creator = $this->createContext(role: 'createur');
        $client = $this->createContext(role: 'client');

        $this->assertTrue($createur->isCreator());
        $this->assertTrue($creator->isCreator());
        $this->assertFalse($client->isCreator());
    }

    /** @test */
    public function it_identifies_clients()
    {
        $client = $this->createContext(role: 'client');
        $admin = $this->createContext(role: 'admin');

        $this->assertTrue($client->isClient());
        $this->assertFalse($admin->isClient());
    }

    /** @test */
    public function it_identifies_creator_statuses()
    {
        $pending = $this->createContext(role: 'createur', creatorStatus: 'pending');
        $active = $this->createContext(role: 'createur', creatorStatus: 'active');
        $suspended = $this->createContext(role: 'createur', creatorStatus: 'suspended');
        $client = $this->createContext(role: 'client', creatorStatus: null);

        $this->assertTrue($pending->isCreatorPending());
        $this->assertFalse($pending->isCreatorActive());
        $this->assertFalse($pending->isCreatorSuspended());

        $this->assertTrue($active->isCreatorActive());
        $this->assertFalse($active->isCreatorPending());
        $this->assertFalse($active->isCreatorSuspended());

        $this->assertTrue($suspended->isCreatorSuspended());
        $this->assertFalse($suspended->isCreatorPending());
        $this->assertFalse($suspended->isCreatorActive());

        $this->assertFalse($client->isCreatorPending());
        $this->assertFalse($client->isCreatorActive());
        $this->assertFalse($client->isCreatorSuspended());
    }

    /** @test */
    public function it_can_check_permissions()
    {
        $context = $this->createContext(permissions: ['view_dashboard', 'manage_users']);

        $this->assertTrue($context->hasPermission('view_dashboard'));
        $this->assertTrue($context->hasPermission('manage_users'));
        $this->assertFalse($context->hasPermission('delete_users'));
    }

    /** @test */
    public function it_can_convert_to_array()
    {
        $frozenAt = Carbon::now();
        $context = new UserContext(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
            role: 'admin',
            creatorStatus: null,
            permissions: ['view_dashboard'],
            requires2FA: true,
            has2FAEnabled: true,
            authVersion: 1,
            frozenAt: $frozenAt,
        );

        $array = $context->toArray();

        $this->assertEquals([
            'user_id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'role' => 'admin',
            'creator_status' => null,
            'permissions' => ['view_dashboard'],
            'requires_2fa' => true,
            'has_2fa_enabled' => true,
            'auth_version' => 1,
            'frozen_at' => $frozenAt->toIso8601String(),
        ], $array);
    }

    /** @test */
    public function it_can_be_created_from_array()
    {
        $frozenAt = Carbon::now();
        $array = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'role' => 'admin',
            'creator_status' => 'active',
            'permissions' => ['view_dashboard'],
            'requires_2fa' => true,
            'has_2fa_enabled' => true,
            'auth_version' => 1,
            'frozen_at' => $frozenAt->toIso8601String(),
        ];

        $context = UserContext::fromArray($array);

        $this->assertEquals(1, $context->userId);
        $this->assertEquals('test@example.com', $context->email);
        $this->assertEquals('Test User', $context->name);
        $this->assertEquals('admin', $context->role);
        $this->assertEquals('active', $context->creatorStatus);
        $this->assertEquals(['view_dashboard'], $context->permissions);
        $this->assertTrue($context->requires2FA);
        $this->assertTrue($context->has2FAEnabled);
        $this->assertEquals(1, $context->authVersion);
        $this->assertEquals($frozenAt->toIso8601String(), $context->frozenAt->toIso8601String());
    }

    /** @test */
    public function it_round_trips_through_array_conversion()
    {
        $original = $this->createContext(
            role: 'createur',
            creatorStatus: 'active',
            permissions: ['view_dashboard', 'manage_products']
        );

        $array = $original->toArray();
        $restored = UserContext::fromArray($array);

        $this->assertEquals($original->userId, $restored->userId);
        $this->assertEquals($original->email, $restored->email);
        $this->assertEquals($original->name, $restored->name);
        $this->assertEquals($original->role, $restored->role);
        $this->assertEquals($original->creatorStatus, $restored->creatorStatus);
        $this->assertEquals($original->permissions, $restored->permissions);
        $this->assertEquals($original->requires2FA, $restored->requires2FA);
        $this->assertEquals($original->has2FAEnabled, $restored->has2FAEnabled);
        $this->assertEquals($original->authVersion, $restored->authVersion);
    }

    /**
     * Helper to create a UserContext with default values
     */
    private function createContext(
        int $userId = 1,
        string $email = 'test@example.com',
        string $name = 'Test User',
        string $role = 'client',
        ?string $creatorStatus = null,
        array $permissions = [],
        bool $requires2FA = false,
        bool $has2FAEnabled = false,
        ?int $authVersion = null,
    ): UserContext {
        return new UserContext(
            userId: $userId,
            email: $email,
            name: $name,
            role: $role,
            creatorStatus: $creatorStatus,
            permissions: $permissions,
            requires2FA: $requires2FA,
            has2FAEnabled: $has2FAEnabled,
            authVersion: $authVersion,
            frozenAt: Carbon::now(),
        );
    }
}

<?php

namespace App\DTOs\Auth;

use App\Models\User;
use Carbon\Carbon;

/**
 * User Context DTO
 * 
 * Represents a frozen snapshot of user authentication state.
 * This context is stored in session and used for all authorization decisions.
 * 
 * CRITICAL: This is the SINGLE SOURCE OF TRUTH for user state during a session.
 * Once frozen, it does NOT change unless session is invalidated.
 */
class UserContext
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $name,
        public readonly string $role,
        public readonly ?string $creatorStatus,
        public readonly array $permissions,
        public readonly bool $requires2FA,
        public readonly bool $has2FAEnabled,
        public readonly ?int $authVersion,
        public readonly Carbon $frozenAt,
    ) {}

    /**
     * Create UserContext from User model
     */
    public static function fromUser(User $user): self
    {
        // This will be implemented by UserContextResolver
        throw new \LogicException('Use UserContextResolver::resolve() instead');
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Check if user is admin (admin or super_admin)
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    /**
     * Check if user is team member (staff, admin, super_admin)
     */
    public function isTeamMember(): bool
    {
        return in_array($this->role, ['staff', 'admin', 'super_admin'], true);
    }

    /**
     * Check if user is creator
     */
    public function isCreator(): bool
    {
        return $this->role === 'createur' || $this->role === 'creator';
    }

    /**
     * Check if user is client
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Check if creator is pending
     */
    public function isCreatorPending(): bool
    {
        return $this->isCreator() && $this->creatorStatus === 'pending';
    }

    /**
     * Check if creator is active
     */
    public function isCreatorActive(): bool
    {
        return $this->isCreator() && $this->creatorStatus === 'active';
    }

    /**
     * Check if creator is suspended
     */
    public function isCreatorSuspended(): bool
    {
        return $this->isCreator() && $this->creatorStatus === 'suspended';
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    /**
     * Convert to array for session storage
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
            'creator_status' => $this->creatorStatus,
            'permissions' => $this->permissions,
            'requires_2fa' => $this->requires2FA,
            'has_2fa_enabled' => $this->has2FAEnabled,
            'auth_version' => $this->authVersion,
            'frozen_at' => $this->frozenAt->toIso8601String(),
        ];
    }

    /**
     * Create from array (session restoration)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            email: $data['email'],
            name: $data['name'],
            role: $data['role'],
            creatorStatus: $data['creator_status'] ?? null,
            permissions: $data['permissions'] ?? [],
            requires2FA: $data['requires_2fa'] ?? false,
            has2FAEnabled: $data['has_2fa_enabled'] ?? false,
            authVersion: $data['auth_version'] ?? null,
            frozenAt: Carbon::parse($data['frozen_at']),
        );
    }
}

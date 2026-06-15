<?php

namespace App\DTO\Auth;

use App\Models\Role;
use Carbon\Carbon;

/**
 * Immutable DTO representing the frozen authentication context in session.
 */
class UserContext
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $name,
        public readonly string $role,
        public readonly ?string $creatorStatus = null,
        public readonly array $permissions = [],
        public readonly bool $requires2FA = false,
        public readonly bool $has2FAEnabled = false,
        public readonly ?int $authVersion = null,
        public readonly ?int $activeCreatorId = null,
        public readonly ?string $creatorRole = null,
        public readonly Carbon $frozenAt = new Carbon(),
    ) {}

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN]);
    }

    public function isTeamMember(): bool
    {
        return $this->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN, Role::STAFF]);
    }

    public function isCreator(): bool
    {
        return $this->hasAnyRole(['createur']);
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isCreatorPending(): bool
    {
        return $this->isCreator() && $this->creatorStatus === 'pending';
    }

    public function isCreatorActive(): bool
    {
        return $this->isCreator() && $this->creatorStatus === 'active';
    }

    public function isCreatorSuspended(): bool
    {
        return $this->isCreator() && $this->creatorStatus === 'suspended';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === Role::SUPER_ADMIN) {
            return true;
        }

        return in_array($permission, $this->permissions, true);
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
            'creator_status' => $this->creatorStatus,
            'permissions' => array_values($this->permissions),
            'requires_2fa' => $this->requires2FA,
            'has_2fa_enabled' => $this->has2FAEnabled,
            'auth_version' => $this->authVersion,
            'active_creator_id' => $this->activeCreatorId,
            'creator_role' => $this->creatorRole,
            'frozen_at' => $this->frozenAt->toIso8601String(),
        ];
    }

    public static function fromArray(array $data): self
    {
        $userId = (int) ($data['user_id'] ?? $data['id'] ?? 0);

        return new self(
            userId: $userId,
            email: (string) ($data['email'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            role: (string) ($data['role'] ?? 'client'),
            creatorStatus: $data['creator_status'] ?? $data['creatorStatus'] ?? null,
            permissions: is_array($data['permissions'] ?? null) ? $data['permissions'] : [],
            requires2FA: (bool) ($data['requires_2fa'] ?? $data['requires2FA'] ?? false),
            has2FAEnabled: (bool) ($data['has_2fa_enabled'] ?? $data['has2FAEnabled'] ?? false),
            authVersion: isset($data['auth_version']) || isset($data['authVersion'])
                ? (int) ($data['auth_version'] ?? $data['authVersion'])
                : null,
            activeCreatorId: isset($data['active_creator_id']) || isset($data['activeCreatorId'])
                ? (int) ($data['active_creator_id'] ?? $data['activeCreatorId'])
                : null,
            creatorRole: $data['creator_role'] ?? $data['creatorRole'] ?? null,
            frozenAt: !empty($data['frozen_at'] ?? $data['frozenAt'])
                ? Carbon::parse($data['frozen_at'] ?? $data['frozenAt'])
                : now(),
        );
    }
}

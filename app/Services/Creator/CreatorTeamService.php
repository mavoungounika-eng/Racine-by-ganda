<?php

namespace App\Services\Creator;

use App\Models\CreatorInvitation;
use App\Models\CreatorMember;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreatorTeamService
{
    /**
     * Inviter un nouvel utilisateur dans l'équipe
     */
    public function invite(CreatorProfile $creator, string $email, string $role, User $inviter): CreatorInvitation
    {
        return CreatorInvitation::create([
            'creator_profile_id' => $creator->id,
            'email' => $email,
            'role' => $role,
            'invited_by' => $inviter->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Accepter une invitation
     */
    public function acceptInvitation(string $token, User $user): bool
    {
        $invitation = CreatorInvitation::where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return false;
        }

        if ($invitation->email !== $user->email) {
            Log::warning('[MultiAccount] Email mismatch during invitation acceptance', [
                'user_id' => $user->id,
                'invitation_id' => $invitation->id,
                'user_email' => $user->email,
                'invite_email' => $invitation->email,
            ]);
            return false;
        }

        return DB::transaction(function () use ($invitation, $user) {
            // 1. Créer le membre
            CreatorMember::updateOrCreate(
                ['user_id' => $user->id, 'creator_profile_id' => $invitation->creator_profile_id],
                ['role' => $invitation->role, 'is_active' => true, 'joined_at' => now()]
            );

            // 2. Marquer l'invitation comme acceptée
            $invitation->update(['status' => 'accepted']);

            // 3. Forcer une mise à jour de la version auth pour forcer un refresh du context
            $user->increment('auth_version');

            return true;
        });
    }

    /**
     * Changer le rôle d'un membre
     */
    public function updateMemberRole(CreatorProfile $creator, int $userId, string $role): bool
    {
        $member = CreatorMember::where('creator_profile_id', $creator->id)
            ->where('user_id', $userId)
            ->first();

        if (!$member) {
            return false;
        }

        $member->update(['role' => $role]);

        // CRITICAL SECURITY: Invalidate current session of the user
        $user = User::findOrFail($userId);
        $user->increment('auth_version');

        return true;
    }

    /**
     * Révoquer l'accès d'un membre
     */
    public function removeMember(CreatorProfile $creator, int $userId): bool
    {
        $member = CreatorMember::where('creator_profile_id', $creator->id)
            ->where('user_id', $userId)
            ->first();

        if (!$member) {
            return false;
        }

        $member->delete();

        // CRITICAL SECURITY: Invalidate current sessions of the user
        $user = User::findOrFail($userId);
        $user->increment('auth_version');

        return true;
    }
}

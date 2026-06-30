<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\CreatorMember;
use App\Models\CreatorProfile;
use App\Models\User;
use App\Services\Auth\UserContextResolver;
use App\Services\Creator\CreatorTeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamManagementController extends Controller
{
    public function __construct(
        private CreatorTeamService $teamService,
        private UserContextResolver $contextResolver
    ) {}

    /**
     * Lister les membres de l'équipe
     */
    public function index()
    {
        $context = $this->contextResolver->getFromSession();
        $creator = CreatorProfile::findOrFail($context->activeCreatorId);

        $this->authorize('view-team', $creator);

        $members = $creator->members()->with('user:id,name,email')->get();
        $invitations = $creator->invitations()->where('status', 'pending')->get();

        return response()->json([
            'members' => $members,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Inviter un membre
     */
    public function invite(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,editor,viewer',
        ]);

        $context = $this->contextResolver->getFromSession();
        $creator = CreatorProfile::findOrFail($context->activeCreatorId);

        $this->authorize('manage-team', $creator);

        $invitation = $this->teamService->invite(
            $creator,
            $request->email,
            $request->role,
            Auth::user()
        );

        return response()->json([
            'message' => 'Invitation sent successfully.',
            'invitation' => $invitation,
        ]);
    }

    /**
     * Changer le rôle d'un membre
     */
    public function updateRole(Request $request, int $userId)
    {
        $request->validate(['role' => 'required|in:admin,editor,viewer']);

        $context = $this->contextResolver->getFromSession();
        $creator = CreatorProfile::findOrFail($context->activeCreatorId);

        $this->authorize('manage-team', $creator);

        $success = $this->teamService->updateMemberRole($creator, $userId, $request->role);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Role updated.' : 'Member not found.',
        ]);
    }

    /**
     * Supprimer un membre
     */
    public function removeMember(int $userId)
    {
        $context = $this->contextResolver->getFromSession();
        $creator = CreatorProfile::findOrFail($context->activeCreatorId);

        $this->authorize('manage-team', $creator);

        if ($userId === Auth::id()) {
            return response()->json(['message' => 'You cannot remove yourself.'], 400);
        }

        $success = $this->teamService->removeMember($creator, $userId);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Member removed.' : 'Member not found.',
        ]);
    }
}

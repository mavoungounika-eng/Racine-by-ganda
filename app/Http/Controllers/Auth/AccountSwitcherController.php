<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\UserContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AccountSwitcherController extends Controller
{
    public function __construct(
        private UserContextResolver $contextResolver
    ) {}

    /**
     * Changer de compte actif (Organization Switcher)
     */
    public function switch(Request $request, int $creatorId)
    {
        $user = Auth::user();

        // 1. Vérifier que l'utilisateur a accès à ce compte
        // On check via memberships OU via le profil legacy
        $hasAccess = $user->memberships()->where('creator_profile_id', $creatorId)->exists() 
            || ($user->creatorProfile && $user->creatorProfile->id === $creatorId);

        if (!$hasAccess) {
            return response()->json(['message' => 'Unauthorized account switch.'], 403);
        }

        // 2. Stocker le nouveau ID dans la session
        Session::put('active_creator_id', $creatorId);

        // 3. Re-résoudre le contexte pour geler les nouvelles permissions/rôles
        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Account switched successfully.',
                'active_creator_id' => $creatorId,
                'role' => $context->creatorRole,
            ]);
        }

        return back()->with('success', 'Compte changé avec succès.');
    }

    /**
     * Lister les comptes disponibles
     */
    public function list()
    {
        $user = Auth::user();
        
        $accounts = $user->creatorProfiles()
            ->select('creator_profiles.id', 'brand_name', 'logo_path')
            ->get();

        // Si le profil legacy n'est pas dans les memberships, l'ajouter
        if ($user->creatorProfile && !$accounts->contains('id', $user->creatorProfile->id)) {
            $accounts->prepend($user->creatorProfile);
        }

        return response()->json([
            'accounts' => $accounts,
            'active_id' => Session::get('active_creator_id') ?? ($user->creatorProfile?->id),
        ]);
    }
}

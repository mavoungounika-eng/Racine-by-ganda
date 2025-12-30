<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Traits\HandlesAuthRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Contrôleur du hub d'authentification
 * 
 * Comportement :
 * - Si utilisateur connecté → Redirige vers son dashboard selon son rôle
 * - Si utilisateur non connecté → Affiche la page hub avec choix entre boutique et équipe
 */
class AuthHubController extends Controller
{
    use HandlesAuthRedirect;

    /**
     * Display the authentication hub page.
     * 
     * Si l'utilisateur est déjà connecté, le redirige directement vers son dashboard.
     * Sinon, affiche la page de choix entre espace boutique et espace équipe.
     */
    public function index(): View|RedirectResponse
    {
        // Si l'utilisateur est déjà connecté, rediriger vers son dashboard
        if (Auth::check()) {
            $user = Auth::user();
            $user->load('roleRelation');
            
            return redirect($this->getRedirectPath($user));
        }

        // Sinon, afficher le hub normalement
        return view('auth.hub');
    }
}

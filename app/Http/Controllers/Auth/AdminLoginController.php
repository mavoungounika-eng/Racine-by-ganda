<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Contrôleur d'authentification pour l'espace Admin/Équipe
 * 
 * PHASE 1 SÉCURITÉ : Route dédiée /admin/login pour l'espace équipe
 * Réutilise la logique de LoginController sans duplication
 */
class AdminLoginController extends Controller
{
    /**
     * Afficher le formulaire de connexion admin
     * PHASE 3 : Vue dédiée admin-login.blade.php
     */
    public function showLoginForm(): View|RedirectResponse
    {
        // Si déjà connecté et membre équipe, rediriger vers dashboard
        if (Auth::check()) {
            $user = Auth::user();
            $user->load('roleRelation');
            
            if ($user->isTeamMember()) {
                return redirect()->route('admin.dashboard');
            }
            
            // Si connecté mais pas membre équipe, déconnecter
            Auth::logout();
        }
        
        // PHASE 3 : Afficher la vue dédiée admin login
        return view('auth.admin-login');
    }
    
    /**
     * Traiter la connexion admin
     * Utilise AuthOrchestratorService directement
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        // Déléguer à AuthOrchestratorService
        $orchestrator = app(\App\Services\Auth\AuthOrchestratorService::class);
        $result = $orchestrator->authenticate($request, $credentials, $remember);

        // Gérer le résultat
        if ($result->isFailed()) {
            return back()->withErrors($result->errors)->onlyInput('email');
        }

        if ($result->requires2FA()) {
            return redirect($result->redirectUrl);
        }

        return redirect()->intended($result->redirectUrl);
    }
}

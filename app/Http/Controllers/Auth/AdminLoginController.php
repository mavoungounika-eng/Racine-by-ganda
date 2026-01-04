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
     * Délègue au LoginController standard pour éviter duplication
     */
    public function login(Request $request): RedirectResponse
    {
        // Ajouter le contexte équipe à la requête
        $request->merge(['context' => 'equipe']);
        
        // Déléguer au LoginController standard
        $loginController = app(LoginController::class);
        return $loginController->login($request);
    }
}

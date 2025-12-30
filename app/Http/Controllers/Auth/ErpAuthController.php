<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ErpAuthController extends Controller
{
    /**
     * Show the ERP login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.erp-login');
    }

    /**
     * Handle ERP login request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            
            // Charger la relation roleRelation avant la vérification
            $user->load('roleRelation');
            
            // Vérifier que l'utilisateur a un rôle ERP autorisé (utiliser le slug)
            $erpRoleSlugs = ['admin', 'super_admin', 'moderator', 'staff'];
            $userRoleSlug = $user->getRoleSlug();
            
            if (!in_array($userRoleSlug, $erpRoleSlugs)) {
                Auth::logout();
                
                return back()->withErrors([
                    'email' => 'Vous n\'avez pas l\'autorisation d\'accéder à l\'espace ERP.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            
            return $this->redirectByRole($user);
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis sont incorrects.',
        ])->onlyInput('email');
    }

    /**
     * Handle ERP logout request.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('erp.login');
    }

    /**
     * Redirect user based on their role.
     * Utilise le slug du rôle (via getRoleSlug()) au lieu du name.
     */
    protected function redirectByRole($user): RedirectResponse
    {
        // Utiliser getRoleSlug() pour obtenir le slug du rôle
        $roleSlug = $user->getRoleSlug();

        return match($roleSlug) {
            'admin', 'super_admin', 'moderator', 'staff' => redirect()->route('admin.dashboard'),
            default => redirect()->route('erp.login'),
        };
    }
}

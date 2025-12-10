<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Traits\HandlesAuthRedirect;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PublicAuthController extends Controller
{
    use HandlesAuthRedirect;
    /**
     * Show the login form.
     */
    public function showLoginForm(Request $request): View
    {
        $style = $request->query('style', 'neutral');
        
        return match($style) {
            'female' => view('auth.login-female'),
            'male' => view('auth.login-male'),
            default => view('auth.login-neutral'),
        };
    }

    /**
     * Handle login request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Charger la relation roleRelation avant la redirection
            $user->load('roleRelation');
            
            // Sauvegarder le style visuel si fourni
            if ($request->has('visual_style')) {
                $settings = \App\Models\UserSetting::forUser($user->id);
                $settings->update(['visual_style' => $request->visual_style]);
            }
            
            return $this->redirectByRole($user);
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
        ])->onlyInput('email');
    }

    /**
     * Show the registration form.
     * 
     * Adapte l'UI selon le contexte (boutique/equipe/neutral) comme pour le login.
     */
    public function showRegisterForm(Request $request): View
    {
        // Résoudre le contexte d'inscription (boutique, equipe ou null)
        $registerContext = $this->resolveRegisterContext($request);
        
        return view('auth.register', [
            'registerContext' => $registerContext,
        ]);
    }

    /**
     * Résout le contexte d'inscription depuis la requête et la session
     * 
     * Priorité :
     * 1. Paramètre query `context` si présent et valide
     * 2. Session `register_context` si présente et valide
     * 3. null (contexte neutre)
     * 
     * @param Request $request
     * @return string|null Retourne 'boutique', 'equipe' ou null
     */
    protected function resolveRegisterContext(Request $request): ?string
    {
        // Priorité 1: Paramètre query si présent et valide
        $queryContext = $request->query('context');
        
        if ($queryContext && in_array($queryContext, ['boutique', 'equipe'], true)) {
            // Stocker en session pour persistance
            session(['register_context' => $queryContext]);
            return $queryContext;
        }

        // Priorité 2: Session si présente et valide
        $sessionContext = session('register_context');
        
        if ($sessionContext && in_array($sessionContext, ['boutique', 'equipe'], true)) {
            return $sessionContext;
        }

        // Nettoyer la session si contexte invalide
        session()->forget('register_context');

        // Priorité 3: Contexte neutre
        return null;
    }

    /**
     * Handle registration request.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        // Récupérer le type de compte depuis le formulaire
        $accountType = $request->input('account_type', 'client');
        
        // Mapping entre les valeurs du formulaire et les slugs/noms dans la base
        $slugMap = ['client' => 'client', 'creator' => 'createur'];
        $nameMap = ['client' => 'Client', 'creator' => 'Créateur'];
        
        $slug = $slugMap[$accountType] ?? 'client';
        $name = $nameMap[$accountType] ?? 'Client';
        
        // Utiliser firstOrCreate avec le slug comme clé unique
        $role = Role::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => $name,
                'is_active' => true,
            ]
        );

        // Créer l'utilisateur
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
        ]);

        // Charger la relation roleRelation avant la redirection
        $user->load('roleRelation');

        // Connecter automatiquement l'utilisateur
        Auth::login($user);

        return $this->redirectByRole($user);
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Redirect user based on their role.
     * Utilise maintenant le trait HandlesAuthRedirect pour éviter la duplication.
     */
    protected function redirectByRole(User $user): RedirectResponse
    {
        // Délégation au trait HandlesAuthRedirect
        return redirect($this->getRedirectPath($user));
    }
}

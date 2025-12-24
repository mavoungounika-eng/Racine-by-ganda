<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Traits\HandlesAuthRedirect;
use App\Http\Controllers\Auth\Traits\HandlesAuthContext;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicAuthController extends Controller
{
    use HandlesAuthRedirect, HandlesAuthContext;
    /**
     * Show the login form.
     */
    public function showLoginForm(Request $request): View
    {
        // Utiliser la vue premium avec design existant
        $loginContext = $this->resolveContext($request, 'login');
        
        return view('auth.login-neutral', [
            'loginContext' => $loginContext,
        ]);
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
        $registerContext = $this->resolveContext($request, 'register');
        
        // Utiliser la vue unifiée avec message rassurant
        return view('auth.register-unified', [
            'registerContext' => $registerContext,
        ]);
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
     * Afficher le formulaire "Mot de passe oublié"
     */
    public function showForgotForm(): View
    {
        return view('auth.passwords.forgot');
    }

    /**
     * Envoyer le lien de réinitialisation par email
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Afficher le formulaire de réinitialisation
     */
    public function showResetForm(string $token): View
    {
        return view('auth.passwords.reset', ['token' => $token]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:12|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
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

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Modules\CRM\Models\CrmContact;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactorService;
    
    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }
    
    /**
     * Affiche la page de configuration 2FA
     */
    public function setup()
    {
        $user = Auth::user();
        
        if ($this->twoFactorService->isEnabled($user)) {
            return redirect()->route('2fa.manage')
                ->with('info', 'L\'authentification à deux facteurs est déjà activée.');
        }
        
        // Générer un nouveau secret
        $secret = $this->twoFactorService->generateSecretKey();
        Session::put('2fa_setup_secret', $secret);
        
        // Générer le QR Code
        $qrCodeSvg = $this->twoFactorService->generateQrCodeSvg($user, $secret);
        
        return view('auth.2fa.setup', [
            'secret' => $secret,
            'qrCodeSvg' => $qrCodeSvg,
            'user' => $user,
        ]);
    }
    
    /**
     * Confirme l'activation du 2FA
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);
        
        $user = Auth::user();
        $secret = Session::get('2fa_setup_secret');
        
        if (!$secret) {
            return redirect()->route('2fa.setup')
                ->with('error', 'Session expirée. Veuillez recommencer.');
        }
        
        // Vérifier le code
        if (!$this->twoFactorService->verifyCode($secret, $request->code)) {
            return back()->with('error', 'Code invalide. Vérifiez votre application et réessayez.');
        }
        
        // Activer le 2FA
        $this->twoFactorService->enableTwoFactor($user, $secret);
        
        // Supprimer le secret de la session
        Session::forget('2fa_setup_secret');
        
        // Récupérer les codes de récupération à afficher
        $recoveryCodes = $this->twoFactorService->getRecoveryCodes($user);
        
        // Ajouter/mettre à jour le contact CRM
        $this->syncToCrm($user);
        
        return view('auth.2fa.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
            'user' => $user,
        ]);
    }
    
    /**
     * Affiche la page de gestion 2FA
     */
    public function manage()
    {
        $user = Auth::user();
        
        return view('auth.2fa.manage', [
            'user' => $user,
            'isEnabled' => $this->twoFactorService->isEnabled($user),
            'isRequired' => $this->twoFactorService->isRequired($user),
            'recoveryCodesCount' => count($this->twoFactorService->getRecoveryCodes($user)),
        ]);
    }
    
    /**
     * Régénère les codes de récupération
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);
        
        $user = Auth::user();
        $recoveryCodes = $this->twoFactorService->regenerateRecoveryCodes($user);
        
        return view('auth.2fa.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
            'user' => $user,
            'regenerated' => true,
        ]);
    }
    
    /**
     * Désactive le 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
            'code' => 'required|string',
        ]);
        
        $user = Auth::user();
        
        // Vérifier si c'est obligatoire
        if ($this->twoFactorService->isRequired($user)) {
            return back()->with('error', 'La double authentification est obligatoire pour votre compte.');
        }
        
        // Vérifier le code 2FA ou code de récupération
        $secret = $this->twoFactorService->getDecryptedSecret($user);
        $isValidCode = $this->twoFactorService->verifyCode($secret, $request->code);
        $isValidRecovery = $this->twoFactorService->verifyRecoveryCode($user, $request->code);
        
        if (!$isValidCode && !$isValidRecovery) {
            return back()->with('error', 'Code invalide.');
        }
        
        // Désactiver
        $this->twoFactorService->disableTwoFactor($user);
        
        return redirect()->route('2fa.manage')
            ->with('success', 'L\'authentification à deux facteurs a été désactivée.');
    }
    
    /**
     * Affiche la page de challenge 2FA (lors de la connexion)
     */
    public function challenge()
    {
        if (!Session::has('2fa_user_id')) {
            return redirect()->route('login');
        }
        
        return view('auth.2fa.challenge');
    }
    
    /**
     * Vérifie le code 2FA lors de la connexion
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'trust_device' => 'nullable|boolean',
        ]);
        
        $userId = Session::get('2fa_user_id');
        $remember = Session::get('2fa_remember', false);
        
        if (!$userId) {
            return redirect()->route('login')
                ->with('error', 'Session expirée. Veuillez vous reconnecter.');
        }
        
        $user = User::find($userId);
        
        if (!$user) {
            Session::forget(['2fa_user_id', '2fa_remember']);
            return redirect()->route('login')
                ->with('error', 'Utilisateur introuvable.');
        }
        
        $secret = $this->twoFactorService->getDecryptedSecret($user);
        $code = str_replace([' ', '-'], '', $request->code);
        
        // Vérifier le code 2FA ou code de récupération
        $isValid = false;
        
        if (strlen($code) === 6 && is_numeric($code)) {
            // Code TOTP
            $isValid = $this->twoFactorService->verifyCode($secret, $code);
        } else {
            // Code de récupération
            $isValid = $this->twoFactorService->verifyRecoveryCode($user, $code);
        }
        
        if (!$isValid) {
            return back()->with('error', 'Code invalide. Vérifiez et réessayez.');
        }
        
        // Nettoyer la session
        Session::forget(['2fa_user_id', '2fa_remember']);
        
        // Connecter l'utilisateur
        Auth::login($user, $remember);
        
        // Gérer l'appareil de confiance
        if ($request->boolean('trust_device')) {
            $token = $this->twoFactorService->generateTrustedDeviceToken($user);
            cookie()->queue('trusted_device', $token, 60 * 24 * 30); // 30 jours
        }
        
        // Mettre à jour le CRM
        $this->syncToCrm($user);
        
        // Rediriger selon le rôle
        return $this->redirectByRole($user);
    }
    
    /**
     * Synchronise l'utilisateur avec le CRM
     * Note: Ne pas ajouter les membres de l'équipe au CRM
     */
    protected function syncToCrm(User $user): void
    {
        // Ne pas ajouter les membres de l'équipe au CRM
        if ($user->isTeamMember()) {
            return;
        }
        
        try {
            $roleSlug = $user->roleRelation?->slug ?? 'client';
            
            $crmContact = CrmContact::updateOrCreate(
                ['email' => $user->email],
                [
                    'first_name' => explode(' ', $user->name)[0] ?? $user->name,
                    'last_name' => explode(' ', $user->name, 2)[1] ?? '',
                    'phone' => $user->phone,
                    'type' => $this->mapRoleToCrmType($roleSlug),
                    'status' => 'active',
                    'source' => '2fa_registration',
                    'tags' => json_encode(['2fa_enabled', 'verified']),
                    'last_activity_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas bloquer le processus
            \Log::warning('CRM sync failed for user ' . $user->id . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Mappe le rôle utilisateur au type CRM
     */
    protected function mapRoleToCrmType(string $role): string
    {
        return match($role) {
            'createur' => 'partner',
            'client' => 'client',
            default => 'lead',
        };
    }
    
    /**
     * Redirige selon le rôle après connexion
     */
    protected function redirectByRole(User $user)
    {
        $roleSlug = $user->roleRelation?->slug ?? 'client';
        
        return match($roleSlug) {
            'super_admin' => redirect()->route('admin.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'staff' => redirect()->route('dashboard.staff'),
            'createur' => redirect()->route('dashboard.createur'),
            'client' => redirect()->route('account.dashboard'),
            default => redirect()->route('frontend.home'),
        };
    }
}


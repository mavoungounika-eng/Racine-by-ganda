# Architecture d'Authentification Sécurisée - Laravel 10

**Projet:** RACINE BY GANDA  
**Objectif:** Système d'authentification 9/10 en sécurité  
**Stack:** Laravel 10, PHP 8.2, Redis

---

## 1. Architecture Globale

### Guards & Providers

```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Models\Admin::class,
    ],
],
```

**Recommandations:**
- ✅ Séparer guards web/admin/api
- ✅ Sessions pour web, Sanctum pour API
- ❌ Éviter Passport (overkill pour SPA)

---

## 2. Sécurité Login

### Rate Limiting Intelligent

```php
// app/Http/Middleware/ThrottleLogins.php
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Str;

class ThrottleLogins
{
    public function handle($request, Closure $next)
    {
        $key = $this->throttleKey($request);
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw new ThrottleRequestsException("Too many attempts. Retry in {$seconds}s");
        }
        
        RateLimiter::hit($key, 300); // 5 min
        
        return $next($request);
    }
    
    protected function throttleKey($request): string
    {
        // Combiner IP + email pour éviter bypass
        return Str::lower($request->input('email')).'|'.$request->ip();
    }
}
```

### Lockout Progressif

```php
// app/Services/Auth/LoginThrottleService.php
class LoginThrottleService
{
    public function incrementAttempts(string $email, string $ip): void
    {
        $attempts = $this->getAttempts($email, $ip);
        
        // Lockout progressif: 1min, 5min, 15min, 1h, 24h
        $delays = [60, 300, 900, 3600, 86400];
        $delay = $delays[min($attempts, count($delays) - 1)] ?? 86400;
        
        Cache::put("login_attempts:{$email}:{$ip}", $attempts + 1, $delay);
    }
    
    public function clearAttempts(string $email, string $ip): void
    {
        Cache::forget("login_attempts:{$email}:{$ip}");
    }
}
```

### Protection Anti-Enumeration

```php
// Toujours retourner le même message
throw ValidationException::withMessages([
    'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
]);

// ❌ JAMAIS: "Email non trouvé" ou "Mot de passe incorrect"
```

---

## 3. Gestion Mots de Passe

### Configuration Argon2id

```php
// config/hashing.php
'driver' => 'argon2id',
'argon' => [
    'memory' => 65536,    // 64 MB
    'threads' => 4,
    'time' => 4,
],
```

```env
# .env
HASH_DRIVER=argon2id
```

### Politique Complexité

```php
// app/Rules/StrongPassword.php
use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    public function passes($attribute, $value)
    {
        return strlen($value) >= 12
            && preg_match('/[a-z]/', $value)
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[0-9]/', $value)
            && preg_match('/[@$!%*?&#]/', $value);
    }
    
    public function message()
    {
        return 'Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
    }
}
```

### Vérification Compromis (HaveIBeenPwned)

```php
// app/Services/Auth/PwnedPasswordService.php
class PwnedPasswordService
{
    public function isCompromised(string $password): bool
    {
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);
        
        $response = Http::get("https://api.pwnedpasswords.com/range/{$prefix}");
        
        return str_contains($response->body(), $suffix);
    }
}

// Utilisation
if ($pwnedService->isCompromised($password)) {
    throw ValidationException::withMessages([
        'password' => 'Ce mot de passe a été compromis dans une fuite de données.',
    ]);
}
```

---

## 4. 2FA (TOTP)

### Installation

```bash
composer require pragmarx/google2fa-laravel
```

### Migration

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('two_factor_secret')->nullable();
    $table->text('two_factor_recovery_codes')->nullable();
    $table->timestamp('two_factor_confirmed_at')->nullable();
});
```

### Service 2FA

```php
// app/Services/Auth/TwoFactorService.php
use PragmaRx\Google2FA\Google2FA;

class TwoFactorService
{
    public function generateSecret(): string
    {
        return (new Google2FA())->generateSecretKey();
    }
    
    public function verify(User $user, string $code): bool
    {
        $google2fa = new Google2FA();
        return $google2fa->verifyKey($user->two_factor_secret, $code);
    }
    
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn() => Str::random(10).'-'.Str::random(10))
            ->toArray();
    }
}
```

### Forcer 2FA Admins

```php
// app/Http/Middleware/Require2FA.php
class Require2FA
{
    public function handle($request, Closure $next)
    {
        if (auth()->user()->isAdmin() && !auth()->user()->two_factor_confirmed_at) {
            return redirect()->route('2fa.setup')
                ->with('warning', '2FA obligatoire pour les administrateurs');
        }
        
        return $next($request);
    }
}
```

---

## 5. Gestion Sessions

### Configuration Redis

```php
// config/session.php
'driver' => 'redis',
'connection' => 'session',
'lifetime' => 120,
'expire_on_close' => false,
'encrypt' => true,
'secure' => true,
'http_only' => true,
'same_site' => 'lax',
```

```env
SESSION_DRIVER=redis
SESSION_CONNECTION=session
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### Invalidation Globale

```php
// Lors du changement de password
Auth::logoutOtherDevices($currentPassword);

// Ou manuellement
DB::table('sessions')->where('user_id', $userId)->delete();
```

---

## 6. Reset Password

### Configuration

```php
// config/auth.php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60, // 1 heure
        'throttle' => 60,
    ],
],
```

### Controller Sécurisé

```php
public function sendResetLink(Request $request)
{
    $request->validate(['email' => 'required|email']);
    
    // Rate limit
    RateLimiter::attempt(
        'reset-password:'.$request->ip(),
        3,
        function() use ($request) {
            Password::sendResetLink($request->only('email'));
        },
        3600
    );
    
    // Toujours même message (anti-enumeration)
    return back()->with('status', 'Si cet email existe, un lien a été envoyé.');
}
```

---

## 7. API Authentication (Sanctum)

### Installation

```bash
php artisan install:api
```

### Configuration

```php
// config/sanctum.php
'expiration' => 60 * 24, // 24h
'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

'middleware' => [
    'encrypt_cookies' => false,
    'verify_csrf_token' => false,
],
```

### Création Token

```php
$token = $user->createToken('mobile-app', ['read', 'write'])->plainTextToken;
```

### Vérification Abilities

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'ability:read'])->get('/products', ...);
```

---

## 8. RBAC (Spatie Permission)

### Installation

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### Définition

```php
// database/seeders/RolePermissionSeeder.php
Role::create(['name' => 'admin']);
Role::create(['name' => 'creator']);
Role::create(['name' => 'customer']);

Permission::create(['name' => 'manage-users']);
Permission::create(['name' => 'manage-products']);

Role::findByName('admin')->givePermissionTo(Permission::all());
```

### Middleware

```php
Route::middleware(['auth', 'role:admin'])->group(function() {
    Route::get('/admin/dashboard', ...);
});

Route::middleware(['auth', 'permission:manage-products'])->group(function() {
    Route::resource('products', ProductController::class);
});
```

---

## 9. Monitoring & Détection

### Logging Tentatives

```php
// app/Listeners/LogAuthenticationAttempt.php
class LogAuthenticationAttempt
{
    public function handle(Attempting $event)
    {
        Log::channel('security')->info('Login attempt', [
            'email' => $event->credentials['email'] ?? null,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### Détection Anomalies

```php
// app/Services/Auth/AnomalyDetectionService.php
class AnomalyDetectionService
{
    public function detectSuspiciousLogin(User $user): bool
    {
        $currentIp = request()->ip();
        $lastIp = $user->last_login_ip;
        
        // Nouveau pays
        if ($this->isDifferentCountry($currentIp, $lastIp)) {
            $this->sendAlert($user, 'Connexion depuis un nouveau pays');
            return true;
        }
        
        // Heure inhabituelle
        if ($this->isUnusualTime($user)) {
            $this->sendAlert($user, 'Connexion à une heure inhabituelle');
            return true;
        }
        
        return false;
    }
}
```

---

## 10. Hardening Production

### Headers Sécurité

```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        return $response
            ->header('X-Frame-Options', 'DENY')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-XSS-Protection', '1; mode=block')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
            ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
```

### Configuration PHP

```ini
; php.ini
expose_php = Off
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Lax
session.use_strict_mode = 1
```

### .env Production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://racine-by-ganda.com

SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=racine-by-ganda.com
```

---

## Priorisation

### 🔴 CRITIQUE (Obligatoire)
1. Argon2id hashing
2. Rate limiting login
3. HTTPS forcé
4. CSRF protection
5. Session sécurisée (Redis)
6. Headers sécurité
7. Lockout progressif
8. Anti-enumeration

### 🟠 IMPORTANT (Fortement recommandé)
1. 2FA pour admins
2. Password reset sécurisé
3. Logging tentatives
4. RBAC (Spatie)
5. Sanctum API
6. Invalidation sessions
7. Strong password policy

### 🟡 OPTIONNEL (Nice to have)
1. reCAPTCHA v3
2. HaveIBeenPwned check
3. Anomaly detection
4. WebAuthn
5. Geolocation tracking

---

## Erreurs Fréquentes à Éviter

❌ **Messages différents login/password**  
✅ Toujours même message générique

❌ **Rate limit uniquement IP**  
✅ Combiner IP + email

❌ **Bcrypt par défaut**  
✅ Argon2id obligatoire

❌ **Sessions en fichiers**  
✅ Redis pour performance + sécurité

❌ **Pas de 2FA admins**  
✅ 2FA obligatoire pour privilèges élevés

❌ **Tokens API sans expiration**  
✅ Expiration 24h max

❌ **Logging insuffisant**  
✅ Audit trail complet

❌ **HTTPS optionnel**  
✅ HTTPS forcé partout

---

**Score Sécurité Attendu:** 9.5/10  
**Conformité:** OWASP Top 10, RGPD

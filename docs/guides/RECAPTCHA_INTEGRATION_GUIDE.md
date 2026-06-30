# Guide Complet - Intégration Google reCAPTCHA v3

**Application:** RACINE BY GANDA  
**Laravel:** 10.x  
**PHP:** 8.2+  
**Objectif:** Protéger le login contre les attaques brute-force

---

## 📋 Table des Matières

1. [Installation Package](#1-installation-package)
2. [Configuration](#2-configuration)
3. [Service RecaptchaService](#3-service-recaptchaservice)
4. [Intégration AuthOrchestratorService](#4-intégration-authorchestratorservice)
5. [Frontend Integration](#5-frontend-integration)
6. [Tests Unitaires](#6-tests-unitaires)
7. [Bonnes Pratiques Production](#7-bonnes-pratiques-production)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Installation Package

### Étape 1.1: Installer google/recaptcha

```bash
composer require google/recaptcha "^1.3"
```

**Vérification:**
```bash
composer show google/recaptcha
```

**Output attendu:**
```
name     : google/recaptcha
versions : * 1.3.0
```

---

## 2. Configuration

### Étape 2.1: Créer compte Google reCAPTCHA

1. Aller sur: https://www.google.com/recaptcha/admin/create
2. Remplir le formulaire:
   - **Label:** RACINE BY GANDA - Production
   - **reCAPTCHA type:** reCAPTCHA v3
   - **Domains:** 
     - `racine-by-ganda.com`
     - `staging.racine-by-ganda.com`
     - `localhost` (pour développement)
3. Accepter les conditions
4. Cliquer "Submit"
5. **Copier:**
   - Site Key
   - Secret Key

### Étape 2.2: Configuration .env

**Ajouter dans `.env` et `.env.production`:**

```env
# Google reCAPTCHA v3
RECAPTCHA_ENABLED=true
RECAPTCHA_SITE_KEY=6LcXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
RECAPTCHA_SECRET_KEY=6LcYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY
RECAPTCHA_THRESHOLD=0.5
RECAPTCHA_VERIFY_URL=https://www.google.com/recaptcha/api/siteverify
```

**Ajouter dans `.env.example`:**

```env
# Google reCAPTCHA v3
RECAPTCHA_ENABLED=true
RECAPTCHA_SITE_KEY=your_site_key_here
RECAPTCHA_SECRET_KEY=your_secret_key_here
RECAPTCHA_THRESHOLD=0.5
RECAPTCHA_VERIFY_URL=https://www.google.com/recaptcha/api/siteverify
```

### Étape 2.3: Créer fichier de configuration

**Créer `config/recaptcha.php`:**

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable reCAPTCHA validation globally.
    | Set to false in development to bypass validation.
    |
    */
    'enabled' => env('RECAPTCHA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Site Key
    |--------------------------------------------------------------------------
    |
    | Your reCAPTCHA v3 site key (public key).
    | Get it from: https://www.google.com/recaptcha/admin
    |
    */
    'site_key' => env('RECAPTCHA_SITE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Secret Key
    |--------------------------------------------------------------------------
    |
    | Your reCAPTCHA v3 secret key (private key).
    | NEVER expose this in frontend code.
    |
    */
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Score Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum score required to pass validation (0.0 - 1.0).
    | Recommended: 0.5 for login, 0.3 for less critical actions.
    |
    | Score interpretation:
    | - 1.0: Very likely a good interaction
    | - 0.5: Neutral
    | - 0.0: Very likely a bot
    |
    */
    'threshold' => env('RECAPTCHA_THRESHOLD', 0.5),

    /*
    |--------------------------------------------------------------------------
    | Verification URL
    |--------------------------------------------------------------------------
    |
    | Google reCAPTCHA API verification endpoint.
    |
    */
    'verify_url' => env('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | HTTP request timeout in seconds for reCAPTCHA verification.
    |
    */
    'timeout' => 5,

    /*
    |--------------------------------------------------------------------------
    | Skip for Testing
    |--------------------------------------------------------------------------
    |
    | Automatically disable reCAPTCHA when running tests.
    |
    */
    'skip_for_testing' => env('APP_ENV') === 'testing',

];
```

**Vérifier configuration:**

```bash
php artisan tinker
>>> config('recaptcha.enabled')
=> true
>>> config('recaptcha.threshold')
=> 0.5
```

---

## 3. Service RecaptchaService

### Étape 3.1: Créer le service

**Créer `app/Services/Auth/RecaptchaService.php`:**

```php
<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RecaptchaService
{
    /**
     * Check if reCAPTCHA is enabled.
     */
    public function isEnabled(): bool
    {
        // Désactiver automatiquement en tests
        if (config('recaptcha.skip_for_testing')) {
            return false;
        }

        // Vérifier que les clés sont configurées
        return config('recaptcha.enabled', false) 
            && !empty(config('recaptcha.secret_key'))
            && !empty(config('recaptcha.site_key'));
    }

    /**
     * Verify reCAPTCHA token.
     *
     * @param string $token Token from frontend
     * @param string $action Expected action name (default: 'login')
     * @return bool True if verification passes
     */
    public function verify(string $token, string $action = 'login'): bool
    {
        // Bypass si désactivé
        if (!$this->isEnabled()) {
            Log::info('reCAPTCHA bypassed (disabled)');
            return true;
        }

        // Valider token non vide
        if (empty($token)) {
            Log::warning('reCAPTCHA token is empty');
            return false;
        }

        try {
            // Appel API Google
            $response = Http::timeout(config('recaptcha.timeout', 5))
                ->asForm()
                ->post(config('recaptcha.verify_url'), [
                    'secret' => config('recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);

            // Vérifier succès HTTP
            if (!$response->successful()) {
                Log::error('reCAPTCHA API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return true; // Fail-open: laisser passer en cas d'erreur API
            }

            $data = $response->json();

            // Vérifier succès reCAPTCHA
            if (!($data['success'] ?? false)) {
                Log::warning('reCAPTCHA verification failed', [
                    'error_codes' => $data['error-codes'] ?? [],
                    'token' => substr($token, 0, 20) . '...',
                ]);
                return false;
            }

            // Vérifier action (optionnel mais recommandé)
            if (isset($data['action']) && $data['action'] !== $action) {
                Log::warning('reCAPTCHA action mismatch', [
                    'expected' => $action,
                    'received' => $data['action'],
                ]);
                return false;
            }

            // Vérifier score
            $score = $data['score'] ?? 0.0;
            $threshold = config('recaptcha.threshold');
            $passed = $score >= $threshold;

            // Log résultat
            Log::info('reCAPTCHA verification completed', [
                'score' => $score,
                'threshold' => $threshold,
                'action' => $data['action'] ?? null,
                'passed' => $passed,
                'hostname' => $data['hostname'] ?? null,
            ]);

            return $passed;

        } catch (\Exception $e) {
            // Fail-open: En cas d'erreur, laisser passer
            Log::error('reCAPTCHA verification exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return true; // Fail-open pour éviter de bloquer les utilisateurs
        }
    }

    /**
     * Get reCAPTCHA score for a token.
     *
     * @param string $token Token from frontend
     * @return float Score between 0.0 and 1.0
     */
    public function getScore(string $token): float
    {
        if (!$this->isEnabled() || empty($token)) {
            return 1.0; // Score maximum si désactivé
        }

        try {
            $response = Http::timeout(config('recaptcha.timeout', 5))
                ->asForm()
                ->post(config('recaptcha.verify_url'), [
                    'secret' => config('recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ]);

            if (!$response->successful()) {
                return 1.0; // Fail-open
            }

            $data = $response->json();
            return $data['score'] ?? 0.0;

        } catch (\Exception $e) {
            Log::error('reCAPTCHA score retrieval error', [
                'error' => $e->getMessage(),
            ]);
            return 1.0; // Fail-open
        }
    }

    /**
     * Get site key for frontend.
     *
     * @return string|null
     */
    public function getSiteKey(): ?string
    {
        return $this->isEnabled() ? config('recaptcha.site_key') : null;
    }
}
```

### Étape 3.2: Enregistrer le service (optionnel)

**Dans `app/Providers/AppServiceProvider.php`:**

```php
use App\Services\Auth\RecaptchaService;

public function register(): void
{
    $this->app->singleton(RecaptchaService::class);
}
```

---

## 4. Intégration AuthOrchestratorService

### Étape 4.1: Modifier le constructeur

**Dans `app/Services/Auth/AuthOrchestratorService.php`:**

```php
use App\Services\Auth\RecaptchaService;

public function __construct(
    // ... autres dépendances existantes
    private RecaptchaService $recaptchaService
) {}
```

### Étape 4.2: Ajouter validation CAPTCHA

**Remplacer ligne 182 (TODO) par:**

```php
// Validation reCAPTCHA v3
if ($this->recaptchaService->isEnabled()) {
    $token = $request->input('recaptcha_token');
    
    if (!$token) {
        Log::warning('reCAPTCHA token missing', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);
        
        throw ValidationException::withMessages([
            'email' => __('Security validation required. Please refresh and try again.'),
        ]);
    }
    
    if (!$this->recaptchaService->verify($token, 'login')) {
        $score = $this->recaptchaService->getScore($token);
        
        Log::warning('reCAPTCHA validation failed', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'score' => $score,
            'threshold' => config('recaptcha.threshold'),
        ]);
        
        throw ValidationException::withMessages([
            'email' => __('Suspicious activity detected. Please try again later.'),
        ]);
    }
}
```

---

## 5. Frontend Integration

### Étape 5.1: Modifier login.blade.php

**Dans `resources/views/auth/login.blade.php`, ajouter avant `</head>`:**

```blade
@if(config('recaptcha.enabled'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('recaptcha.site_key') }}"></script>
@endif
```

### Étape 5.2: Ajouter script de soumission

**Avant `</body>`, ajouter:**

```blade
@if(config('recaptcha.enabled'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    
    if (!loginForm) {
        console.error('Login form not found');
        return;
    }
    
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Désactiver bouton submit
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Vérification...';
        }
        
        grecaptcha.ready(function() {
            grecaptcha.execute('{{ config('recaptcha.site_key') }}', {action: 'login'})
                .then(function(token) {
                    // Ajouter token au formulaire
                    let tokenInput = loginForm.querySelector('input[name="recaptcha_token"]');
                    if (!tokenInput) {
                        tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = 'recaptcha_token';
                        loginForm.appendChild(tokenInput);
                    }
                    tokenInput.value = token;
                    
                    // Soumettre formulaire
                    loginForm.submit();
                })
                .catch(function(error) {
                    console.error('reCAPTCHA error:', error);
                    
                    // Réactiver bouton
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Se connecter';
                    }
                    
                    alert('Erreur de sécurité. Veuillez rafraîchir la page.');
                });
        });
    });
});
</script>
@endif
```

### Étape 5.3: Ajouter ID au formulaire

**S'assurer que le formulaire a un ID:**

```blade
<form id="login-form" method="POST" action="{{ route('login') }}">
    @csrf
    {{-- ... champs du formulaire ... --}}
</form>
```

---

## 6. Tests Unitaires

### Étape 6.1: Créer RecaptchaServiceTest

**Créer `tests/Unit/Services/Auth/RecaptchaServiceTest.php`:**

```php
<?php

namespace Tests\Unit\Services\Auth;

use Tests\TestCase;
use App\Services\Auth\RecaptchaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class RecaptchaServiceTest extends TestCase
{
    private RecaptchaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecaptchaService();
    }

    #[Test]
    public function it_is_disabled_in_testing_environment()
    {
        Config::set('recaptcha.skip_for_testing', true);
        
        $this->assertFalse($this->service->isEnabled());
    }

    #[Test]
    public function it_is_disabled_without_secret_key()
    {
        Config::set('recaptcha.skip_for_testing', false);
        Config::set('recaptcha.enabled', true);
        Config::set('recaptcha.secret_key', '');
        
        $this->assertFalse($this->service->isEnabled());
    }

    #[Test]
    public function it_verifies_valid_token_with_high_score()
    {
        Config::set('recaptcha.skip_for_testing', false);
        Config::set('recaptcha.enabled', true);
        Config::set('recaptcha.secret_key', 'test_secret');
        Config::set('recaptcha.threshold', 0.5);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'login',
                'hostname' => 'localhost',
            ], 200),
        ]);

        $result = $this->service->verify('valid_token', 'login');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_rejects_token_with_low_score()
    {
        Config::set('recaptcha.skip_for_testing', false);
        Config::set('recaptcha.enabled', true);
        Config::set('recaptcha.secret_key', 'test_secret');
        Config::set('recaptcha.threshold', 0.5);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'score' => 0.3,
                'action' => 'login',
            ], 200),
        ]);

        $result = $this->service->verify('low_score_token', 'login');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_rejects_failed_verification()
    {
        Config::set('recaptcha.skip_for_testing', false);
        Config::set('recaptcha.enabled', true);
        Config::set('recaptcha.secret_key', 'test_secret');

        Http::fake([
            '*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $result = $this->service->verify('invalid_token', 'login');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_rejects_action_mismatch()
    {
        Config::set('recaptcha.skip_for_testing', false);
        Config::set('recaptcha.enabled', true);
        Config::set('recaptcha.secret_key', 'test_secret');

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'score' => 0.9,
                'action' => 'register', // Différent de 'login'
            ], 200),
        ]);

        $result = $this->service->verify('token', 'login');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_open_on_api_error()
    {
        Config::set('recaptcha.skip_for_testing', false);
        Config::set('recaptcha.enabled', true);
        Config::set('recaptcha.secret_key', 'test_secret');

        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $result = $this->service->verify('token', 'login');

        // Doit laisser passer en cas d'erreur API
        $this->assertTrue($result);
    }

    #[Test]
    public function it_fails_open_on_exception()
    {
        Config::set('recaptcha.skip_for_testing', false);
        Config::set('recaptcha.enabled', true);
        Config::set('recaptcha.secret_key', 'test_secret');

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $result = $this->service->verify('token', 'login');

        // Doit laisser passer en cas d'exception
        $this->assertTrue($result);
    }

    #[Test]
    public function it_bypasses_when_disabled()
    {
        Config::set('recaptcha.enabled', false);

        $result = $this->service->verify('any_token', 'login');

        $this->assertTrue($result);
        Http::assertNothingSent();
    }

    #[Test]
    public function it_returns_correct_score()
    {
        Config::set('recaptcha.skip_for_testing', false);
        Config::set('recaptcha.enabled', true);
        Config::set('recaptcha.secret_key', 'test_secret');

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'score' => 0.75,
            ], 200),
        ]);

        $score = $this->service->getScore('token');

        $this->assertEquals(0.75, $score);
    }

    #[Test]
    public function it_returns_max_score_when_disabled()
    {
        Config::set('recaptcha.enabled', false);

        $score = $this->service->getScore('token');

        $this->assertEquals(1.0, $score);
    }

    #[Test]
    public function it_rejects_empty_token()
    {
        Config::set('recaptcha.skip_for_testing', false);
        Config::set('recaptcha.enabled', true);
        Config::set('recaptcha.secret_key', 'test_secret');

        $result = $this->service->verify('', 'login');

        $this->assertFalse($result);
        Http::assertNothingSent();
    }
}
```

### Étape 6.2: Lancer les tests

```bash
php artisan test --filter RecaptchaServiceTest
```

**Output attendu:**
```
PASS  Tests\Unit\Services\Auth\RecaptchaServiceTest
✓ it is disabled in testing environment
✓ it is disabled without secret key
✓ it verifies valid token with high score
✓ it rejects token with low score
✓ it rejects failed verification
✓ it rejects action mismatch
✓ it fails open on api error
✓ it fails open on exception
✓ it bypasses when disabled
✓ it returns correct score
✓ it returns max score when disabled
✓ it rejects empty token

Tests:    12 passed (12 assertions)
Duration: 0.15s
```

---

## 7. Bonnes Pratiques Production

### 7.1 Fail-Open Strategy

**✅ TOUJOURS implémenter fail-open:**

```php
try {
    // Vérification reCAPTCHA
} catch (\Exception $e) {
    Log::error('reCAPTCHA error', ['error' => $e->getMessage()]);
    return true; // Laisser passer en cas d'erreur
}
```

**Raison:** Éviter de bloquer tous les utilisateurs si l'API Google est down.

### 7.2 Seuils Recommandés

| Action | Seuil | Justification |
|--------|-------|---------------|
| Login | 0.5 | Équilibre sécurité/UX |
| Register | 0.3 | Plus permissif |
| Password Reset | 0.5 | Sécurité importante |
| Contact Form | 0.3 | Éviter faux positifs |

### 7.3 Logging Stratégique

**✅ Logger:**
- Score reCAPTCHA
- IP utilisateur
- Email (si disponible)
- Action tentée

**❌ NE PAS logger:**
- Token reCAPTCHA complet (sensible)
- Secret key (jamais!)

### 7.4 Monitoring

**Métriques à surveiller:**

```php
// Dans un observer ou middleware
Log::channel('metrics')->info('recaptcha_verification', [
    'score' => $score,
    'passed' => $passed,
    'threshold' => $threshold,
    'action' => $action,
]);
```

**Alertes recommandées:**
- Taux de rejet > 10%
- API errors > 5%
- Score moyen < 0.6

### 7.5 Cache (Optionnel)

**Pour éviter double vérification:**

```php
public function verify(string $token, string $action = 'login'): bool
{
    $cacheKey = 'recaptcha:' . hash('sha256', $token);
    
    return Cache::remember($cacheKey, 300, function() use ($token, $action) {
        // Logique de vérification
    });
}
```

### 7.6 Rate Limiting Complémentaire

**Ne pas se reposer uniquement sur reCAPTCHA:**

```php
// Dans routes/web.php
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 tentatives par minute
```

### 7.7 Environnements

**Development:**
```env
RECAPTCHA_ENABLED=false
```

**Staging:**
```env
RECAPTCHA_ENABLED=true
RECAPTCHA_THRESHOLD=0.3  # Plus permissif pour tests
```

**Production:**
```env
RECAPTCHA_ENABLED=true
RECAPTCHA_THRESHOLD=0.5  # Équilibré
```

---

## 8. Troubleshooting

### Problème 1: "Token missing"

**Symptôme:** Erreur "Security validation required"

**Solutions:**
1. Vérifier que le script reCAPTCHA est chargé
2. Vérifier console navigateur pour erreurs JS
3. Vérifier que le formulaire a `id="login-form"`
4. Vérifier que `RECAPTCHA_SITE_KEY` est correcte

**Debug:**
```javascript
grecaptcha.ready(function() {
    console.log('reCAPTCHA ready');
    grecaptcha.execute('SITE_KEY', {action: 'login'})
        .then(token => console.log('Token:', token.substring(0, 20) + '...'));
});
```

### Problème 2: "Suspicious activity detected"

**Symptôme:** Utilisateurs légitimes bloqués

**Solutions:**
1. Baisser threshold: `RECAPTCHA_THRESHOLD=0.3`
2. Vérifier logs pour voir scores réels
3. Vérifier que domaine est autorisé dans console Google
4. Tester depuis différents navigateurs/IPs

**Debug:**
```bash
tail -f storage/logs/laravel.log | grep reCAPTCHA
```

### Problème 3: API Errors

**Symptôme:** Logs montrent erreurs API Google

**Solutions:**
1. Vérifier `RECAPTCHA_SECRET_KEY` correcte
2. Vérifier connectivité serveur vers google.com
3. Vérifier timeout pas trop court
4. Fail-open doit laisser passer

**Test connectivité:**
```bash
curl -X POST https://www.google.com/recaptcha/api/siteverify \
  -d "secret=YOUR_SECRET&response=test"
```

### Problème 4: Tests échouent

**Symptôme:** Tests unitaires rouges

**Solutions:**
1. Vérifier `RECAPTCHA_ENABLED=false` dans `.env.testing`
2. Ou `skip_for_testing=true` dans config
3. Utiliser `Http::fake()` dans tests

**`.env.testing`:**
```env
RECAPTCHA_ENABLED=false
```

### Problème 5: CORS Errors

**Symptôme:** Erreurs CORS dans console

**Solutions:**
1. Vérifier domaine autorisé dans Google Console
2. Ajouter `localhost` pour développement
3. Vérifier HTTPS en production

---

## ✅ Checklist Finale

### Configuration
- [ ] Package `google/recaptcha` installé
- [ ] Compte Google reCAPTCHA créé
- [ ] Site Key et Secret Key copiés
- [ ] Variables `.env` configurées
- [ ] Fichier `config/recaptcha.php` créé
- [ ] Domaines autorisés dans Google Console

### Code
- [ ] `RecaptchaService` créé
- [ ] Service enregistré dans `AppServiceProvider`
- [ ] `AuthOrchestratorService` modifié
- [ ] TODO ligne 182 supprimé
- [ ] Frontend `login.blade.php` modifié
- [ ] Formulaire a `id="login-form"`

### Tests
- [ ] `RecaptchaServiceTest` créé
- [ ] 12 tests passent
- [ ] Tests login fonctionnels
- [ ] `.env.testing` configuré

### Production
- [ ] Fail-open implémenté
- [ ] Logging configuré
- [ ] Threshold approprié (0.5)
- [ ] Rate limiting complémentaire
- [ ] Monitoring configuré
- [ ] Documentation équipe

---

## 📊 Métriques de Succès

**Après déploiement, surveiller:**

- **Taux de rejet:** < 5% (sinon baisser threshold)
- **Score moyen:** > 0.6 (sinon vérifier bots)
- **API errors:** < 1% (sinon vérifier connectivité)
- **Temps réponse:** < 500ms (sinon augmenter timeout)

---

**Guide créé le:** 13 Février 2026  
**Version:** 1.0  
**Auteur:** RACINE BY GANDA Tech Team

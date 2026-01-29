# 🏗️ ARCHITECTURE SOCIAL AUTH V2 — MULTI-PROVIDERS

## 📋 TABLE DES MATIÈRES

1. [Analyse de l'existant](#analyse-de-lexistant)
2. [Schéma de base de données](#schéma-de-base-de-données)
3. [Architecture du nouveau module](#architecture-du-nouveau-module)
4. [Flux OAuth détaillé](#flux-oauth-détaillé)
5. [Exemple de contrôleur générique](#exemple-de-contrôleur-générique)
6. [Checklist sécurité](#checklist-sécurité)
7. [Plan de migration progressive](#plan-de-migration-progressive)
8. [Décision stratégique](#décision-stratégique)

---

## 🔍 ANALYSE DE L'EXISTANT

### Architecture actuelle (Google Auth v1)

**Points forts :**
- ✅ Sécurité robuste (state CSRF, vérification de rôle, protection account takeover)
- ✅ Support des rôles `client` et `creator`
- ✅ Onboarding créateur avec profil `pending`
- ✅ Refus automatique des comptes `staff/admin` via OAuth
- ✅ Gestion des conflits de rôle avec messages explicites
- ✅ Transaction atomique pour création utilisateur + profil créateur

**Structure actuelle :**
```
users table:
  - google_id (string, nullable, unique, indexed)
  
GoogleAuthController:
  - redirect() : Redirige vers Google OAuth
  - callback() : Gère le callback et création/connexion
```

**Logique métier validée :**
- Vérification du `state` OAuth (CSRF protection)
- Stockage du rôle demandé en session (`google_auth_role`)
- Liaison fiable `google_id` ↔ `user_id`
- Refus de conversion automatique de rôle
- Création conditionnelle du `CreatorProfile` selon le rôle

---

## 🗄️ SCHÉMA DE BASE DE DONNÉES

### Table `oauth_accounts` (nouvelle table pivot)

```sql
CREATE TABLE oauth_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,              -- 'google', 'apple', 'facebook'
    provider_user_id VARCHAR(255) NOT NULL,    -- ID unique du provider
    provider_email VARCHAR(255) NULL,           -- Email du provider (peut être masqué pour Apple)
    provider_name VARCHAR(255) NULL,            -- Nom du provider
    access_token TEXT NULL,                     -- Token d'accès (optionnel, pour API futures)
    refresh_token TEXT NULL,                    -- Refresh token (optionnel)
    token_expires_at TIMESTAMP NULL,            -- Expiration du token
    is_primary BOOLEAN DEFAULT FALSE,          -- Compte OAuth principal (un seul par user)
    metadata JSON NULL,                         -- Données supplémentaires (avatar, etc.)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    -- Contraintes
    UNIQUE KEY unique_provider_user (provider, provider_user_id),
    UNIQUE KEY unique_user_primary (user_id, is_primary) WHERE is_primary = TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_provider (provider),
    INDEX idx_provider_user_id (provider_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Explication des colonnes :**

| Colonne | Type | Description |
|---------|------|-------------|
| `provider` | VARCHAR(50) | Identifiant du provider : `google`, `apple`, `facebook` |
| `provider_user_id` | VARCHAR(255) | ID unique retourné par le provider (ex: Google ID, Apple Subject) |
| `provider_email` | VARCHAR(255) NULL | Email du provider (peut être `null` pour Apple si masqué) |
| `provider_name` | VARCHAR(255) NULL | Nom complet du provider |
| `access_token` | TEXT NULL | Token d'accès OAuth (optionnel, pour futures intégrations API) |
| `refresh_token` | TEXT NULL | Refresh token (optionnel, pour renouvellement automatique) |
| `token_expires_at` | TIMESTAMP NULL | Date d'expiration du token |
| `is_primary` | BOOLEAN | Compte OAuth principal (un seul `TRUE` par utilisateur) |
| `metadata` | JSON NULL | Données supplémentaires (avatar URL, locale, etc.) |

**Contraintes d'unicité :**
- `unique_provider_user` : Un même `provider_user_id` ne peut être lié qu'à un seul utilisateur
- `unique_user_primary` : Un utilisateur ne peut avoir qu'un seul compte OAuth marqué comme `primary`

**Cas d'usage :**
- Un utilisateur peut avoir plusieurs comptes OAuth (Google + Apple + Facebook)
- Un utilisateur peut avoir plusieurs comptes du même provider (ex: 2 comptes Google) si nécessaire
- Le compte `primary` est utilisé pour l'affichage dans le profil utilisateur

---

## 🏛️ ARCHITECTURE DU NOUVEAU MODULE

### Structure des fichiers (sans modifier l'existant)

```
app/
├── Http/
│   └── Controllers/
│       └── Auth/
│           ├── GoogleAuthController.php          # EXISTANT (ne pas modifier)
│           └── SocialAuthController.php         # NOUVEAU (générique multi-providers)
│
├── Models/
│   ├── User.php                                  # EXISTANT (ajouter relation oauthAccounts)
│   └── OauthAccount.php                          # NOUVEAU
│
├── Services/
│   └── SocialAuthService.php                    # NOUVEAU (logique métier centralisée)
│
├── Http/
│   └── Requests/
│       └── SocialAuthRequest.php                 # NOUVEAU (validation)
│
database/
└── migrations/
    └── YYYY_MM_DD_HHMMSS_create_oauth_accounts_table.php  # NOUVEAU

config/
└── services.php                                   # EXISTANT (ajouter Apple, Facebook)
```

### Principe de cohabitation

**Module Google Auth v1 (existant) :**
- ✅ Reste fonctionnel et inchangé
- ✅ Continue d'utiliser `users.google_id`
- ✅ Routes : `/auth/google/redirect`, `/auth/google/callback`

**Module Social Auth v2 (nouveau) :**
- ✅ Nouveau contrôleur générique `SocialAuthController`
- ✅ Utilise la table `oauth_accounts`
- ✅ Routes : `/auth/{provider}/redirect`, `/auth/{provider}/callback`
- ✅ Supporte : `google`, `apple`, `facebook`

**Migration progressive :**
- Phase 1 : Implémenter le nouveau module en parallèle
- Phase 2 : Migrer progressivement les utilisateurs Google vers `oauth_accounts`
- Phase 3 : Désactiver l'ancien module Google Auth v1 (optionnel)

---

## 🔄 FLUX OAuth DÉTAILLÉ

### Flux générique multi-providers

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
       │ 1. GET /auth/{provider}/redirect?role=client&context=boutique
       ▼
┌─────────────────────┐
│ SocialAuthController │
│    redirect()        │
└──────┬──────────────┘
       │
       │ 2. Valider provider (google|apple|facebook)
       │ 3. Valider rôle (client|creator)
       │ 4. Valider contexte (boutique uniquement)
       │ 5. Générer state CSRF (40 caractères)
       │ 6. Stocker en session :
       │    - oauth_state
       │    - oauth_provider
       │    - oauth_role
       │    - social_login_context
       │
       │ 7. Rediriger vers provider OAuth avec state
       ▼
┌─────────────────┐
│ Provider OAuth   │
│ (Google/Apple/   │
│  Facebook)       │
└──────┬───────────┘
       │
       │ 8. Utilisateur s'authentifie
       │ 9. Provider redirige vers callback avec code + state
       ▼
┌─────────────────────┐
│ SocialAuthController │
│    callback()        │
└──────┬──────────────┘
       │
       │ 10. Vérifier state CSRF
       │ 11. Récupérer provider depuis session
       │ 12. Appeler Socialite::driver(provider)->user()
       │ 13. Extraire :
       │     - provider_user_id
       │     - email (peut être null pour Apple)
       │     - name
       │     - metadata (avatar, etc.)
       │
       │ 14. Appeler SocialAuthService::handleCallback()
       ▼
┌─────────────────────┐
│ SocialAuthService   │
│  handleCallback()   │
└──────┬──────────────┘
       │
       │ 15. Chercher OauthAccount par (provider, provider_user_id)
       │
       │ SI OauthAccount existe :
       │   16a. Récupérer User via oauthAccount->user_id
       │   16b. Vérifier cohérence email (si disponible)
       │   16c. Vérifier rôle (refus si conflit)
       │   16d. Vérifier statut (refus si staff/admin)
       │
       │ SINON :
       │   17a. Chercher User par email (si email disponible)
       │   17b. SI User existe :
       │        - Vérifier conflit de rôle
       │        - Vérifier statut staff/admin
       │        - Créer OauthAccount lié à User existant
       │   17c. SINON (nouvel utilisateur) :
       │        - Créer User avec rôle demandé
       │        - Créer OauthAccount
       │        - Si rôle = creator : Créer CreatorProfile (pending)
       │
       │ 18. Connecter l'utilisateur (Auth::login)
       │ 19. Régénérer session
       │ 20. Gérer onboarding créateur (si applicable)
       │ 21. Rediriger selon rôle
       ▼
┌─────────────┐
│  Dashboard  │
└─────────────┘
```

### Spécificités par provider

#### Google
- ✅ Email toujours disponible
- ✅ `provider_user_id` = Google ID (ex: `123456789`)
- ✅ `provider_email` = Email Google
- ✅ `provider_name` = Nom complet Google

#### Apple (Sign in with Apple)
- ⚠️ Email peut être masqué (`private relay`)
- ✅ `provider_user_id` = Apple Subject (ex: `001234.abc...`)
- ⚠️ `provider_email` peut être `null` si masqué
- ✅ `provider_name` = Nom complet (si fourni lors de la première connexion)
- ⚠️ **Gestion spéciale** : Si email masqué, utiliser `provider_user_id` comme identifiant principal

#### Facebook
- ✅ Email généralement disponible
- ✅ `provider_user_id` = Facebook ID (ex: `123456789`)
- ✅ `provider_email` = Email Facebook
- ✅ `provider_name` = Nom complet Facebook

---

## 💻 EXEMPLE DE CONTRÔLEUR GÉNÉRIQUE

### `app/Http/Controllers/Auth/SocialAuthController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\Traits\HandlesAuthRedirect;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Contrôleur générique pour l'authentification sociale multi-providers
 * 
 * Supporte : Google, Apple, Facebook
 * 
 * Routes :
 * - GET /auth/{provider}/redirect?role=client&context=boutique
 * - GET /auth/{provider}/callback
 */
class SocialAuthController extends Controller
{
    use HandlesAuthRedirect;

    protected SocialAuthService $socialAuthService;

    // Providers autorisés
    protected const ALLOWED_PROVIDERS = ['google', 'apple', 'facebook'];

    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
    }

    /**
     * Redirige vers le provider OAuth
     * 
     * @param Request $request
     * @param string $provider Provider OAuth (google|apple|facebook)
     * @param string|null $role Rôle demandé : 'client' ou 'creator' (défaut: 'client')
     * @return RedirectResponse
     */
    public function redirect(Request $request, string $provider, ?string $role = 'client'): RedirectResponse
    {
        // Valider le provider
        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return redirect()->route('login', ['context' => 'boutique'])
                ->with('error', 'Provider OAuth non supporté.');
        }

        // Valider et normaliser le rôle
        if (!in_array($role, ['client', 'creator'], true)) {
            $role = 'client';
        }

        // Stocker le rôle en session
        session(['oauth_role' => $role]);

        // Récupérer le contexte
        $context = $request->query('context');

        // SÉCURITÉ : Refuser l'espace équipe
        if ($context === 'equipe') {
            return redirect()->route('login', ['context' => 'equipe'])
                ->with('error', 'La connexion sociale n\'est pas disponible pour l\'espace équipe.');
        }

        // Stocker le contexte (uniquement boutique)
        session(['social_login_context' => 'boutique']);

        // Générer et stocker le state CSRF
        $state = Str::random(40);
        session([
            'oauth_state' => $state,
            'oauth_provider' => $provider,
        ]);

        // Vérifier la configuration du provider
        $providerConfig = config("services.{$provider}");
        if (empty($providerConfig['client_id']) || empty($providerConfig['client_secret'])) {
            \Log::warning("OAuth {$provider}: Configuration incomplète");
            return redirect()->route('login', ['context' => 'boutique'])
                ->with('error', "La connexion {$provider} n'est pas configurée.");
        }

        try {
            // Configuration spécifique selon le provider
            $socialite = Socialite::driver($provider);

            // Apple nécessite des scopes spécifiques
            if ($provider === 'apple') {
                $socialite->scopes(['name', 'email']);
            }

            // Ajouter le state CSRF
            return $socialite
                ->with(['state' => $state])
                ->redirect();
        } catch (\Exception $e) {
            \Log::error("OAuth {$provider} redirect error", ['error' => $e->getMessage()]);
            session()->forget(['oauth_state', 'oauth_provider', 'oauth_role', 'social_login_context']);
            
            return redirect()->route('login', ['context' => 'boutique'])
                ->with('error', "La connexion {$provider} n'est pas disponible pour le moment.");
        }
    }

    /**
     * Gère le callback OAuth
     * 
     * @param Request $request
     * @param string $provider Provider OAuth
     * @return RedirectResponse
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        // Valider le provider
        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return redirect()->route('login')
                ->with('error', 'Provider OAuth non supporté.');
        }

        // Vérifier le state CSRF
        $sessionState = session('oauth_state');
        $requestState = $request->query('state');
        $sessionProvider = session('oauth_provider');

        if (!$sessionState || $sessionState !== $requestState || $sessionProvider !== $provider) {
            session()->forget(['oauth_state', 'oauth_provider', 'oauth_role', 'social_login_context']);
            return redirect()->route('login')
                ->with('error', 'Erreur de sécurité lors de la connexion. Veuillez réessayer.');
        }

        // Nettoyer le state après validation
        session()->forget(['oauth_state', 'oauth_provider']);

        try {
            // Récupérer l'utilisateur du provider
            $providerUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            \Log::error("OAuth {$provider} callback error", ['error' => $e->getMessage()]);
            return redirect()->route('login')
                ->with('error', "Erreur lors de la connexion avec {$provider}. Veuillez réessayer.");
        }

        // Récupérer le contexte et le rôle depuis la session
        $context = session('social_login_context', 'boutique');
        $requestedRole = session('oauth_role', 'client');
        session()->forget(['social_login_context', 'oauth_role']);

        // Normaliser le rôle
        $requestedRoleSlug = $requestedRole === 'creator' ? 'createur' : 'client';

        // Refuser l'espace équipe
        if ($context === 'equipe') {
            return redirect()->route('login', ['context' => 'equipe'])
                ->with('error', 'La connexion sociale n\'est pas disponible pour l\'espace équipe.');
        }

        // Déléguer la logique métier au service
        try {
            $user = $this->socialAuthService->handleCallback(
                provider: $provider,
                providerUser: $providerUser,
                requestedRole: $requestedRoleSlug,
                context: $context
            );
        } catch (\App\Exceptions\OAuthException $e) {
            return redirect()->route('login', ['context' => $context])
                ->with('error', $e->getMessage())
                ->with('conversion_offer', $e->getConversionOffer());
        } catch (\Exception $e) {
            \Log::error("OAuth {$provider} service error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('login', ['context' => $context])
                ->with('error', 'Erreur lors de la création de votre compte. Veuillez réessayer.');
        }

        // Vérifier le statut de l'utilisateur
        if (isset($user->status) && $user->status !== 'active') {
            return redirect()->route('login')
                ->with('error', 'Votre compte est désactivé. Contactez l\'administrateur.');
        }

        // Connecter l'utilisateur
        Auth::login($user, true);
        $request->session()->regenerate();

        // Gérer l'onboarding créateur
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['createur', 'creator'])) {
            $creatorProfile = $user->creatorProfile;
            
            if (!$creatorProfile) {
                return redirect()->route('creator.register')
                    ->with('info', 'Veuillez compléter votre profil créateur.');
            }
            
            if ($creatorProfile->isPending()) {
                return redirect()->route('creator.pending')
                    ->with('status', 'Votre compte créateur est en attente de validation.');
            }
            
            if ($creatorProfile->isSuspended()) {
                return redirect()->route('creator.suspended')
                    ->with('error', 'Votre compte créateur a été suspendu.');
            }
        }

        // Rediriger selon le rôle
        return redirect($this->getRedirectPath($user));
    }
}
```

---

## 🔒 CHECKLIST SÉCURITÉ

### ✅ Protection CSRF

- [x] Génération d'un `state` aléatoire (40 caractères minimum)
- [x] Stockage du `state` en session avant redirection
- [x] Vérification du `state` dans le callback
- [x] Suppression du `state` après validation
- [x] Refus si `state` manquant ou invalide

### ✅ Protection Account Takeover

- [x] Vérification de l'unicité `(provider, provider_user_id)`
- [x] Refus si `provider_user_id` déjà lié à un autre utilisateur
- [x] Vérification de cohérence email (si disponible)
- [x] Refus si email déjà associé à un autre compte OAuth du même provider
- [x] Logging des tentatives suspectes

### ✅ Protection des rôles

- [x] Refus de conversion automatique de rôle
- [x] Vérification du rôle demandé vs rôle existant
- [x] Message d'erreur explicite en cas de conflit
- [x] Refus des comptes `staff/admin` via OAuth (email + mot de passe uniquement)
- [x] Validation du paramètre `role` (client|creator uniquement)

### ✅ Protection du contexte

- [x] Refus de l'espace `equipe` via OAuth
- [x] Validation du contexte (boutique uniquement)
- [x] Stockage sécurisé du contexte en session

### ✅ Gestion des erreurs

- [x] Try-catch autour des appels Socialite
- [x] Logging des erreurs OAuth
- [x] Messages d'erreur génériques pour l'utilisateur
- [x] Messages détaillés dans les logs (sans exposer de secrets)

### ✅ Gestion des tokens

- [x] Stockage optionnel des tokens (pas obligatoire)
- [x] Chiffrement des tokens sensibles (si stockés)
- [x] Expiration des tokens gérée
- [x] Suppression des tokens lors de la déconnexion du compte OAuth

### ✅ Validation des données

- [x] Validation du provider (whitelist)
- [x] Validation du rôle (whitelist)
- [x] Validation du contexte (whitelist)
- [x] Sanitisation des données du provider
- [x] Validation de l'email (format, null accepté pour Apple)

### ✅ Spécificités Apple

- [x] Gestion de l'email masqué (private relay)
- [x] Utilisation du `provider_user_id` comme identifiant principal si email masqué
- [x] Support des scopes Apple (`name`, `email`)

---

## 📅 PLAN DE MIGRATION PROGRESSIVE

### Phase 1 : Implémentation du nouveau module (sans toucher à l'existant)

**Durée estimée :** 2-3 jours

**Tâches :**
1. Créer la migration `create_oauth_accounts_table`
2. Créer le modèle `OauthAccount` avec relations
3. Créer le service `SocialAuthService`
4. Créer le contrôleur `SocialAuthController`
5. Ajouter les routes génériques `/auth/{provider}/redirect` et `/auth/{provider}/callback`
6. Configurer les providers dans `config/services.php` (Apple, Facebook)
7. Tester avec un provider (ex: Facebook) en parallèle de Google

**Résultat :**
- Module Social Auth v2 fonctionnel
- Module Google Auth v1 toujours actif
- Aucun impact sur les utilisateurs existants

### Phase 2 : Migration des utilisateurs Google (optionnel)

**Durée estimée :** 1 jour

**Tâches :**
1. Créer une commande Artisan `migrate:google-to-oauth`
2. Pour chaque utilisateur avec `google_id` :
   - Créer un `OauthAccount` avec `provider='google'`
   - Marquer comme `is_primary=true`
   - Conserver `google_id` dans `users` (pour compatibilité)
3. Tester la migration sur un environnement de staging
4. Exécuter en production avec rollback possible

**Résultat :**
- Utilisateurs Google migrés vers `oauth_accounts`
- Compatibilité maintenue (les deux systèmes coexistent)

### Phase 3 : Désactivation de l'ancien module (optionnel, à long terme)

**Durée estimée :** 1 jour

**Tâches :**
1. Rediriger les routes `/auth/google/*` vers le nouveau module
2. Marquer `GoogleAuthController` comme `@deprecated`
3. Supprimer la colonne `google_id` de `users` (migration)
4. Supprimer `GoogleAuthController` (après période de transition)

**Résultat :**
- Module unifié multi-providers
- Code simplifié

---

## 🎯 DÉCISION STRATÉGIQUE

### 📌 RECOMMANDATION FRANCHE

**🔒 Ne pas implémenter maintenant**

**Raisons :**
1. ✅ Le module Google Auth v1 est **clôturé et validé**
2. ✅ Il répond aux besoins actuels (Google uniquement)
3. ✅ Aucun besoin immédiat pour Apple/Facebook
4. ✅ Complexité ajoutée sans bénéfice immédiat

### 🧭 PLANIFICATION RECOMMANDÉE

**Créer un nouveau module : "Social Auth v2 — Multi Providers"**

**Implémenter quand :**
- ✅ Le trafic utilisateur augmente significativement
- ✅ Les créateurs sont actifs et demandent Apple/Facebook
- ✅ Le besoin business est réel et mesurable
- ✅ L'équipe a la capacité de maintenir deux modules en parallèle

### 🧱 ARCHITECTURE PROPOSÉE (pour plus tard)

**Principe :** Cohabitation des deux modules

```
Google Auth v1 (existant)          Social Auth v2 (nouveau)
├── GoogleAuthController           ├── SocialAuthController
├── users.google_id                ├── oauth_accounts table
└── Routes dédiées                 └── Routes génériques
```

**Avantages :**
- ✅ Aucun risque pour le module existant
- ✅ Migration progressive possible
- ✅ Rollback facile si problème
- ✅ Tests isolés par module

### 📋 CHECKLIST AVANT IMPLÉMENTATION

Avant de commencer l'implémentation, vérifier :

- [ ] **Besoin business validé** : Demande réelle des utilisateurs/créateurs
- [ ] **ROI mesurable** : Impact attendu sur les inscriptions/connexions
- [ ] **Ressources disponibles** : Temps de développement + maintenance
- [ ] **Tests planifiés** : Environnement de staging + tests utilisateurs
- [ ] **Documentation prête** : Guide d'utilisation pour l'équipe

---

## 📝 CONCLUSION

Ce document fournit une **architecture complète et prête à l'emploi** pour le module Social Auth v2 multi-providers.

**État actuel :** Planification et conception terminées ✅  
**Prochaine étape :** Implémentation quand le besoin business est validé

**Fichiers de référence :**
- `ARCHITECTURE_SOCIAL_AUTH_V2_MULTI_PROVIDERS.md` (ce document)
- `DIAGNOSTIC_GOOGLE_OAUTH.md` (diagnostic du module existant)
- `CLOTURE_MODULE_GOOGLE_AUTH.md` (documentation du module v1)

---

**Date de création :** 2025-01-XX  
**Statut :** 📋 Planification complète — Prêt pour implémentation future  
**Auteur :** Architecture Laravel Senior


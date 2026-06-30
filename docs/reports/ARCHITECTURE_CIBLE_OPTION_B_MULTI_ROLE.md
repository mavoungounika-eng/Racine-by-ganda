# 🏗️ ARCHITECTURE CIBLE — OPTION B (MULTI-RÔLE)

## 📋 INFORMATIONS GÉNÉRALES

**Architecture cible :** Option B — Multi-Rôle  
**Principe :** Un utilisateur = plusieurs rôles (client + creator possible)  
**Date d'analyse :** 2025-12-19  
**Statut :** 📋 **ARCHITECTURE CIBLE — NON IMPLÉMENTÉE**

**⚠️ IMPORTANT :** Ce document décrit une architecture cible future. Le module Social Auth v2 actuel est **gelé** et utilise l'architecture actuelle (un rôle par utilisateur).

---

## 📊 RÉSUMÉ EXÉCUTIF

### Principe fondamental

**L'authentification identifie la personne. Les rôles définissent ce qu'elle peut faire.**

**Conséquence :**
- ✅ Un même utilisateur peut avoir plusieurs rôles simultanément
- ✅ Un utilisateur peut être **client ET créateur** avec un seul compte
- ✅ Le rôle `client` est **toujours présent** (base)
- ✅ Le rôle `creator` est une **surcouche** (ajout)

### Changements architecturaux

| Aspect | Actuel | Cible |
|--------|--------|-------|
| **Structure rôles** | `users.role_id` (1:1) | `role_user` pivot (many-to-many) |
| **Rôles multiples** | ❌ Non | ✅ Oui |
| **Client → Creator** | ❌ Conflit (refus) | ✅ Ajout automatique |
| **Complexité** | ✅ Simple | ⚠️ Plus complexe |

### Impact sur Social Auth v2

**✅ Compatible** avec modifications moyennes à majeures :

| Fichier | Modification | Impact |
|---------|--------------|--------|
| `SocialAuthService::validateRole()` | Ajouter rôle au lieu de refuser | ⚠️ **Majeur** |
| `SocialAuthService::createNewUserWithOAuth()` | Attacher rôles via pivot | ⚠️ Moyen |
| `User::roles()` | Relation many-to-many | ⚠️ **Majeur** |

**Estimation :** 2-3 jours de développement + tests

### Recommandation

**📋 PLANIFIER LA MIGRATION COMME PROJET SÉPARÉ**

- ✅ Après stabilisation complète de Social Auth v2 (post-48h)
- ✅ En coordination avec les besoins métier (fonctionnalité "Devenir créateur")
- ✅ Le module Social Auth v2 actuel reste **gelé et fonctionnel** ✅

---

---

## 🎯 PRINCIPE FONDAMENTAL

### Séparation Auth / Rôles

**L'authentification identifie la personne.**  
**Les rôles définissent ce qu'elle peut faire.**

**Conséquence :**
- ✅ Un même utilisateur peut avoir plusieurs rôles
- ✅ Un utilisateur peut être **client ET créateur** simultanément
- ✅ Une seule connexion, quel que soit le mode (formulaire, Google, Apple, Facebook)
- ✅ Le rôle `client` est **toujours présent** (base)
- ✅ Le rôle `creator` est une **surcouche** (ajout)

---

## 🧱 STRUCTURE LOGIQUE FINALE (CIBLE)

### Table `users`

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255) NULLABLE,  -- Nullable si OAuth only
    status ENUM('active', 'suspended', 'banned'),
    last_login_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE
);
```

**Changements par rapport à l'actuel :**
- ❌ Suppression de `role_id` (FK)
- ❌ Suppression de `role` (enum)
- ✅ Ajout de `last_login_at`
- ✅ `password` nullable (OAuth only)

---

### Table `roles`

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(255),  -- 'client', 'creator', 'staff', 'admin'
    slug VARCHAR(255) UNIQUE,
    description TEXT NULLABLE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Changements par rapport à l'actuel :**
- ✅ Structure similaire (peut être conservée)
- ✅ Rôles : `client`, `creator`, `staff`, `admin`

---

### Table pivot `role_user` (NOUVELLE)

```sql
CREATE TABLE role_user (
    id BIGINT UNSIGNED PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    role_id BIGINT UNSIGNED,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id)
);
```

**Fonction :**
- ✅ Permet à un utilisateur d'avoir plusieurs rôles
- ✅ Contrainte unique `(user_id, role_id)` pour éviter les doublons
- ✅ Cascade on delete (suppression automatique si user ou role supprimé)

---

### Table `creator_profiles`

```sql
CREATE TABLE creator_profiles (
    id BIGINT UNSIGNED PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    status ENUM('draft', 'pending', 'active', 'suspended'),
    shop_name VARCHAR(255),
    description TEXT NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);
```

**Changements par rapport à l'actuel :**
- ✅ Ajout statut `draft` (créateur non actif)
- ✅ Le rôle `creator` existe même si `status != 'active'`
- ✅ C'est `creator_profile.status` qui décide l'accès réel

**Règle importante :**
- ✅ Le rôle `creator` peut exister avec `status = 'draft'` ou `'pending'`
- ✅ L'accès dashboard créateur dépend de `status = 'active'`

---

## 🔐 AUTHENTIFICATION (RAPPEL — CE QUI NE BOUGE PAS)

### Principe

**Tous ces chemins mènent AU MÊME User :**

| Action | Résultat |
|--------|----------|
| Formulaire login | User |
| Google login | User |
| Apple login | User |
| Facebook login | User |

**➡️ Aucun rôle n'est forcé par l'auth**  
**➡️ Le rôle est un attribut métier, pas un mécanisme de login**

**Impact sur Social Auth v2 :**
- ✅ Le module Social Auth v2 actuel est **compatible** avec cette architecture
- ✅ L'authentification OAuth crée/connecte un User
- ✅ Les rôles sont gérés **après** l'authentification (métier)

---

## 🔁 SCÉNARIOS COMPLETS — CYCLE DE VIE UTILISATEUR

### 🟢 SCÉNARIO 1 — CLIENT CLASSIQUE

#### Étape 1 — Inscription

**Méthode :** Formulaire / Google / Apple / Facebook

**Résultat :**
- ✅ `User` créé
- ✅ `Role` attaché : `client` (via `role_user`)

**État :**
```
User: { id: 1, email: 'user@example.com' }
role_user: [
    { user_id: 1, role_id: client }
]
creator_profile: null
```

#### Étape 2 — Utilisation

- ✅ Peut acheter
- ✅ Peut gérer son compte
- ❌ N'a PAS accès à l'espace créateur

---

### 🟡 SCÉNARIO 2 — CLIENT → CRÉATEUR (UPGRADE)

#### Étape 1 — Action utilisateur

**Depuis son compte client :**
- Clic sur "Devenir créateur"

#### Étape 2 — Système

**Actions :**
1. ✅ Ajout rôle : `creator` (via `role_user`)
2. ✅ Création `creator_profile` avec `status = 'draft'`

**État :**
```
User: { id: 1, email: 'user@example.com' }
role_user: [
    { user_id: 1, role_id: client },
    { user_id: 1, role_id: creator }  // NOUVEAU
]
creator_profile: {
    user_id: 1,
    status: 'draft',  // Non actif
    shop_name: null,
    description: null
}
```

**➡️ À ce stade :**
- ✅ Il est **client + créateur**
- ❌ Mais créateur **non actif** (`status = 'draft'`)
- ✅ Peut toujours acheter (client)
- ❌ Ne peut pas vendre (creator non actif)

#### Étape 3 — Onboarding créateur

**Formulaire :**
- Nom boutique
- Description
- Documents
- Politique

**Résultat :**
- ✅ `creator_profile.status = 'pending'`

**État :**
```
creator_profile: {
    user_id: 1,
    status: 'pending',  // En attente validation
    shop_name: 'Ma Boutique',
    description: '...',
    ...
}
```

**➡️ À ce stade :**
- ✅ Il est **client + créateur**
- ❌ Mais créateur **en attente** (`status = 'pending'`)
- ✅ Peut toujours acheter (client)
- ❌ Ne peut pas vendre (creator en attente)

#### Étape 4 — Validation admin

**Admin valide :**
- ✅ `creator_profile.status = 'active'`

**État :**
```
creator_profile: {
    user_id: 1,
    status: 'active',  // ACTIF
    shop_name: 'Ma Boutique',
    ...
}
```

**🎉 L'utilisateur peut vendre**

**➡️ À ce stade :**
- ✅ Il est **client + créateur**
- ✅ Créateur **actif** (`status = 'active'`)
- ✅ Peut acheter (client)
- ✅ Peut vendre (creator actif)

---

### 🔴 SCÉNARIO 3 — CRÉATEUR SUSPENDU

**Action admin :**
- ✅ `creator_profile.status = 'suspended'`

**État :**
```
role_user: [
    { user_id: 1, role_id: client },
    { user_id: 1, role_id: creator }  // Rôle toujours présent
]
creator_profile: {
    user_id: 1,
    status: 'suspended'  // SUSPENDU
}
```

**Conséquences :**
- ✅ Peut toujours acheter (client)
- ❌ Ne peut plus vendre (creator suspendu)
- ❌ Accès dashboard créateur bloqué (middleware `creator.active`)

**Règle d'or :**
- ✅ Le rôle `creator` **n'est jamais supprimé**
- ✅ C'est `creator_profile.status` qui contrôle l'accès

---

## 🧠 RÈGLE D'OR (À NE JAMAIS VIOLER)

### ❌ NE JAMAIS SUPPRIMER LE RÔLE CLIENT

### ✅ LE CRÉATEUR EST UNE SURCOUCHE

**Conséquences :**
- ✅ Panier créateur (client peut acheter)
- ✅ Achat chez d'autres créateurs (client peut acheter)
- ✅ UX marketplace fluide (un seul compte)

**Exemple concret :**
```
Utilisateur créateur actif :
- Rôles : [client, creator]
- Peut acheter ses propres produits (client)
- Peut vendre ses produits (creator)
- Un seul compte, une seule connexion
```

---

## 🧭 ROUTING & MIDDLEWARE (CLÉ DE LA SÉCURITÉ)

### Middleware `auth`

**Fonction :** Vérifie que l'utilisateur est authentifié

```php
Route::middleware('auth')->group(function () {
    // Routes protégées
});
```

---

### Middleware `role:creator`

**Fonction :** Vérifie que l'utilisateur a le rôle `creator`

```php
// Exemple d'implémentation
public function handle($request, Closure $next, string $role)
{
    if (!$request->user()->hasRole($role)) {
        abort(403, 'Accès refusé');
    }
    return $next($request);
}
```

**Utilisation :**
```php
Route::middleware(['auth', 'role:creator'])->group(function () {
    // Routes créateur (mais pas forcément actif)
});
```

---

### Middleware `creator.active`

**Fonction :** Vérifie que le créateur a un profil actif

```php
// Exemple d'implémentation
public function handle($request, Closure $next)
{
    $user = $request->user();
    
    if (!$user->hasRole('creator')) {
        abort(403, 'Accès refusé');
    }
    
    $creatorProfile = $user->creatorProfile;
    
    if (!$creatorProfile || $creatorProfile->status !== 'active') {
        return redirect()->route('creator.pending')
            ->with('error', 'Votre compte créateur n\'est pas actif.');
    }
    
    return $next($request);
}
```

**Utilisation :**
```php
Route::middleware(['auth', 'role:creator', 'creator.active'])->group(function () {
    Route::get('/creator/dashboard', ...);
    Route::get('/creator/products', ...);
    // Routes nécessitant un créateur actif
});
```

---

### Exemple complet

```php
// Route accessible à tous les créateurs (même draft/pending)
Route::middleware(['auth', 'role:creator'])
    ->group(function () {
        Route::get('/creator/onboarding', [CreatorOnboardingController::class, 'index']);
    });

// Route accessible uniquement aux créateurs actifs
Route::middleware(['auth', 'role:creator', 'creator.active'])
    ->group(function () {
        Route::get('/creator/dashboard', [CreatorDashboardController::class, 'index']);
        Route::get('/creator/products', [CreatorProductController::class, 'index']);
    });
```

---

## 🔐 MATRICE D'ACCÈS

| Cas | Accès client | Accès créateur |
|-----|--------------|----------------|
| **Client simple** | ✅ | ❌ |
| **Client + créateur (draft)** | ✅ | ❌ (rôle présent mais non actif) |
| **Client + créateur (pending)** | ✅ | ❌ (rôle présent mais en attente) |
| **Client + créateur (active)** | ✅ | ✅ (rôle présent et actif) |
| **Client + créateur (suspended)** | ✅ | ❌ (rôle présent mais suspendu) |

**Logique :**
- ✅ Le rôle `client` donne toujours accès client
- ✅ Le rôle `creator` donne accès créateur **SEULEMENT** si `creator_profile.status = 'active'`

---

## 📊 COMPARAISON ARCHITECTURE ACTUELLE vs CIBLE

### Architecture actuelle (Social Auth v2)

**Structure :**
```
users
  - role_id (FK → roles.id)  // UN SEUL RÔLE
  - role (enum)               // Rôle direct

Relation: User belongsTo Role (1:1)
```

**Limitations :**
- ❌ Un utilisateur ne peut avoir qu'un seul rôle
- ❌ Conflit si client veut devenir créateur (refus OAuth)
- ❌ Conversion de rôle nécessaire (processus manuel)

**Avantages :**
- ✅ Simple et direct
- ✅ Performant (pas de jointure pivot)
- ✅ Déjà implémenté et gelé

---

### Architecture cible (Option B)

**Structure :**
```
users
  - (pas de role_id)
  - (pas de role enum)

role_user (pivot)
  - user_id
  - role_id

Relation: User belongsToMany Role (many-to-many)
```

**Avantages :**
- ✅ Un utilisateur peut avoir plusieurs rôles
- ✅ Client peut devenir créateur sans conflit
- ✅ Rôle client toujours présent (base)
- ✅ Rôle créateur = surcouche
- ✅ UX marketplace fluide

**Complexité :**
- ⚠️ Plus complexe (table pivot)
- ⚠️ Middleware plus sophistiqué
- ⚠️ Migration nécessaire

---

## 🔄 IMPACT SUR SOCIAL AUTH V2

### Compatibilité

**✅ Le module Social Auth v2 actuel est compatible avec l'architecture cible**

**Raisons :**
1. ✅ L'authentification OAuth crée/connecte un `User` (sans rôle)
2. ✅ Les rôles sont gérés **après** l'authentification (métier)
3. ✅ La logique OAuth ne dépend pas de la structure des rôles

### Modifications nécessaires (si migration vers Option B)

#### 1. SocialAuthService::createNewUserWithOAuth()

**Fichier :** `app/Services/SocialAuthService.php` (ligne ~280)

**ACTUEL (un seul rôle) :**
```php
$user = User::create([
    'name' => $name,
    'email' => $email,
    'password' => Hash::make(Str::random(32)),
    'role_id' => $role->id,  // ❌ À supprimer
    'email_verified_at' => now(),
]);
```

**CIBLE (plusieurs rôles) :**
```php
$user = User::create([
    'name' => $name,
    'email' => $email,
    'password' => Hash::make(Str::random(32)),
    // ❌ Supprimer 'role_id'
    'email_verified_at' => now(),
]);

// ✅ Attacher rôle via pivot (toujours client par défaut)
$clientRole = Role::where('slug', 'client')->first();
$user->roles()->attach($clientRole->id);

// Si rôle demandé = creator, ajouter aussi creator
if ($requestedRole === 'creator' || $requestedRole === 'createur') {
    $creatorRole = Role::where('slug', 'creator')->first();
    $user->roles()->attach($creatorRole->id);
}
```

**Impact :** ⚠️ Modification de la logique de création utilisateur

---

#### 2. SocialAuthService::validateRole()

**Fichier :** `app/Services/SocialAuthService.php` (ligne ~150)

**ACTUEL (un seul rôle) :**
```php
$currentRoleSlug = $user->getRoleSlug();  // ❌ Un seul rôle

if ($currentRoleSlug !== $requestedRole) {
    throw new OAuthException(
        "Vous êtes déjà inscrit en tant que {$currentRoleSlug}. " .
        "Vous ne pouvez pas vous connecter en tant que {$requestedRole}."
    );
}
```

**CIBLE (plusieurs rôles) :**
```php
// ✅ Vérifier si l'utilisateur a déjà le rôle demandé
$hasRequestedRole = $user->hasRole($requestedRole);

if ($hasRequestedRole) {
    // ✅ Rôle déjà présent, OK (pas de conflit)
    return;
}

// ✅ Si pas de rôle, vérifier si conflit avec rôle existant
// Exemple : Si client veut devenir creator, OK (ajout)
// Si creator veut devenir client, OK (déjà client normalement)
// Si staff/admin, refuser OAuth

if ($user->hasAnyRole(['staff', 'admin'])) {
    throw new OAuthException(
        "Les comptes staff/admin ne peuvent pas utiliser l'authentification OAuth."
    );
}

// ✅ Si pas de conflit, ajouter le rôle
$role = Role::where('slug', $requestedRole)->first();
$user->roles()->attach($role->id);
```

**Impact :** ⚠️ **Changement majeur** : Plus de refus de conflit, ajout de rôle si absent

**Règle importante :**
- ✅ Client peut devenir creator (ajout rôle)
- ✅ Creator peut se connecter en tant que client (rôle client toujours présent)
- ❌ Staff/Admin toujours refusé

---

#### 3. SocialAuthService::validateUserStatus()

**Fichier :** `app/Services/SocialAuthService.php` (ligne ~120)

**ACTUEL :**
```php
$roleSlug = $user->getRoleSlug();

if (in_array($roleSlug, ['staff', 'admin'], true)) {
    throw new OAuthException(
        "Les comptes staff/admin ne peuvent pas utiliser l'authentification OAuth."
    );
}
```

**CIBLE :**
```php
// ✅ Vérifier si l'utilisateur a un des rôles interdits
if ($user->hasAnyRole(['staff', 'admin'])) {
    throw new OAuthException(
        "Les comptes staff/admin ne peuvent pas utiliser l'authentification OAuth."
    );
}
```

**Impact :** ⚠️ Modification mineure (méthode différente, logique identique)

---

#### 4. SocialAuthService::handleExistingUser()

**Fichier :** `app/Services/SocialAuthService.php` (ligne ~200)

**ACTUEL :**
```php
// Vérifier conflit de rôle
$currentRoleSlug = $user->getRoleSlug();
if ($currentRoleSlug !== $requestedRole) {
    throw new OAuthException(...);
}
```

**CIBLE :**
```php
// ✅ Vérifier si rôle déjà présent
if (!$user->hasRole($requestedRole)) {
    // Ajouter le rôle si pas de conflit
    if (!$user->hasAnyRole(['staff', 'admin'])) {
        $role = Role::where('slug', $requestedRole)->first();
        $user->roles()->attach($role->id);
    }
}
```

**Impact :** ⚠️ **Changement majeur** : Plus de refus, ajout automatique de rôle

---

#### 5. Modèle User - Relations

**Fichier :** `app/Models/User.php`

**ACTUEL :**
```php
public function roleRelation()
{
    return $this->belongsTo(Role::class, 'role_id');
}
```

**CIBLE :**
```php
public function roles()
{
    return $this->belongsToMany(Role::class, 'role_user')
        ->withTimestamps();
}

// Méthodes utilitaires à adapter
public function hasRole(string $role): bool
{
    return $this->roles()->where('slug', $role)->exists();
}

public function hasAnyRole(array $roles): bool
{
    return $this->roles()->whereIn('slug', $roles)->exists();
}
```

**Impact :** ⚠️ **Changement majeur** : Relation many-to-many au lieu de belongsTo

---

### Résumé des modifications

| Fichier | Modification | Impact |
|---------|--------------|--------|
| `SocialAuthService::createNewUserWithOAuth()` | Attacher rôles via pivot | ⚠️ Moyen |
| `SocialAuthService::validateRole()` | Ajouter rôle au lieu de refuser | ⚠️ **Majeur** |
| `SocialAuthService::handleExistingUser()` | Ajouter rôle au lieu de refuser | ⚠️ **Majeur** |
| `User::roles()` | Relation many-to-many | ⚠️ **Majeur** |
| `User::hasRole()` | Vérifier via pivot | ⚠️ Moyen |

**Impact global :** ⚠️ **Modifications moyennes à majeures** nécessaires

**Estimation :** 2-3 jours de développement + tests

---

## 📌 CE QUE TU AS GAGNÉ AVEC OPTION B

### Avantages métier

- ✅ **Un seul compte** : Client peut devenir créateur sans créer un nouveau compte
- ✅ **Zéro friction UX** : Pas de conflit de rôle, pas de conversion
- ✅ **UX marketplace fluide** : Un utilisateur peut acheter ET vendre avec le même compte

### Avantages techniques

- ✅ **Compatible OAuth** : Social Auth v2 compatible (modifications mineures)
- ✅ **Compatible abonnement futur** : Rôles multiples facilitent les abonnements
- ✅ **Compatible BI & scoring** : Analyse par rôle plus fine
- ✅ **Architecture scalable** : Facile d'ajouter de nouveaux rôles

### Avantages standards

- ✅ **Standard marketplace professionnelle** : Architecture courante (Etsy, Amazon Seller, etc.)

---

## ⚠️ CONSIDÉRATIONS IMPORTANTES

### Module Social Auth v2 actuel

**Statut :** ✅ **GELÉ ET EN PRODUCTION**

**Décision stratégique :**
- ❌ **Ne pas modifier** le module Social Auth v2 actuel
- ✅ **Planifier** la migration vers Option B comme projet séparé
- ✅ **Architecture cible** documentée pour référence future

### Migration future (si décidée)

**Phases recommandées :**

1. **Phase 1 : Préparation**
   - Créer table pivot `role_user`
   - Migrer données existantes (un rôle → pivot)
   - Adapter modèles User et Role

2. **Phase 2 : Adaptation Social Auth v2**
   - Modifier `SocialAuthService` (attacher rôles via pivot)
   - Adapter validation des rôles
   - Tests de non-régression

3. **Phase 3 : Middleware**
   - Créer middleware `role:creator`
   - Créer middleware `creator.active`
   - Adapter routes existantes

4. **Phase 4 : Fonctionnalité "Devenir créateur"**
   - Interface utilisateur
   - Logique métier (ajout rôle + création profil draft)
   - Onboarding créateur

---

## 🎯 CONCLUSION

### Architecture cible validée

**✅ L'architecture Option B (Multi-Rôle) est une excellente cible pour l'évolution future du projet.**

**Avantages :**
- ✅ UX marketplace professionnelle
- ✅ Compatible avec Social Auth v2 (modifications mineures)
- ✅ Scalable et maintenable
- ✅ Standard industrie

### Recommandation

**📋 PLANIFIER LA MIGRATION COMME PROJET SÉPARÉ**

**Timing :**
- ✅ Après stabilisation complète de Social Auth v2 (post-48h)
- ✅ Après validation définitive du module
- ✅ En coordination avec les besoins métier (fonctionnalité "Devenir créateur")

**Le module Social Auth v2 actuel reste gelé et fonctionnel** ✅

---

**Date d'analyse :** 2025-12-19  
**Statut :** 📋 **ARCHITECTURE CIBLE — DOCUMENTÉE POUR RÉFÉRENCE FUTURE**


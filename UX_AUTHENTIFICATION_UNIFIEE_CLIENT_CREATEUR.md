# 🎨 UX AUTHENTIFICATION UNIFIÉE — CLIENT & CRÉATEUR

## 📋 INFORMATIONS GÉNÉRALES

**Date :** 2025-12-19  
**Objectif :** Implémenter une UX d'authentification claire et unifiée pour clients et créateurs  
**Contraintes :** Aucune modification de la base de données, aucun changement du module Social Auth v2 (gelé)

---

## 🎯 PRINCIPES FONDAMENTAUX

### ✅ Principes non négociables

1. **Un seul compte utilisateur** — `users.id` immuable
2. **Plusieurs moyens de connexion** — Formulaire, Google, Apple, Facebook
3. **Même logique pour CLIENT et CRÉATEUR** — Pas de séparation technique
4. **Aucune perte d'historique** — Garanti par l'architecture
5. **Messages UX explicites** — Rassurer l'utilisateur

### 🧠 Règle UX d'or

> **"Acheter et vendre se fait avec un seul compte. Vous ne perdez jamais vos données."**

---

## 🔐 ÉCRAN 1 — CONNEXION UNIFIÉE (`/login`)

### Structure de la page

**URL :** `/login`  
**Contrôleur :** `LoginController@showLoginForm`  
**Vue :** `auth.login-unified` (nouvelle vue)

### Contenu visible

#### 1. En-tête

```
┌─────────────────────────────────────┐
│  Connexion                          │
│  Accédez à votre espace personnel  │
└─────────────────────────────────────┘
```

**Message clé (important UX) :**
```
💡 Un seul compte suffit. Vous pouvez acheter et vendre avec le même compte.
```

**Affichage :**
- Badge informatif (fond léger, texte rassurant)
- Icône : `fa-info-circle` ou `fa-shield-alt`
- Couleur : `#D4A574` (racine-orange)

#### 2. Formulaire de connexion

**Champs :**
- Email (obligatoire)
- Mot de passe (obligatoire)
- "Se souvenir de moi" (checkbox)
- Lien "Mot de passe oublié ?"

**Bouton principal :**
```
┌─────────────────────────────────────┐
│  Se connecter                        │
└─────────────────────────────────────┘
```

#### 3. Boutons OAuth (Social Auth v2)

**Titre :** "Ou continuer avec"

**Boutons :**
```
┌─────────────────────────────────────┐
│  [G] Continuer avec Google          │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│  [🍎] Continuer avec Apple          │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│  [f] Continuer avec Facebook        │
└─────────────────────────────────────┘
```

**Comportement :**
- Tous les boutons OAuth utilisent Social Auth v2
- Route : `/auth/{provider}/redirect` (sans paramètre `role`)
- Le système détecte automatiquement le rôle lors du callback

#### 4. Lien inscription

```
Vous n'avez pas de compte ? Créer un compte
```

**Lien vers :** `/register`

---

### Messages UX à afficher

#### ✅ Connexion réussie

**Message flash :**
```
✅ Vous êtes connecté avec succès.
```

**Redirection :**
- Client → `/compte` (dashboard client)
- Créateur (pending) → `/createur/pending` avec message
- Créateur (active) → `/createur/dashboard`
- Créateur (suspended) → `/createur/suspended` avec message

#### ⚠️ Créateur en attente

**Message flash :**
```
⏳ Votre compte créateur est en cours de validation.
Vous pouvez continuer à acheter pendant ce temps.
```

**Redirection :** `/createur/pending`

#### 🛑 Créateur suspendu

**Message flash :**
```
⚠️ Votre activité de vente est suspendue, mais votre compte client reste actif.
```

**Redirection :** `/createur/suspended`

---

## 📝 ÉCRAN 2 — INSCRIPTION UNIFIÉE (`/register`)

### Structure de la page

**URL :** `/register`  
**Contrôleur :** `PublicAuthController@showRegisterForm`  
**Vue :** `auth.register-unified` (nouvelle vue)

### Contenu visible

#### 1. En-tête

```
┌─────────────────────────────────────┐
│  Créer votre compte                 │
│  Rejoignez l'univers RACINE BY GANDA│
└─────────────────────────────────────┘
```

**Message clé :**
```
💡 Un seul compte suffit. Vous pouvez acheter et vendre avec le même compte.
```

#### 2. Deux blocs clairs

##### 🔹 Bloc Client

**Titre :** "Créer un compte client"

**Contenu :**
- Formulaire d'inscription (nom, email, mot de passe, confirmation)
- Bouton OAuth Google
- Bouton OAuth Apple
- Bouton OAuth Facebook
- Checkbox "J'accepte les conditions d'utilisation"

**Bouton principal :**
```
┌─────────────────────────────────────┐
│  Créer mon compte client             │
└─────────────────────────────────────┘
```

##### 🔹 Bloc Créateur

**Titre :** "Créer un compte créateur"

**Contenu :**
- Formulaire d'inscription (nom, email, mot de passe, confirmation)
- Bouton OAuth Google
- Bouton OAuth Apple
- Bouton OAuth Facebook
- Checkbox "J'accepte les conditions d'utilisation"

**Bouton principal :**
```
┌─────────────────────────────────────┐
│  Créer mon compte créateur           │
└─────────────────────────────────────┘
```

**Message informatif :**
```
ℹ️ Votre compte créateur sera en attente de validation par l'équipe RACINE.
Vous pourrez toujours acheter pendant ce temps.
```

#### 3. Lien connexion

```
Vous avez déjà un compte ? Se connecter
```

**Lien vers :** `/login`

---

### Messages UX à afficher

#### ✅ Inscription client réussie

**Message flash :**
```
✅ Votre compte client a été créé avec succès.
Bienvenue sur RACINE BY GANDA !
```

**Redirection :** `/compte` (dashboard client)

#### ✅ Inscription créateur réussie

**Message flash :**
```
✅ Votre demande de compte créateur a été envoyée.
Votre compte est en cours de validation par l'équipe RACINE.
Vous recevrez un email une fois votre compte validé.
```

**Redirection :** `/createur/pending`

---

## 🧠 LOGIQUE MÉTIER DERRIÈRE

### Cas 1 — Nouvel utilisateur

| Action | Résultat |
|--------|----------|
| Inscription client | `User` créé + rôle `client` |
| Inscription créateur | `User` créé + rôle `creator` + `creator_profile` (pending) |

**Important :**
- ✅ Même logique backend pour les deux
- ✅ Seul le rôle change
- ✅ `users.id` reste immuable

---

### Cas 2 — Client existant → devient créateur

**UX :**
- Bouton "Devenir créateur" dans le compte client
- Formulaire d'onboarding créateur
- Création `creator_profile` avec `status = 'pending'`

**Backend :**
- Création `creator_profile`
- Mise à jour du rôle (actuel : `role_id` change)
- **Aucune modification de `users.id`**

**Message UX :**
```
✅ Votre compte créateur est en cours de validation.
Votre compte client reste actif. Vous pourrez toujours acheter.
```

---

### Cas 3 — Connexion OAuth (Google / Apple / Facebook)

**Scénario :**
1. Utilisateur clique sur "Continuer avec Google/Apple/Facebook"
2. OAuth identifie l'utilisateur
3. Le système retrouve `users.id` (ou crée un nouveau `User`)
4. Redirection selon le contexte :
   - Client → dashboard client
   - Créateur (pending) → `/createur/pending`
   - Créateur (active) → dashboard créateur

**Important :**
- ✅ Même logique OAuth pour client et créateur
- ✅ Le rôle est détecté automatiquement
- ✅ Pas de choix de rôle dans l'UX OAuth

---

## 🧩 STRUCTURE DES DASHBOARDS (UX)

### Compte utilisateur (toujours accessible)

**Route :** `/compte`

**Sections :**
- Profil
- Commandes
- Paiements
- Adresses
- Wishlist
- Fidélité

**Accès :** Tous les utilisateurs authentifiés (client, créateur, etc.)

---

### Espace créateur (conditionnel)

#### Onboarding (draft / pending)

**Route :** `/createur/pending`

**Contenu :**
- Message : "Votre compte créateur est en cours de validation"
- Formulaire d'onboarding (si draft)
- Informations sur le processus de validation

**Accès :** Créateurs avec `creator_profile.status = 'pending'` ou `'draft'`

#### Dashboard créateur (active)

**Route :** `/createur/dashboard`

**Sections :**
- Produits
- Commandes vendeurs
- Statistiques
- Paramètres créateur

**Accès :** Créateurs avec `creator_profile.status = 'active'`

---

## 🔄 LOGIQUE DE REDIRECTION APRÈS LOGIN

### Algorithme de redirection

**Fichier :** `app/Http/Controllers/Auth/Traits/HandlesAuthRedirect.php`

**Logique actuelle :**
```php
protected function getRedirectPath(User $user): string
{
    $roleSlug = $user->getRoleSlug() ?? 'client';

    return match($roleSlug) {
        'client' => route('account.dashboard'),
        'createur', 'creator' => route('creator.dashboard'),
        'staff' => route('staff.dashboard'),
        'admin', 'super_admin' => route('admin.dashboard'),
        default => route('frontend.home'),
    };
}
```

**Logique améliorée (à implémenter) :**
```php
protected function getRedirectPath(User $user): string
{
    $roleSlug = $user->getRoleSlug() ?? 'client';

    // Cas spécial : Créateur avec statut pending ou suspended
    if (in_array($roleSlug, ['createur', 'creator'])) {
        $creatorProfile = $user->creatorProfile;
        
        if (!$creatorProfile) {
            // Pas de profil créateur → rediriger vers onboarding
            return route('creator.onboarding');
        }
        
        if ($creatorProfile->status === 'pending') {
            // En attente de validation
            return route('creator.pending');
        }
        
        if ($creatorProfile->status === 'suspended') {
            // Suspendu
            return route('creator.suspended');
        }
        
        if ($creatorProfile->status === 'active') {
            // Actif → dashboard créateur
            return route('creator.dashboard');
        }
    }

    // Cas par défaut
    return match($roleSlug) {
        'client' => route('account.dashboard'),
        'staff' => route('staff.dashboard'),
        'admin', 'super_admin' => route('admin.dashboard'),
        default => route('frontend.home'),
    };
}
```

---

## 💬 MESSAGES UX À AFFICHER (TRÈS IMPORTANT)

### 🔐 Lors de la connexion

**Succès :**
```
✅ Vous êtes connecté avec succès.
```

**Erreur :**
```
❌ Les identifiants fournis ne correspondent pas à nos enregistrements.
```

---

### 🧵 Client devenu créateur

**Après création du profil créateur :**
```
✅ Votre compte créateur est en cours de validation.
Vous pouvez continuer à acheter pendant ce temps.
```

**Redirection :** `/createur/pending`

---

### 🛍️ Créateur actif

**Lors de la connexion :**
```
✅ Bienvenue dans votre espace créateur.
```

**Redirection :** `/createur/dashboard`

---

### 🛑 Créateur suspendu

**Lors de la connexion :**
```
⚠️ Votre activité de vente est suspendue, mais votre compte client reste actif.
```

**Redirection :** `/createur/suspended`

**Message rassurant :**
```
💡 Vous pouvez toujours :
- Acheter des produits
- Consulter vos commandes
- Gérer votre profil client
```

---

## 🧪 CE QU'ON NE FAIT PAS (IMPORTANT)

### ❌ Interdictions strictes

1. **Ne pas demander à l'utilisateur de créer deux comptes**
   - Un seul compte suffit
   - Le rôle est un attribut, pas un compte séparé

2. **Ne pas séparer client et créateur par email**
   - Même email pour client et créateur
   - Le système gère les rôles multiples (futur)

3. **Ne pas supprimer ou masquer l'historique**
   - Toutes les données restent accessibles
   - L'historique client est préservé lors du passage créateur

4. **Ne pas changer `users.id`**
   - `users.id` est immuable
   - Toutes les relations sont préservées

5. **Ne pas présenter le rôle comme une création de compte séparée**
   - Le rôle est un attribut métier
   - L'authentification identifie la personne, pas le rôle

---

## 📊 SCHÉMA SIMPLE DU PARCOURS UTILISATEUR

### Parcours 1 : Nouveau client

```
1. Visite /register
   ↓
2. Choisit "Créer un compte client"
   ↓
3. Remplit le formulaire OU clique sur OAuth
   ↓
4. Compte créé (User + rôle client)
   ↓
5. Redirection → /compte (dashboard client)
   ↓
6. Peut acheter, consulter commandes, etc.
```

---

### Parcours 2 : Nouveau créateur

```
1. Visite /register
   ↓
2. Choisit "Créer un compte créateur"
   ↓
3. Remplit le formulaire OU clique sur OAuth
   ↓
4. Compte créé (User + rôle creator + creator_profile pending)
   ↓
5. Redirection → /createur/pending
   ↓
6. Message : "En attente de validation"
   ↓
7. Admin valide → creator_profile.status = 'active'
   ↓
8. Prochaine connexion → /createur/dashboard
```

---

### Parcours 3 : Client → Créateur (upgrade)

```
1. Client connecté sur /compte
   ↓
2. Clique sur "Devenir créateur"
   ↓
3. Formulaire d'onboarding créateur
   ↓
4. Création creator_profile (status = 'pending')
   ↓
5. Mise à jour rôle (role_id change)
   ↓
6. Redirection → /createur/pending
   ↓
7. Message : "Votre compte client reste actif"
   ↓
8. Admin valide → creator_profile.status = 'active'
   ↓
9. Prochaine connexion → /createur/dashboard
   ↓
10. Peut toujours accéder à /compte (historique préservé)
```

---

### Parcours 4 : Connexion OAuth

```
1. Visite /login
   ↓
2. Clique sur "Continuer avec Google/Apple/Facebook"
   ↓
3. OAuth callback
   ↓
4. Système identifie ou crée User
   ↓
5. Redirection selon rôle et statut :
   - Client → /compte
   - Créateur (pending) → /createur/pending
   - Créateur (active) → /createur/dashboard
```

---

## 🎨 SPÉCIFICATIONS VISUELLES

### Couleurs

- **Primaire :** `#D4A574` (racine-orange)
- **Secondaire :** `#8B5A2B` (racine-brown)
- **Accent :** `#FF6B00` (racine-orange-bright)
- **Succès :** `#22c55e` (green-500)
- **Avertissement :** `#f59e0b` (amber-500)
- **Erreur :** `#ef4444` (red-500)

### Typographie

- **Titres :** 'Libre Baskerville', serif
- **Corps :** 'Outfit', sans-serif

### Icônes

- **OAuth Google :** `fab fa-google`
- **OAuth Apple :** `fab fa-apple`
- **OAuth Facebook :** `fab fa-facebook`
- **Info :** `fas fa-info-circle`
- **Succès :** `fas fa-check-circle`
- **Avertissement :** `fas fa-exclamation-triangle`

---

## 📝 CHECKLIST D'IMPLÉMENTATION

### Phase 1 : Vues

- [ ] Créer `resources/views/auth/login-unified.blade.php`
- [ ] Créer `resources/views/auth/register-unified.blade.php`
- [ ] Ajouter les boutons OAuth (Google, Apple, Facebook)
- [ ] Ajouter les messages UX rassurants
- [ ] Ajouter les badges informatifs

### Phase 2 : Contrôleurs

- [ ] Modifier `LoginController@showLoginForm` pour utiliser la nouvelle vue
- [ ] Modifier `PublicAuthController@showRegisterForm` pour utiliser la nouvelle vue
- [ ] Améliorer `HandlesAuthRedirect@getRedirectPath` pour gérer les statuts créateur

### Phase 3 : Messages flash

- [ ] Ajouter les messages de succès
- [ ] Ajouter les messages d'avertissement
- [ ] Ajouter les messages d'erreur

### Phase 4 : Tests

- [ ] Tester le parcours nouveau client
- [ ] Tester le parcours nouveau créateur
- [ ] Tester le parcours client → créateur
- [ ] Tester les connexions OAuth
- [ ] Vérifier les redirections selon les statuts

---

## 🎯 RÉSUMÉ

### Objectifs atteints

✅ **UX claire et unifiée** — Un seul compte, plusieurs moyens de connexion  
✅ **Messages rassurants** — L'utilisateur comprend qu'il ne perd rien  
✅ **Logique simplifiée** — Pas de choix de rôle dans l'UX OAuth  
✅ **Redirections intelligentes** — Selon le rôle et le statut créateur  
✅ **Respect de l'architecture** — Aucune modification de la base de données

### Prochaines étapes

1. Implémenter les nouvelles vues
2. Améliorer la logique de redirection
3. Ajouter les messages flash
4. Tester tous les parcours utilisateur

---

**Date :** 2025-12-19  
**Statut :** 📋 **SPÉCIFICATIONS UX COMPLÈTES — PRÊT POUR IMPLÉMENTATION**






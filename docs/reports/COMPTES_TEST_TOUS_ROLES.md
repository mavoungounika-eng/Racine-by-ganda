# 🔐 COMPTES DE TEST - TOUS LES RÔLES
## RACINE BY GANDA

**Date de création :** 2025  
**Dernière mise à jour :** 2025  
**Mot de passe par défaut pour tous les comptes :** `password`

⚠️ **IMPORTANT :** Si les comptes ne fonctionnent pas, exécutez `php artisan accounts:fix-test` pour les corriger automatiquement.

---

## 📋 LISTE DES COMPTES PAR RÔLE

### 1️⃣ SUPER ADMINISTRATEUR

| Email | Mot de passe | Rôle | Accès |
|-------|--------------|------|-------|
| `superadmin@racine.cm` | `password` | `super_admin` | Accès complet à toutes les fonctionnalités, gestion des administrateurs |

**URL de connexion :** `/admin/login` ou `/login?context=admin`

---

### 2️⃣ ADMINISTRATEUR

| Email | Mot de passe | Rôle | Accès |
|-------|--------------|------|-------|
| `admin@racine.cm` | `password` | `admin` | Accès administrateur standard, gestion utilisateurs et contenu |

**URL de connexion :** `/admin/login` ou `/login?context=admin`

---

### 3️⃣ STAFF (Personnel)

| Email | Mot de passe | Rôle | Staff Role | Accès |
|-------|--------------|------|------------|-------|
| `staff@racine.cm` | `password` | `staff` | - | Accès aux outils internes |
| `vendeur@racine.cm` | `password` | `staff` | `vendeur` | Gestion des ventes |
| `caissier@racine.cm` | `password` | `staff` | `caissier` | Gestion de la caisse |
| `stock@racine.cm` | `password` | `staff` | `gestionnaire_stock` | Gestion des stocks |
| `comptable@racine.cm` | `password` | `staff` | `comptable` | Gestion comptable |

**URL de connexion :** `/admin/login` ou `/login?context=admin`

---

### 4️⃣ CRÉATEUR

| Email | Mot de passe | Rôle | Statut | Accès |
|-------|--------------|------|--------|-------|
| `createur@racine.cm` | `password` | `createur` | `active` | Espace créateur complet |
| `createur.pending@racine.cm` | `password` | `createur` | `pending` | Compte en attente de validation |
| `createur.suspended@racine.cm` | `password` | `createur` | `suspended` | Compte suspendu |

**URL de connexion :** `/createur/login`

**Note :** Les créateurs avec statut `pending` ou `suspended` seront redirigés vers les pages correspondantes.

---

### 5️⃣ CLIENT

| Email | Mot de passe | Rôle | Accès |
|-------|--------------|------|-------|
| `client@racine.cm` | `password` | `client` | Accès boutique, commandes, profil |
| `client2@racine.cm` | `password` | `client` | Accès boutique, commandes, profil |
| `client3@racine.cm` | `password` | `client` | Accès boutique, commandes, profil |

**URL de connexion :** `/login` ou `/login?context=boutique`

---

## 🚀 CRÉATION DES COMPTES

### Option 1 : Commande Artisan de Correction (Recommandé)

Si les comptes ne fonctionnent pas, utilisez cette commande pour les corriger automatiquement :

```bash
php artisan accounts:fix-test
```

Cette commande va :
- ✅ Vérifier tous les comptes
- ✅ Corriger les mots de passe
- ✅ S'assurer que tous les champs sont corrects
- ✅ Créer les profils créateurs manquants
- ✅ Désactiver la 2FA

### Option 2 : Via Seeder

Le seeder supprime automatiquement tous les anciens comptes de test avant de créer les nouveaux.

Exécutez le seeder pour créer automatiquement tous les comptes :

```bash
php artisan db:seed --class=TestUsersSeeder
```

**Note :** Les anciens comptes de test seront automatiquement supprimés avant la création des nouveaux.

### Option 3 : Via Tinker (Correction manuelle)

Si les comptes ne fonctionnent toujours pas, utilisez Tinker pour les corriger manuellement :

```bash
php artisan tinker
```

Puis exécutez ce code pour corriger tous les comptes :

```php
use App\Models\User;
use App\Models\CreatorProfile;
use Illuminate\Support\Facades\Hash;

$accounts = [
    ['email' => 'superadmin@racine.cm', 'password' => 'password', 'role_id' => 1, 'role' => 'super_admin', 'is_admin' => true],
    ['email' => 'admin@racine.cm', 'password' => 'password', 'role_id' => 2, 'role' => 'admin', 'is_admin' => true],
    ['email' => 'staff@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'is_admin' => false],
    ['email' => 'vendeur@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'vendeur', 'is_admin' => false],
    ['email' => 'caissier@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'caissier', 'is_admin' => false],
    ['email' => 'stock@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'gestionnaire_stock', 'is_admin' => false],
    ['email' => 'comptable@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'comptable', 'is_admin' => false],
    ['email' => 'createur@racine.cm', 'password' => 'password', 'role_id' => 4, 'role' => 'createur', 'is_admin' => false, 'creator_status' => 'active'],
    ['email' => 'createur.pending@racine.cm', 'password' => 'password', 'role_id' => 4, 'role' => 'createur', 'is_admin' => false, 'creator_status' => 'pending'],
    ['email' => 'createur.suspended@racine.cm', 'password' => 'password', 'role_id' => 4, 'role' => 'createur', 'is_admin' => false, 'creator_status' => 'suspended'],
    ['email' => 'client@racine.cm', 'password' => 'password', 'role_id' => 5, 'role' => 'client', 'is_admin' => false],
    ['email' => 'client2@racine.cm', 'password' => 'password', 'role_id' => 5, 'role' => 'client', 'is_admin' => false],
    ['email' => 'client3@racine.cm', 'password' => 'password', 'role_id' => 5, 'role' => 'client', 'is_admin' => false],
];

foreach ($accounts as $data) {
    $user = User::where('email', $data['email'])->first();
    $creatorStatus = $data['creator_status'] ?? null;
    unset($data['creator_status']);
    
    if (!$user) {
        $user = User::create([
            'name' => ucwords(str_replace(['.', '_'], ' ', explode('@', $data['email'])[0])),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'role' => $data['role'],
            'is_admin' => $data['is_admin'] ?? false,
            'staff_role' => $data['staff_role'] ?? null,
            'status' => 'active',
            'email_verified_at' => now(),
            'two_factor_required' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
        echo "✅ {$data['email']} créé\n";
    } else {
        $user->password = Hash::make($data['password']);
        $user->role_id = $data['role_id'];
        $user->role = $data['role'];
        $user->is_admin = $data['is_admin'] ?? false;
        $user->staff_role = $data['staff_role'] ?? null;
        $user->status = 'active';
        $user->email_verified_at = now();
        $user->two_factor_required = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
        echo "✅ {$data['email']} corrigé\n";
    }
    
    if ($data['role'] === 'createur') {
        $status = $creatorStatus ?? 'active';
        $profile = CreatorProfile::where('user_id', $user->id)->first();
        if (!$profile) {
            CreatorProfile::create([
                'user_id' => $user->id,
                'brand_name' => 'Boutique Test ' . ucfirst($status),
                'slug' => 'boutique-test-' . $status,
                'bio' => 'Créateur de test',
                'status' => $status,
                'is_verified' => $status === 'active',
                'is_active' => $status === 'active',
            ]);
        } else {
            $profile->status = $status;
            $profile->is_verified = $status === 'active';
            $profile->is_active = $status === 'active';
            $profile->save();
        }
    }
}

echo "\n🎉 Tous les comptes ont été corrigés !\n";
```

### Option 4 : Suppression manuelle des anciens comptes

Si vous voulez supprimer manuellement les anciens comptes :

```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Models\CreatorProfile;

// Supprimer les profils créateurs
$testUsers = User::whereIn('email', [
    'superadmin@racine.cm',
    'admin@racine.cm',
    'staff@racine.cm',
    'vendeur@racine.cm',
    'caissier@racine.cm',
    'stock@racine.cm',
    'comptable@racine.cm',
    'createur@racine.cm',
    'createur.pending@racine.cm',
    'createur.suspended@racine.cm',
    'client@racine.cm',
    'client2@racine.cm',
    'client3@racine.cm',
])->get();

foreach ($testUsers as $user) {
    if ($user->creatorProfile) {
        $user->creatorProfile->delete();
    }
    $user->delete();
}
```

---

## 📝 DÉTAILS DES COMPTES

### Super Administrateur
- **Nom :** Super Admin RACINE
- **Email :** `superadmin@racine.cm`
- **Téléphone :** `+237 6XX XXX XXX`
- **Rôle ID :** 1
- **Rôle :** `super_admin`
- **is_admin :** `true`
- **Accès :** Toutes les fonctionnalités, y compris la gestion des autres administrateurs

### Administrateur
- **Nom :** Admin RACINE
- **Email :** `admin@racine.cm`
- **Téléphone :** `+237 6XX XXX XXX`
- **Rôle ID :** 2
- **Rôle :** `admin`
- **is_admin :** `true`
- **Accès :** Gestion standard (utilisateurs, produits, commandes, CMS)

### Staff Général
- **Nom :** Staff RACINE
- **Email :** `staff@racine.cm`
- **Téléphone :** `+237 6XX XXX XXX`
- **Rôle ID :** 3
- **Rôle :** `staff`
- **Staff Role :** `null`
- **Accès :** Outils internes de base

### Staff Vendeur
- **Nom :** Vendeur RACINE
- **Email :** `vendeur@racine.cm`
- **Rôle ID :** 3
- **Rôle :** `staff`
- **Staff Role :** `vendeur`
- **Accès :** Gestion des ventes et commandes

### Staff Caissier
- **Nom :** Caissier RACINE
- **Email :** `caissier@racine.cm`
- **Rôle ID :** 3
- **Rôle :** `staff`
- **Staff Role :** `caissier`
- **Accès :** Gestion de la caisse et paiements

### Staff Gestionnaire Stock
- **Nom :** Gestionnaire Stock RACINE
- **Email :** `stock@racine.cm`
- **Rôle ID :** 3
- **Rôle :** `staff`
- **Staff Role :** `gestionnaire_stock`
- **Accès :** Gestion des stocks et inventaire

### Staff Comptable
- **Nom :** Comptable RACINE
- **Email :** `comptable@racine.cm`
- **Rôle ID :** 3
- **Rôle :** `staff`
- **Staff Role :** `comptable`
- **Accès :** Gestion comptable et finances

### Créateur Actif
- **Nom :** Créateur Test
- **Email :** `createur@racine.cm`
- **Rôle ID :** 4
- **Rôle :** `createur`
- **Creator Profile :** `status = 'active'`
- **Accès :** Dashboard créateur, gestion produits, commandes, finances, stats

### Créateur En Attente
- **Nom :** Créateur Pending
- **Email :** `createur.pending@racine.cm`
- **Rôle ID :** 4
- **Rôle :** `createur`
- **Creator Profile :** `status = 'pending'`
- **Accès :** Redirigé vers `/createur/pending`

### Créateur Suspendu
- **Nom :** Créateur Suspended
- **Email :** `createur.suspended@racine.cm`
- **Rôle ID :** 4
- **Rôle :** `createur`
- **Creator Profile :** `status = 'suspended'`
- **Accès :** Redirigé vers `/createur/suspended`

### Clients
- **Nom :** Client Test 1, 2, 3
- **Email :** `client@racine.cm`, `client2@racine.cm`, `client3@racine.cm`
- **Rôle ID :** 5
- **Rôle :** `client`
- **Accès :** Boutique, panier, commandes, profil, wishlist

---

## 🔒 SÉCURITÉ

⚠️ **IMPORTANT :** Ces comptes sont destinés uniquement au développement et aux tests.

**En production :**
1. Changez tous les mots de passe
2. Désactivez ou supprimez ces comptes
3. Utilisez des mots de passe forts et uniques
4. Activez la 2FA pour les comptes administrateurs

---

## 📍 URLS DE CONNEXION

### Espace Admin/Staff
- `/admin/login`
- `/login?context=admin`

### Espace Créateur
- `/createur/login`
- `/createur/register` (pour créer un nouveau compte créateur)

### Espace Client
- `/login`
- `/login?context=boutique`
- `/register`
- `/register?context=boutique`

---

## ✅ VÉRIFICATION

Après création/correction des comptes, vérifiez :

1. **Super Admin :** `/admin/login` → Email: `superadmin@racine.cm` / Password: `password` → `/admin/dashboard`
2. **Admin :** `/admin/login` → Email: `admin@racine.cm` / Password: `password` → `/admin/dashboard`
3. **Staff :** `/admin/login` → Email: `staff@racine.cm` / Password: `password` → `/admin/dashboard`
4. **Créateur actif :** `/createur/login` → Email: `createur@racine.cm` / Password: `password` → `/createur/dashboard`
5. **Créateur pending :** `/createur/login` → Email: `createur.pending@racine.cm` / Password: `password` → Redirection vers `/createur/pending`
6. **Créateur suspended :** `/createur/login` → Email: `createur.suspended@racine.cm` / Password: `password` → Redirection vers `/createur/suspended`
7. **Client :** `/login` → Email: `client@racine.cm` / Password: `password` → Accès boutique normal

## 🔧 PROBLÈMES DE CONNEXION

Si les comptes ne fonctionnent pas, vérifiez :

### Checklist de diagnostic

1. **Le compte existe-t-il ?**
   ```bash
   php artisan tinker
   ```
   ```php
   \App\Models\User::where('email', 'superadmin@racine.cm')->exists();
   ```

2. **Le mot de passe est-il correct ?**
   ```php
   $user = \App\Models\User::where('email', 'superadmin@racine.cm')->first();
   \Illuminate\Support\Facades\Hash::check('password', $user->password);
   ```

3. **Le statut est-il actif ?**
   ```php
   $user->status === 'active';
   ```

4. **L'email est-il vérifié ?**
   ```php
   $user->email_verified_at !== null;
   ```

5. **La 2FA est-elle désactivée ?**
   ```php
   $user->two_factor_required === false;
   ```

### Solution rapide

Si un compte ne fonctionne pas, réinitialisez-le :

```bash
php artisan accounts:fix-test
```

Ou manuellement dans Tinker :

```php
$user = \App\Models\User::where('email', 'superadmin@racine.cm')->first();
$user->password = \Illuminate\Support\Facades\Hash::make('password');
$user->status = 'active';
$user->email_verified_at = now();
$user->two_factor_required = false;
$user->save();
```

---

## 🛠️ MAINTENANCE

### Réinitialiser tous les comptes de test

Le seeder supprime automatiquement les anciens comptes avant de créer les nouveaux :

```bash
php artisan db:seed --class=TestUsersSeeder
```

Ou avec force (si nécessaire) :

```bash
php artisan db:seed --class=TestUsersSeeder --force
```

### Supprimer tous les comptes de test manuellement

```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Models\CreatorProfile;

// Liste des emails de test
$testEmails = [
    'superadmin@racine.cm',
    'admin@racine.cm',
    'staff@racine.cm',
    'vendeur@racine.cm',
    'caissier@racine.cm',
    'stock@racine.cm',
    'comptable@racine.cm',
    'createur@racine.cm',
    'createur.pending@racine.cm',
    'createur.suspended@racine.cm',
    'client@racine.cm',
    'client2@racine.cm',
    'client3@racine.cm',
];

// Supprimer les profils créateurs associés
$users = User::whereIn('email', $testEmails)->get();
foreach ($users as $user) {
    if ($user->creatorProfile) {
        $user->creatorProfile->delete();
    }
}

// Supprimer les utilisateurs
User::whereIn('email', $testEmails)->delete();

echo "✅ Tous les comptes de test ont été supprimés\n";
```

### Vérifier les comptes existants

```bash
php artisan tinker
```

```php
use App\Models\User;

// Lister tous les comptes de test
$testEmails = [
    'superadmin@racine.cm',
    'admin@racine.cm',
    'staff@racine.cm',
    'vendeur@racine.cm',
    'caissier@racine.cm',
    'stock@racine.cm',
    'comptable@racine.cm',
    'createur@racine.cm',
    'createur.pending@racine.cm',
    'createur.suspended@racine.cm',
    'client@racine.cm',
    'client2@racine.cm',
    'client3@racine.cm',
];

$users = User::whereIn('email', $testEmails)->get(['id', 'name', 'email', 'role']);
foreach ($users as $user) {
    echo "{$user->email} - {$user->name} - {$user->role}\n";
}
```

---

**Dernière mise à jour :** 2025


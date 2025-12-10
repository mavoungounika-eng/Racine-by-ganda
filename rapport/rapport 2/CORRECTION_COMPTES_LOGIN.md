# 🔧 CORRECTION DES COMPTES DE TEST - PROBLÈME DE CONNEXION

## 🚨 Problème identifié

Les comptes créés ne permettent pas de se connecter. Voici comment les corriger.

---

## ✅ SOLUTION 1 : Commande Artisan (Recommandé)

Exécutez cette commande pour corriger automatiquement tous les comptes :

```bash
php artisan accounts:fix-test
```

Cette commande va :
- Vérifier tous les comptes
- Corriger les mots de passe
- S'assurer que tous les champs sont corrects
- Créer les profils créateurs manquants

---

## ✅ SOLUTION 2 : Via Tinker (Manuel)

Si la commande ne fonctionne pas, utilisez Tinker :

```bash
php artisan tinker
```

Puis exécutez ce code :

```php
use App\Models\User;
use App\Models\CreatorProfile;
use Illuminate\Support\Facades\Hash;

// Liste des comptes à corriger
$accounts = [
    ['email' => 'superadmin@racine.cm', 'password' => 'password', 'role_id' => 1, 'role' => 'super_admin', 'is_admin' => true],
    ['email' => 'admin@racine.cm', 'password' => 'password', 'role_id' => 2, 'role' => 'admin', 'is_admin' => true],
    ['email' => 'staff@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'is_admin' => false],
    ['email' => 'vendeur@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'vendeur', 'is_admin' => false],
    ['email' => 'caissier@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'caissier', 'is_admin' => false],
    ['email' => 'stock@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'gestionnaire_stock', 'is_admin' => false],
    ['email' => 'comptable@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'comptable', 'is_admin' => false],
    ['email' => 'createur@racine.cm', 'password' => 'password', 'role_id' => 4, 'role' => 'createur', 'is_admin' => false],
    ['email' => 'createur.pending@racine.cm', 'password' => 'password', 'role_id' => 4, 'role' => 'createur', 'is_admin' => false],
    ['email' => 'createur.suspended@racine.cm', 'password' => 'password', 'role_id' => 4, 'role' => 'createur', 'is_admin' => false],
    ['email' => 'client@racine.cm', 'password' => 'password', 'role_id' => 5, 'role' => 'client', 'is_admin' => false],
    ['email' => 'client2@racine.cm', 'password' => 'password', 'role_id' => 5, 'role' => 'client', 'is_admin' => false],
    ['email' => 'client3@racine.cm', 'password' => 'password', 'role_id' => 5, 'role' => 'client', 'is_admin' => false],
];

foreach ($accounts as $data) {
    $user = User::where('email', $data['email'])->first();
    
    if (!$user) {
        echo "Création de {$data['email']}...\n";
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
    } else {
        echo "Correction de {$data['email']}...\n";
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
    }
    
    // Profils créateurs
    if ($data['role'] === 'createur') {
        $status = 'active';
        if (str_contains($data['email'], 'pending')) $status = 'pending';
        if (str_contains($data['email'], 'suspended')) $status = 'suspended';
        
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
    
    echo "✅ {$data['email']} - OK\n";
}

echo "\n🎉 Tous les comptes ont été corrigés !\n";
```

---

## ✅ SOLUTION 3 : Ré-exécuter le Seeder

Ré-exécutez le seeder qui a été corrigé :

```bash
php artisan db:seed --class=TestUsersSeeder
```

Le seeder a été mis à jour pour :
- Créer les rôles en premier
- Supprimer les anciens comptes
- Créer tous les comptes avec les bons champs
- Désactiver la 2FA

---

## 🔍 VÉRIFICATION

Après correction, testez la connexion :

### Super Admin
- URL: `/admin/login`
- Email: `superadmin@racine.cm`
- Password: `password`

### Admin
- URL: `/admin/login`
- Email: `admin@racine.cm`
- Password: `password`

### Créateur
- URL: `/createur/login`
- Email: `createur@racine.cm`
- Password: `password`

### Client
- URL: `/login`
- Email: `client@racine.cm`
- Password: `password`

---

## 🐛 PROBLÈMES POSSIBLES

### 1. Mot de passe incorrect
**Solution :** Le mot de passe doit être hashé avec `Hash::make('password')`

### 2. Statut inactif
**Solution :** Vérifier que `status = 'active'`

### 3. 2FA activé
**Solution :** Vérifier que `two_factor_required = false`

### 4. Rôle manquant
**Solution :** Vérifier que `role_id` correspond à un rôle existant dans la table `roles`

### 5. Email non vérifié
**Solution :** Vérifier que `email_verified_at` n'est pas null

---

## 📝 CHECKLIST DE VÉRIFICATION

Pour chaque compte, vérifier :
- [ ] Le compte existe dans la table `users`
- [ ] Le mot de passe est hashé correctement
- [ ] `status = 'active'`
- [ ] `email_verified_at` n'est pas null
- [ ] `two_factor_required = false`
- [ ] `role_id` correspond à un rôle existant
- [ ] `role` correspond au slug du rôle
- [ ] Pour les créateurs : le profil `creator_profiles` existe

---

## 🚀 COMMANDES RAPIDES

### Vérifier un compte spécifique
```bash
php artisan tinker
```
```php
$user = \App\Models\User::where('email', 'superadmin@racine.cm')->first();
if ($user) {
    echo "Email: {$user->email}\n";
    echo "Rôle: {$user->role}\n";
    echo "Statut: {$user->status}\n";
    echo "2FA: " . ($user->two_factor_required ? 'Oui' : 'Non') . "\n";
    echo "Mot de passe hashé: " . (strlen($user->password) > 20 ? 'Oui' : 'Non') . "\n";
} else {
    echo "Compte non trouvé\n";
}
```

### Réinitialiser un mot de passe
```php
$user = \App\Models\User::where('email', 'superadmin@racine.cm')->first();
$user->password = \Illuminate\Support\Facades\Hash::make('password');
$user->save();
echo "Mot de passe réinitialisé\n";
```

---

**Dernière mise à jour :** 2025



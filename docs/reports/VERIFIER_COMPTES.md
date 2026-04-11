# ✅ VÉRIFICATION DES COMPTES DE TEST

## 🚀 Le seeder a été exécuté

Pour vérifier que tous les comptes ont été créés, exécutez :

```bash
php artisan tinker
```

Puis dans Tinker, exécutez :

```php
use App\Models\User;
use App\Models\CreatorProfile;

// Vérifier tous les comptes
$emails = [
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

echo "=== VÉRIFICATION DES COMPTES ===\n\n";

foreach ($emails as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $role = $user->role ?? $user->roleRelation?->slug ?? 'N/A';
        $status = $user->status ?? 'N/A';
        echo "✅ {$email} - {$user->name} - Rôle: {$role} - Statut: {$status}\n";
        
        // Vérifier le profil créateur si c'est un créateur
        if ($user->isCreator() && $user->creatorProfile) {
            echo "   └─ Profil créateur: {$user->creatorProfile->status}\n";
        }
    } else {
        echo "❌ {$email} - NON TROUVÉ\n";
    }
}

echo "\n=== RÉSUMÉ ===\n";
$total = User::whereIn('email', $emails)->count();
echo "Total de comptes trouvés: {$total}/" . count($emails) . "\n";
```

## 🔍 Vérification rapide

Pour une vérification rapide :

```php
// Compter les comptes par rôle
echo "Super Admin: " . User::where('email', 'superadmin@racine.cm')->count() . "\n";
echo "Admin: " . User::where('email', 'admin@racine.cm')->count() . "\n";
echo "Staff: " . User::whereIn('email', ['staff@racine.cm', 'vendeur@racine.cm', 'caissier@racine.cm', 'stock@racine.cm', 'comptable@racine.cm'])->count() . "\n";
echo "Créateurs: " . User::whereIn('email', ['createur@racine.cm', 'createur.pending@racine.cm', 'createur.suspended@racine.cm'])->count() . "\n";
echo "Clients: " . User::whereIn('email', ['client@racine.cm', 'client2@racine.cm', 'client3@racine.cm'])->count() . "\n";
```

## 🔄 Ré-exécuter le seeder

Si certains comptes manquent, ré-exécutez le seeder :

```bash
php artisan db:seed --class=TestUsersSeeder
```

## 🔐 Tester la connexion

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



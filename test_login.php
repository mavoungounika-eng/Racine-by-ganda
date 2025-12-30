<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== TEST DE CONNEXION DES COMPTES ===\n\n";

$testAccounts = [
    ['email' => 'superadmin@racine.cm', 'password' => 'password', 'role' => 'super_admin'],
    ['email' => 'admin@racine.cm', 'password' => 'password', 'role' => 'admin'],
    ['email' => 'staff@racine.cm', 'password' => 'password', 'role' => 'staff'],
    ['email' => 'createur@racine.cm', 'password' => 'password', 'role' => 'createur'],
    ['email' => 'client@racine.cm', 'password' => 'password', 'role' => 'client'],
];

foreach ($testAccounts as $account) {
    $user = User::where('email', $account['email'])->first();
    
    if (!$user) {
        echo "❌ {$account['email']} - COMPTE NON TROUVÉ\n";
        continue;
    }
    
    echo "✅ {$account['email']} - Compte trouvé\n";
    echo "   - Nom: {$user->name}\n";
    echo "   - Rôle ID: {$user->role_id}\n";
    echo "   - Rôle: " . ($user->role ?? 'N/A') . "\n";
    echo "   - Statut: " . ($user->status ?? 'N/A') . "\n";
    echo "   - Email vérifié: " . ($user->email_verified_at ? 'Oui' : 'Non') . "\n";
    echo "   - 2FA requis: " . ($user->two_factor_required ? 'Oui' : 'Non') . "\n";
    
    // Test du mot de passe
    if (Hash::check($account['password'], $user->password)) {
        echo "   - ✅ Mot de passe: CORRECT\n";
    } else {
        echo "   - ❌ Mot de passe: INCORRECT\n";
        // Réinitialiser le mot de passe
        $user->password = Hash::make($account['password']);
        $user->save();
        echo "   - 🔄 Mot de passe réinitialisé\n";
    }
    
    // Vérifier la relation role
    if ($user->roleRelation) {
        echo "   - Relation rôle: {$user->roleRelation->slug}\n";
    } else {
        echo "   - ⚠️  Relation rôle: MANQUANTE\n";
    }
    
    echo "\n";
}

echo "=== FIN DU TEST ===\n";



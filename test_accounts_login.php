<?php

/**
 * Script de test des comptes de test
 * 
 * Usage: php test_accounts_login.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "=== TEST DES COMPTES DE TEST ===\n\n";

$accounts = [
    ['email' => 'superadmin@racine.cm', 'password' => 'password', 'role' => 'super_admin'],
    ['email' => 'admin@racine.cm', 'password' => 'password', 'role' => 'admin'],
    ['email' => 'staff@racine.cm', 'password' => 'password', 'role' => 'staff'],
    ['email' => 'createur@racine.cm', 'password' => 'password', 'role' => 'createur'],
    ['email' => 'client@racine.cm', 'password' => 'password', 'role' => 'client'],
];

$results = [];

foreach ($accounts as $account) {
    $email = $account['email'];
    $password = $account['password'];
    $expectedRole = $account['role'];
    
    echo "Test: {$email}...\n";
    
    // Vérifier que le compte existe
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        echo "  ❌ Compte non trouvé\n";
        $results[$email] = ['exists' => false, 'login' => false];
        continue;
    }
    
    echo "  ✅ Compte existe\n";
    $results[$email]['exists'] = true;
    
    // Vérifier le mot de passe
    $passwordValid = Hash::check($password, $user->password);
    echo "  " . ($passwordValid ? "✅" : "❌") . " Mot de passe: " . ($passwordValid ? "Correct" : "Incorrect") . "\n";
    $results[$email]['password'] = $passwordValid;
    
    // Vérifier le rôle
    $roleValid = $user->role === $expectedRole;
    echo "  " . ($roleValid ? "✅" : "❌") . " Rôle: {$user->role} (attendu: {$expectedRole})\n";
    $results[$email]['role'] = $roleValid;
    
    // Vérifier le statut
    $statusValid = $user->status === 'active';
    echo "  " . ($statusValid ? "✅" : "❌") . " Statut: {$user->status} (attendu: active)\n";
    $results[$email]['status'] = $statusValid;
    
    // Vérifier email vérifié
    $emailVerified = $user->email_verified_at !== null;
    echo "  " . ($emailVerified ? "✅" : "❌") . " Email vérifié: " . ($emailVerified ? "Oui" : "Non") . "\n";
    $results[$email]['email_verified'] = $emailVerified;
    
    // Vérifier 2FA désactivée
    $twoFactorDisabled = !$user->two_factor_required;
    echo "  " . ($twoFactorDisabled ? "✅" : "❌") . " 2FA désactivée: " . ($twoFactorDisabled ? "Oui" : "Non") . "\n";
    $results[$email]['two_factor'] = $twoFactorDisabled;
    
    // Tester la connexion
    if ($passwordValid && $statusValid && $emailVerified && $twoFactorDisabled) {
        $credentials = ['email' => $email, 'password' => $password];
        $loginSuccess = Auth::attempt($credentials);
        echo "  " . ($loginSuccess ? "✅" : "❌") . " Connexion: " . ($loginSuccess ? "Réussie" : "Échouée") . "\n";
        $results[$email]['login'] = $loginSuccess;
        
        if ($loginSuccess) {
            Auth::logout();
        }
    } else {
        echo "  ⚠️  Connexion non testée (problèmes détectés)\n";
        $results[$email]['login'] = false;
    }
    
    echo "\n";
}

// Résumé
echo "=== RÉSUMÉ ===\n\n";

$total = count($accounts);
$success = 0;

foreach ($results as $email => $result) {
    if ($result['exists'] && $result['password'] && $result['status'] && $result['email_verified'] && $result['two_factor'] && $result['login']) {
        $success++;
        echo "✅ {$email} - OK\n";
    } else {
        echo "❌ {$email} - PROBLÈME\n";
        if (!$result['exists']) echo "   - Compte non trouvé\n";
        if (!$result['password']) echo "   - Mot de passe incorrect\n";
        if (!$result['status']) echo "   - Statut incorrect\n";
        if (!$result['email_verified']) echo "   - Email non vérifié\n";
        if (!$result['two_factor']) echo "   - 2FA activée\n";
        if (!$result['login']) echo "   - Connexion échouée\n";
    }
}

echo "\n";
echo "Total: {$total} comptes\n";
echo "Réussis: {$success} comptes\n";
echo "Échoués: " . ($total - $success) . " comptes\n";

if ($success === $total) {
    echo "\n🎉 Tous les comptes fonctionnent correctement !\n";
    exit(0);
} else {
    echo "\n⚠️  Certains comptes ont des problèmes. Exécutez 'php artisan accounts:fix-test' pour les corriger.\n";
    exit(1);
}



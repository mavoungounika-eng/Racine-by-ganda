<?php

/**
 * Script rapide pour créer un utilisateur admin
 * Usage: php create-admin.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "🔐 Création d'un utilisateur administrateur...\n\n";

// Vérifier si un admin existe déjà
$existingAdmin = User::where('is_admin', true)
    ->orWhere('role_id', 1)
    ->first();

if ($existingAdmin) {
    echo "✅ Un administrateur existe déjà :\n";
    echo "   Email: {$existingAdmin->email}\n";
    echo "   Nom: {$existingAdmin->name}\n\n";
    echo "Voulez-vous créer un autre admin ? (o/n) : ";
    $response = trim(fgets(STDIN));
    if (strtolower($response) !== 'o') {
        echo "Annulé.\n";
        exit(0);
    }
}

// Demander les informations
echo "Entrez les informations de l'administrateur :\n";
echo "Nom : ";
$name = trim(fgets(STDIN));

echo "Email : ";
$email = trim(fgets(STDIN));

echo "Mot de passe : ";
$password = trim(fgets(STDIN));

if (empty($name) || empty($email) || empty($password)) {
    echo "❌ Tous les champs sont requis !\n";
    exit(1);
}

// Vérifier si l'email existe déjà
if (User::where('email', $email)->exists()) {
    echo "❌ Cet email est déjà utilisé !\n";
    exit(1);
}

// Créer l'utilisateur
try {
    // Récupérer ou créer le rôle admin
    $adminRole = Role::where('slug', 'admin')
        ->orWhere('slug', 'super_admin')
        ->orWhere('id', 1)
        ->first();

    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => bcrypt($password),
        'is_admin' => true,
        'role_id' => $adminRole ? $adminRole->id : 1,
        'status' => 'active',
    ]);

    echo "\n✅ Administrateur créé avec succès !\n\n";
    echo "📋 Informations de connexion :\n";
    echo "   URL: http://localhost:8000/admin/login\n";
    echo "   Email: {$email}\n";
    echo "   Mot de passe: {$password}\n\n";
    echo "🔗 Accédez au panel : http://localhost:8000/admin/login\n";

} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}


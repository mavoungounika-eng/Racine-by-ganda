<?php

/**
 * Script pour créer/mettre à jour le compte développeur
 * Usage: php update-dev-account.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

echo "🔧 Mise à jour du compte développeur passe-partout...\n\n";

$email = 'dev@racine.com';
$password = 'dev123';
$name = 'Developer';

// Récupérer le rôle admin
$adminRole = Role::where('slug', 'admin')
    ->orWhere('slug', 'super_admin')
    ->orWhere('id', 1)
    ->first();

// Créer ou mettre à jour le compte
$user = User::updateOrCreate(
    ['email' => $email],
    [
        'name' => $name,
        'password' => Hash::make($password),
        'is_admin' => true,
        'role_id' => $adminRole ? $adminRole->id : 1,
        'status' => 'active',
        'email_verified_at' => now(),
    ]
);

echo "✅ Compte développeur créé/mis à jour avec succès !\n\n";
echo "📋 Informations de connexion :\n";
echo "   URL: http://localhost:8000/admin/login\n";
echo "   Email: {$email}\n";
echo "   Password: {$password}\n";
echo "   Nom: {$name}\n\n";
echo "🔑 Accès : Super Administrateur (tous les droits)\n\n";
echo "🔗 Accédez maintenant à : http://localhost:8000/admin/login\n";


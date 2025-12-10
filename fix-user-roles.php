<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "🔍 Vérification et correction des rôles des utilisateurs...\n\n";

// S'assurer que les rôles existent
$roles = Role::all();
if ($roles->isEmpty()) {
    echo "⚠️  Aucun rôle trouvé. Exécution du seeder...\n";
    \Artisan::call('db:seed', ['--class' => 'Database\Seeders\RolesTableSeeder']);
    echo "✅ Rôles créés.\n\n";
}

$users = User::all();
$fixed = 0;

foreach ($users as $user) {
    // Si l'utilisateur n'a pas de role_id
    if (!$user->role_id) {
        // Déterminer le rôle selon l'email ou is_admin
        if ($user->is_admin) {
            // Si admin, assigner super_admin (role_id = 1)
            $user->role_id = 1;
            $user->save();
            echo "✅ {$user->name} ({$user->email}) → Super Administrateur\n";
            $fixed++;
        } elseif (str_contains($user->email, 'createur') || str_contains($user->email, 'creator')) {
            // Si créateur
            $user->role_id = 4; // Créateur
            $user->save();
            echo "✅ {$user->name} ({$user->email}) → Créateur\n";
            $fixed++;
        } elseif (str_contains($user->email, 'client') || str_contains($user->email, 'test')) {
            // Si client
            $user->role_id = 5; // Client
            $user->save();
            echo "✅ {$user->name} ({$user->email}) → Client\n";
            $fixed++;
        } else {
            // Par défaut, assigner Client
            $user->role_id = 5; // Client
            $user->save();
            echo "✅ {$user->name} ({$user->email}) → Client (par défaut)\n";
            $fixed++;
        }
    } else {
        // Vérifier que le rôle existe
        $role = Role::find($user->role_id);
        if (!$role) {
            echo "⚠️  {$user->name} a un role_id invalide ({$user->role_id}), assignation Client par défaut\n";
            $user->role_id = 5; // Client par défaut
            $user->save();
            $fixed++;
        }
    }
}

if ($fixed > 0) {
    echo "\n✅ {$fixed} utilisateur(s) corrigé(s) !\n\n";
} else {
    echo "\n✅ Tous les utilisateurs ont déjà un rôle assigné.\n\n";
}

// Afficher le résumé
echo "📊 Résumé des rôles :\n";
$roles = Role::all();
foreach ($roles as $role) {
    $count = User::where('role_id', $role->id)->count();
    echo "  - {$role->name}: {$count} utilisateur(s)\n";
}

echo "\n✅ Terminé !\n";


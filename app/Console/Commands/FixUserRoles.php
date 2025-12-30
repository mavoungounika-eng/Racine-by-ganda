<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;

class FixUserRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fix-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige les rôles manquants pour les utilisateurs existants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Vérification des rôles des utilisateurs...');
        
        // S'assurer que les rôles existent
        $this->call('db:seed', ['--class' => 'Database\Seeders\RolesTableSeeder']);
        
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
                    $this->info("✅ {$user->name} ({$user->email}) → Super Administrateur");
                    $fixed++;
                } elseif (str_contains($user->email, 'createur') || str_contains($user->email, 'creator')) {
                    // Si créateur
                    $user->role_id = 4; // Créateur
                    $user->save();
                    $this->info("✅ {$user->name} ({$user->email}) → Créateur");
                    $fixed++;
                } elseif (str_contains($user->email, 'client') || str_contains($user->email, 'test')) {
                    // Si client
                    $user->role_id = 5; // Client
                    $user->save();
                    $this->info("✅ {$user->name} ({$user->email}) → Client");
                    $fixed++;
                } else {
                    // Par défaut, assigner Client
                    $user->role_id = 5; // Client
                    $user->save();
                    $this->info("✅ {$user->name} ({$user->email}) → Client (par défaut)");
                    $fixed++;
                }
            } else {
                // Vérifier que le rôle existe
                $role = Role::find($user->role_id);
                if (!$role) {
                    $this->warn("⚠️  {$user->name} a un role_id invalide ({$user->role_id}), assignation Client par défaut");
                    $user->role_id = 5; // Client par défaut
                    $user->save();
                    $fixed++;
                }
            }
        }
        
        if ($fixed > 0) {
            $this->info("\n✅ {$fixed} utilisateur(s) corrigé(s) !");
        } else {
            $this->info("\n✅ Tous les utilisateurs ont déjà un rôle assigné.");
        }
        
        // Afficher le résumé
        $this->info("\n📊 Résumé des rôles :");
        $roles = Role::all();
        foreach ($roles as $role) {
            $count = User::where('role_id', $role->id)->count();
            $this->line("  - {$role->name}: {$count} utilisateur(s)");
        }
        
        return Command::SUCCESS;
    }
}


<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UpdateDevAccount extends Command
{
    protected $signature = 'dev:account {--email=dev@racine.com} {--password=dev123} {--name=Developer}';

    protected $description = 'Créer ou mettre à jour le compte développeur passe-partout';

    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        $this->info("🔧 Mise à jour du compte développeur...");
        $this->line("");

        // Chercher un compte existant
        $user = User::where('email', $email)->first();

        // Récupérer le rôle admin
        $adminRole = Role::where('slug', 'admin')
            ->orWhere('slug', 'super_admin')
            ->orWhere('id', 1)
            ->first();

        if ($user) {
            // Mettre à jour le compte existant
            $user->update([
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => true,
                'role_id' => $adminRole ? $adminRole->id : 1,
                'status' => 'active',
            ]);

            $this->info("✅ Compte développeur mis à jour avec succès !");
        } else {
            // Créer un nouveau compte
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
                'role_id' => $adminRole ? $adminRole->id : 1,
                'status' => 'active',
            ]);

            $this->info("✅ Compte développeur créé avec succès !");
        }

        $this->line("");
        $this->line("📋 Informations de connexion :");
        $this->line("   URL: http://localhost:8000/admin/login");
        $this->line("   Email: {$email}");
        $this->line("   Password: {$password}");
        $this->line("");
        $this->line("🔑 Accès complets :");
        $this->line("   ✅ Panel Admin");
        $this->line("   ✅ Tous les modules");
        $this->line("   ✅ Gestion complète");
        $this->line("");
        $this->info("🔗 Accédez au panel : http://localhost:8000/admin/login");

        return Command::SUCCESS;
    }
}


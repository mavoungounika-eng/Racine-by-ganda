<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {--email=admin@racine.com} {--password=} {--name=Administrateur}';

    protected $description = 'Créer un utilisateur administrateur rapidement';

    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password') ?: $this->secret('Password pour le compte admin');
        $name = $this->option('name');

        // Vérifier si l'utilisateur existe déjà
        if (User::where('email', $email)->exists()) {
            $this->error("❌ Un utilisateur avec l'email {$email} existe déjà !");
            return Command::FAILURE;
        }

        // Récupérer ou créer le rôle admin
        $adminRole = Role::where('slug', 'admin')
            ->orWhere('slug', 'super_admin')
            ->orWhere('id', 1)
            ->first();

        // Créer l'utilisateur
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
            'role_id' => $adminRole ? $adminRole->id : 1,
            'status' => 'active',
        ]);

        $this->info("✅ Administrateur créé avec succès !");
        $this->line("");
        $this->line("📋 Informations de connexion :");
        $this->line("   URL: http://localhost:8000/admin/login");
        $this->line("   Email: {$email}");
        $this->line("   Password: {$password}");
        $this->line("");
        $this->info("🔗 Accédez au panel : http://localhost:8000/admin/login");

        return Command::SUCCESS;
    }
}


<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\CreatorProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateCreatorAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'creator:create 
                            {--email=createur@test.com : Email du compte créateur}
                            {--password=password123 : Mot de passe}
                            {--name=Createur Test : Nom du créateur}
                            {--brand=Atelier Mode Test : Nom de la marque}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un compte créateur fonctionnel avec statut actif';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');
        $brand = $this->option('brand');

        // Vérifier si l'utilisateur existe déjà
        if (User::where('email', $email)->exists()) {
            $this->error("Un utilisateur avec l'email {$email} existe déjà.");
            return 1;
        }

        // Créer l'utilisateur
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'createur',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        // Créer le profil créateur avec statut actif
        $profile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => $brand,
            'bio' => 'Compte créateur de test pour RACINE BY GANDA',
            'status' => 'active',
            'is_active' => true,
            'is_verified' => true,
        ]);

        $this->info("✅ Compte créateur créé avec succès !");
        $this->line("");
        $this->line("📧 Email: {$email}");
        $this->line("🔑 Mot de passe: {$password}");
        $this->line("🏷️  Marque: {$brand}");
        $this->line("✅ Statut: ACTIF");
        $this->line("");
        $this->line("🔗 Vous pouvez maintenant vous connecter à : /createur/login");
        $this->line("📊 Dashboard : /createur/dashboard");

        return 0;
    }
}

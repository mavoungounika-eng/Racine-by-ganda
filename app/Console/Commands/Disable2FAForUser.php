<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class Disable2FAForUser extends Command
{
    protected $signature = '2fa:disable {email}';

    protected $description = 'Désactiver la 2FA pour un utilisateur (développement uniquement)';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("❌ Utilisateur non trouvé : {$email}");
            return Command::FAILURE;
        }

        // Désactiver la 2FA
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_required = false;
        $user->trusted_device_token = null;
        $user->trusted_device_expires_at = null;
        $user->save();

        $this->info("✅ 2FA désactivée pour : {$user->email}");
        $this->line("   Nom : {$user->name}");
        $this->line("");
        $this->warn("⚠️  Cette action est pour le développement uniquement !");

        return Command::SUCCESS;
    }
}


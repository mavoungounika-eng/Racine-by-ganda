<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\CreatorProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class FixTestAccounts extends Command
{
    protected $signature = 'accounts:fix-test';
    protected $description = 'Corriger et vérifier tous les comptes de test';

    public function handle()
    {
        $this->info('=== CORRECTION DES COMPTES DE TEST ===');
        $this->newLine();

        $accounts = [
            ['email' => 'superadmin@racine.cm', 'password' => 'password', 'role_id' => 1, 'role' => 'super_admin', 'is_admin' => true],
            ['email' => 'admin@racine.cm', 'password' => 'password', 'role_id' => 2, 'role' => 'admin', 'is_admin' => true],
            ['email' => 'staff@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'is_admin' => false],
            ['email' => 'vendeur@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'vendeur', 'is_admin' => false],
            ['email' => 'caissier@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'caissier', 'is_admin' => false],
            ['email' => 'stock@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'gestionnaire_stock', 'is_admin' => false],
            ['email' => 'comptable@racine.cm', 'password' => 'password', 'role_id' => 3, 'role' => 'staff', 'staff_role' => 'comptable', 'is_admin' => false],
            ['email' => 'createur@racine.cm', 'password' => 'password', 'role_id' => 4, 'role' => 'createur', 'is_admin' => false, 'creator_status' => 'active'],
            ['email' => 'createur.pending@racine.cm', 'password' => 'password', 'role_id' => 4, 'role' => 'createur', 'is_admin' => false, 'creator_status' => 'pending'],
            ['email' => 'createur.suspended@racine.cm', 'password' => 'password', 'role_id' => 4, 'role' => 'createur', 'is_admin' => false, 'creator_status' => 'suspended'],
            ['email' => 'client@racine.cm', 'password' => 'password', 'role_id' => 5, 'role' => 'client', 'is_admin' => false],
            ['email' => 'client2@racine.cm', 'password' => 'password', 'role_id' => 5, 'role' => 'client', 'is_admin' => false],
            ['email' => 'client3@racine.cm', 'password' => 'password', 'role_id' => 5, 'role' => 'client', 'is_admin' => false],
        ];

        foreach ($accounts as $accountData) {
            $email = $accountData['email'];
            $creatorStatus = $accountData['creator_status'] ?? null;
            unset($accountData['creator_status']);

            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->error("❌ {$email} - Compte non trouvé, création...");
                $user = User::create([
                    'name' => $this->getNameFromEmail($email),
                    'email' => $email,
                    'password' => Hash::make($accountData['password']),
                    'role_id' => $accountData['role_id'],
                    'role' => $accountData['role'],
                    'is_admin' => $accountData['is_admin'] ?? false,
                    'staff_role' => $accountData['staff_role'] ?? null,
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'two_factor_required' => false,
                ]);
                $this->info("✅ {$email} - Compte créé");
            } else {
                // Corriger le compte existant
                $user->password = Hash::make($accountData['password']);
                $user->role_id = $accountData['role_id'];
                $user->role = $accountData['role'];
                $user->is_admin = $accountData['is_admin'] ?? false;
                $user->staff_role = $accountData['staff_role'] ?? null;
                $user->status = 'active';
                $user->email_verified_at = now();
                $user->two_factor_required = false;
                $user->two_factor_secret = null;
                $user->two_factor_recovery_codes = null;
                $user->two_factor_confirmed_at = null;
                $user->save();
                $this->info("✅ {$email} - Compte corrigé");
            }

            // Gérer le profil créateur si nécessaire
            if ($accountData['role'] === 'createur') {
                $profile = CreatorProfile::where('user_id', $user->id)->first();
                if (!$profile) {
                    CreatorProfile::create([
                        'user_id' => $user->id,
                        'brand_name' => 'Boutique Test ' . ucfirst($creatorStatus ?? 'Active'),
                        'slug' => 'boutique-test-' . ($creatorStatus ?? 'active'),
                        'bio' => 'Créateur de test',
                        'status' => $creatorStatus ?? 'active',
                        'is_verified' => $creatorStatus === 'active',
                        'is_active' => $creatorStatus === 'active',
                    ]);
                    $this->info("   └─ Profil créateur créé (status: {$creatorStatus})");
                } else {
                    $profile->status = $creatorStatus ?? 'active';
                    $profile->is_verified = $creatorStatus === 'active';
                    $profile->is_active = $creatorStatus === 'active';
                    $profile->save();
                    $this->info("   └─ Profil créateur mis à jour (status: {$creatorStatus})");
                }
            }

            // Vérifier le mot de passe
            if (Hash::check($accountData['password'], $user->password)) {
                $this->line("   └─ Mot de passe: ✅ Correct");
            } else {
                $this->error("   └─ Mot de passe: ❌ Incorrect (déjà corrigé)");
            }
        }

        $this->newLine();
        $this->info('🎉 Tous les comptes ont été corrigés !');
        $this->info('📝 Mot de passe pour tous: password');
    }

    private function getNameFromEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = str_replace(['.', '_'], ' ', $parts[0]);
        return ucwords($name);
    }
}



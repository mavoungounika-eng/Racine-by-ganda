<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\CreatorProfile;
use App\Models\Role;
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

        // S'assurer que les rôles existent avant de créer les comptes
        $this->ensureRolesExist();

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

            // Rechercher le compte même s'il est soft-deleted
            $user = User::withTrashed()->where('email', $email)->first();

            if (!$user) {
                $this->error("❌ {$email} - Compte non trouvé, création...");
                $role = Role::find($accountData['role_id']);
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
                // Associer la relation roleRelation
                if ($role) {
                    $user->roleRelation()->associate($role);
                    $user->save();
                }
                $this->info("✅ {$email} - Compte créé");
            } else {
                // Restaurer le compte s'il est soft-deleted
                if ($user->trashed()) {
                    $user->restore();
                    $this->line("   └─ Compte restauré (était supprimé)");
                }
                
                // Récupérer le rôle pour l'association
                $role = Role::find($accountData['role_id']);
                
                // Corriger le compte existant
                $user->password = Hash::make($accountData['password']);
                $user->role_id = $accountData['role_id'];
                $user->role = $accountData['role'];
                $user->is_admin = $accountData['is_admin'] ?? false;
                $user->staff_role = $accountData['staff_role'] ?? null;
                $user->status = 'active'; // Force la réactivation
                $user->email_verified_at = now();
                $user->two_factor_required = false;
                $user->two_factor_secret = null;
                $user->two_factor_recovery_codes = null;
                $user->two_factor_confirmed_at = null;
                
                // Associer la relation roleRelation
                if ($role) {
                    $user->roleRelation()->associate($role);
                }
                
                $user->save();
                $this->info("✅ {$email} - Compte corrigé et réactivé");
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

    private function ensureRolesExist(): void
    {
        $this->info('Vérification des rôles...');

        $roles = [
            [
                'id' => 1,
                'name' => 'Super Administrateur',
                'slug' => 'super_admin',
                'description' => 'Accès complet à toutes les fonctionnalités du système. Peut gérer les autres administrateurs.',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'Accès administrateur standard. Peut gérer les utilisateurs et le contenu.',
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'Staff',
                'slug' => 'staff',
                'description' => 'Membre de l\'équipe avec accès aux outils internes.',
                'is_active' => true,
            ],
            [
                'id' => 4,
                'name' => 'Créateur',
                'slug' => 'createur',
                'description' => 'Créateur/Designer partenaire. Peut gérer ses produits et sa boutique.',
                'is_active' => true,
            ],
            [
                'id' => 5,
                'name' => 'Client',
                'slug' => 'client',
                'description' => 'Client standard avec accès aux commandes et au profil.',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            $existingRole = Role::find($role['id']);
            if (!$existingRole) {
                Role::create($role);
                $this->line("   ✅ Rôle créé: {$role['name']} ({$role['slug']})");
            }
        }

        $this->newLine();
    }
}



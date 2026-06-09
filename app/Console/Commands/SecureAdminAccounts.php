<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SecureAdminAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:secure
                            {--dry-run : Display what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secure admin accounts by generating strong passwords and enabling 2FA requirement';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔒 Admin Account Security Tool');
        $this->newLine();

        // Find all admin users
        $adminUsers = User::whereHas('role', function ($query) {
            $query->whereIn('slug', ['admin', 'super_admin', 'staff']);
        })->get();

        if ($adminUsers->isEmpty()) {
            $this->warn('⚠️  No admin accounts found.');
            return self::SUCCESS;
        }

        $this->info("Found {$adminUsers->count()} admin account(s):");
        foreach ($adminUsers as $user) {
            $this->line("  - {$user->email} (Role: {$user->getRoleSlug()})");
        }
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
            foreach ($adminUsers as $user) {
                $this->line("Would update: {$user->email}");
                $this->line('  → Generate new 32-character password');
                $this->line('  → Enable two_factor_required = true');
            }
            $this->newLine();
            $this->info('Run without --dry-run to apply changes.');
            return self::SUCCESS;
        }

        // Confirm before proceeding
        if (!$this->confirm('⚠️  This will reset passwords for all admin accounts. Continue?', false)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('🔐 Securing admin accounts...');
        $this->newLine();

        $passwords = [];

        foreach ($adminUsers as $user) {
            // Generate strong random password (32 characters)
            $newPassword = Str::random(32);

            // Update password and enable 2FA requirement
            $user->password = Hash::make($newPassword);
            $user->two_factor_required = true;
            $user->save();

            // Store password for display (NOT logged)
            $passwords[$user->email] = $newPassword;

            $this->info("✅ {$user->email}");
            $this->line("   Password: {$newPassword}");
            $this->line('   2FA: Required');
            $this->newLine();
        }

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🎉 Admin accounts secured successfully!');
        $this->newLine();
        $this->warn('⚠️  IMPORTANT - Save these passwords NOW (they will NOT be shown again):');
        $this->newLine();

        foreach ($passwords as $email => $password) {
            $this->line("  {$email}");
            $this->line("  Password: {$password}");
            $this->newLine();
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Next steps:');
        $this->line('  1. Save passwords in a secure password manager');
        $this->line('  2. Share passwords securely with admin users');
        $this->line('  3. Admins must set up 2FA on next login');
        $this->newLine();

        // Log action (but NOT passwords)
        logger()->info('Admin accounts secured', [
            'admin_count' => $adminUsers->count(),
            'secured_at' => now()->toDateTimeString(),
            'accounts' => $adminUsers->pluck('email')->toArray(),
        ]);

        return self::SUCCESS;
    }
}

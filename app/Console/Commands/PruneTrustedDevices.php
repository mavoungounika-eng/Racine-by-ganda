<?php

namespace App\Console\Commands;

use App\Models\TrustedDevice;
use Illuminate\Console\Command;

class PruneTrustedDevices extends Command
{
    protected $signature = 'trusted-devices:prune';
    protected $description = 'Supprimer les appareils de confiance expirés';

    public function handle(): int
    {
        $deleted = TrustedDevice::where('expires_at', '<', now())->delete();
        $this->info("✅ {$deleted} appareil(s) expiré(s) supprimé(s).");
        return Command::SUCCESS;
    }
}

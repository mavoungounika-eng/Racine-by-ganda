<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

/**
 * VerifyLogIntegrity - Audit de l'audit
 * 
 * Scanne la table audit_logs et vérifie :
 * 1. Que le hash de chaque ligne est correct
 * 2. Que la chaîne n'est pas brisée (chaque ligne pointe vers le bon hash précédent)
 * 
 * Permet de détecter toute modification manuelle en base de données.
 */
class VerifyLogIntegrity extends Command
{
    protected $signature = 'audit:verify {--limit=1000 : Nombre de logs à vérifier}';
    protected $description = 'Vérifier l\'intégrité cryptographique de la chaîne des logs d\'audit';

    public function handle()
    {
        $this->info('Starting audit log integrity verification...');
        
        $limit = $this->option('limit');
        $logs = AuditLog::oldest()->limit($limit)->get();
        
        if ($logs->isEmpty()) {
            $this->warn('No audit logs found.');
            return 0;
        }

        $errorCount = 0;
        $previousHash = '0000000000000000000000000000000000000000000000000000000000000000';

        foreach ($logs as $log) {
            $isValid = $this->checkIntegrity($log, $previousHash);

            if (!$isValid) {
                $this->error("INTEGRITY BREAK detected at Log ID: {$log->id}");
                $this->line(" Expected hash to be valid chain from previous entry.");
                $errorCount++;
            }

            $previousHash = $log->integrity_hash;
        }

        if ($errorCount === 0) {
            $this->info("Verification successful! Checked {$logs->count()} logs. No tampering detected.");
        } else {
            $this->error("Verification failed! Found {$errorCount} integrity issues.");
            return 1;
        }

        return 0;
    }

    /**
     * Recalculer le hash et vérifier la continuité
     */
    protected function checkIntegrity(AuditLog $log, string $prevHash): bool
    {
        $payload = [
            'prev_hash' => $prevHash,
            'action' => $log->action,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'user_id' => $log->user_id,
            'metadata' => json_encode($log->metadata),
            'timestamp' => $log->created_at->timestamp,
        ];

        $calculatedHash = hash('sha256', json_encode($payload));

        return hash_equals($log->integrity_hash, $calculatedHash);
    }
}

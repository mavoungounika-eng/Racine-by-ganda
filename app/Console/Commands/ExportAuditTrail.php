<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;

/**
 * ExportAuditTrail - Conformité & Export
 * 
 * Exporte les logs d'audit au format CSV pour revue externe (commissaires aux comptes, conformité).
 */
class ExportAuditTrail extends Command
{
    protected $signature = 'audit:export {--days=30 : Nombre de jours à exporter} {--path=exports/audit_trail.csv : Chemin relatif de sortie}';
    protected $description = 'Exporter les logs d\'audit au format CSV';

    public function handle()
    {
        $days = (int) $this->option('days');
        $path = $this->option('path');

        $this->info("Exporting audit logs for the last {$days} days to {$path}...");

        $logs = AuditLog::with('user')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('id', 'asc')
            ->get();

        if ($logs->isEmpty()) {
            $this->warn('No logs found for this period.');
            return 0;
        }

        $headers = [
            'ID', 'Timestamp', 'Action', 'Entity Type', 'Entity ID', 
            'User', 'IP Address', 'Request ID', 'Metadata', 'Integrity Hash'
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->created_at->toIso8601String(),
                $log->action,
                $log->entity_type,
                $log->entity_id,
                $log->user ? $log->user->email : 'System',
                $log->ip_address,
                $log->request_id,
                json_encode($log->metadata),
                $log->integrity_hash,
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('local')->put($path, $content);

        $this->info("Export successful! File available at: " . Storage::disk('local')->path($path));

        return 0;
    }
}

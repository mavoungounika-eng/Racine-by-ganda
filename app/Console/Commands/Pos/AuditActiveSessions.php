<?php

namespace App\Console\Commands\Pos;

use App\Models\PosSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditActiveSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:audit-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie l\'état des sessions POS actives et le respect de l\'invariant d\'unicité';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Audit des sessions POS en cours...");

        $activeSessions = PosSession::where('is_active', 1)
            ->with('user')
            ->get();

        if ($activeSessions->isEmpty()) {
            $this->info("✅ Aucune session active détectée.");
            return 0;
        }

        $this->warn($activeSessions->count() . " session(s) active(s) détectée(s) :");

        $tableData = $activeSessions->map(function ($session) {
            return [
                'ID' => $session->id,
                'Utilisateur' => $session->user->name ?? $session->opened_by,
                'Status' => $session->status,
                'Opened At' => $session->opened_at,
            ];
        });

        $this->table(['ID', 'Utilisateur', 'Status', 'Opened At'], $tableData);

        // Vérification de l'invariant (doublons potentiels sur is_active=1)
        $duplicates = DB::table('pos_sessions')
            ->select('opened_by')
            ->where('is_active', 1)
            ->groupBy('opened_by')
            ->having(DB::raw('count(*)'), '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $this->error("🚨 VIOLATION D'INVARIANT : Des utilisateurs ont plusieurs sessions actives !");
            foreach ($duplicates as $dup) {
                $this->error("- User ID: {$dup->opened_by}");
            }
            return 1;
        }

        $this->info("✅ Invariant d'unicité respecté.");
        return 0;
    }
}

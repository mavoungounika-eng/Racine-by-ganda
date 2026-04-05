<?php

namespace App\Console\Commands\Accounting;

use Modules\Accounting\Models\AccountingEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileLedgerBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:reconcile-ledger';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie l\'équilibre débit/crédit de toutes les écritures postées (audit de balance)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Analyse de la balance du grand livre...");
        
        $entries = AccountingEntry::where('is_posted', true)->get();
        $anomalies = 0;
        $results = [];

        foreach ($entries as $entry) {
            $totalDebit = (float)$entry->lines()->sum('debit');
            $totalCredit = (float)$entry->lines()->sum('credit');
            
            if (abs($totalDebit - $totalCredit) >= 0.01) {
                $anomalies++;
                $results[] = [
                    'id' => $entry->id,
                    'number' => $entry->entry_number,
                    'date' => $entry->entry_date,
                    'debit' => $totalDebit,
                    'credit' => $totalCredit,
                    'diff' => abs($totalDebit - $totalCredit),
                ];
            }
        }

        if ($anomalies > 0) {
            $this->error("❌ CRITICAL: $anomalies écritures non équilibrées détectées !");
            $this->table(['ID', 'Numéro', 'Date', 'Total Débit', 'Total Crédit', 'Écart'], $results);
            $this->warn("L'intégrité financière du ledger est compromise. Audit manuel requis.");
        } else {
            $this->info("✅ Balance parfaite. Toutes les écritures postées sont équilibrées (Total Débit = Total Crédit).");
        }

        return $anomalies > 0 ? 1 : 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupCriticalTables extends Command
{
    protected $signature = 'app:backup-critical';
    protected $description = 'Sauvegarde les tables critiques (Stock, POS, Compta) en JSON avant migration';

    public function handle()
    {
        $tables = [
            'products',
            'pos_sessions',
            'accounting_entries',
            'accounting_entry_lines',
            'orders',
            'order_items',
            'erp_stock_movements'
        ];

        $directory = storage_path('backups/' . now()->format('Y-m-d_H-i-s'));
        File::makeDirectory($directory, 0755, true);

        $this->info("Démarrage du backup de secours dans : $directory");

        foreach ($tables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $this->warn("Table '$table' inexistante, passage...");
                continue;
            }

            $data = DB::table($table)->get();
            File::put("$directory/$table.json", $data->toJson(JSON_PRETTY_PRINT));
            $this->info("✅ Table '$table' sauvegardée (" . $data->count() . " lignes)");
        }

        $this->info("---");
        $this->info("Parachute de secours prêt. Tu peux continuer le déploiement en toute sécurité.");
        return 0;
    }
}

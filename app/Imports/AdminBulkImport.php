<?php

namespace App\Imports;

use App\Imports\Admin\CommandesImport;
use App\Imports\Admin\ClientsImport;
use App\Imports\Admin\ProduitsImport;
use App\Imports\Admin\CreateursImport;
use App\Imports\Admin\PromosImport;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AdminBulkImport implements WithMultipleSheets, SkipsUnknownSheets
{
    private array $results = [];

    public function sheets(): array
    {
        return [
            'Commandes' => new CommandesImport(),
            'Clients'   => new ClientsImport(),
            'Produits'  => new ProduitsImport(),
            'Createurs' => new CreateursImport(),
            'Promos'    => new PromosImport(),
        ];
    }

    public function onUnknownSheet(string $sheetName): void
    {
        // Ignore Finances and POS sheets (read-only)
    }

    public function getResults(): array
    {
        return $this->results;
    }
}

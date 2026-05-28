<?php

namespace App\Exports;

use App\Exports\Admin\Sheets\CommandesSheet;
use App\Exports\Admin\Sheets\ClientsSheet;
use App\Exports\Admin\Sheets\ProduitsSheet;
use App\Exports\Admin\Sheets\CreateursSheet;
use App\Exports\Admin\Sheets\FinancesSheet;
use App\Exports\Admin\Sheets\PosSheet;
use App\Exports\Admin\Sheets\PromosSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AdminMultiSheetExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new CommandesSheet(),
            new ClientsSheet(),
            new ProduitsSheet(),
            new CreateursSheet(),
            new FinancesSheet(),
            new PosSheet(),
            new PromosSheet(),
        ];
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PosReportExport implements FromArray, WithHeadings
{
    protected array $data;
    protected array $headings;

    public function __construct(array $data, array $headings)
    {
        $this->data = $data;
        $this->headings = $headings;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}

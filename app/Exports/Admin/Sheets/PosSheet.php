<?php

namespace App\Exports\Admin\Sheets;

use App\Models\PosSession;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PosSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function title(): string
    {
        return 'POS';
    }

    public function query()
    {
        return PosSession::with(['opener', 'sales'])->orderByDesc('opened_at');
    }

    public function headings(): array
    {
        return [
            'id',
            'operateur',
            'ouverture',
            'cloture',
            'statut',
            'fond_caisse',
            'total_ventes',
            'nb_tickets',
            'nb_annulations',
        ];
    }

    public function map($session): array
    {
        return [
            $session->id,
            $session->opener?->name ?? '',
            $session->opened_at?->format('d/m/Y H:i') ?? '',
            $session->closed_at?->format('d/m/Y H:i') ?? '',
            $session->status,
            number_format($session->opening_float ?? 0, 0, '.', ''),
            number_format($session->sales->sum('total') ?? 0, 0, '.', ''),
            $session->sales->count(),
            $session->sales->where('status', 'cancelled')->count(),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

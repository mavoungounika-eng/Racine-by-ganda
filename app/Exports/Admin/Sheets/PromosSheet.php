<?php

namespace App\Exports\Admin\Sheets;

use App\Models\PromoCode;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PromosSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function title(): string
    {
        return 'Promos';
    }

    public function query()
    {
        return PromoCode::orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'id',
            'code',
            'type',
            'valeur',
            'minimum_achat',
            'max_utilisations',
            'utilisations',
            'max_par_user',
            'actif',
            'debut',
            'fin',
        ];
    }

    public function map($promo): array
    {
        return [
            $promo->id,
            $promo->code,
            $promo->type,
            $promo->value,
            $promo->minimum_order_amount ?? 0,
            $promo->max_uses ?? '',
            $promo->used_count ?? 0,
            $promo->max_uses_per_user ?? '',
            $promo->is_active ? 'oui' : 'non',
            $promo->starts_at?->format('d/m/Y') ?? '',
            $promo->expires_at?->format('d/m/Y') ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

<?php

namespace App\Exports\Admin\Sheets;

use App\Models\CreatorProfile;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CreateursSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function title(): string
    {
        return 'Createurs';
    }

    public function query()
    {
        return CreatorProfile::with(['user', 'subscription.plan'])->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'id',
            'user_id',
            'nom',
            'email',
            'marque',
            'statut',
            'verifie',
            'plan',
            'nb_produits',
            'score',
            'inscription',
        ];
    }

    public function map($creator): array
    {
        return [
            $creator->id,
            $creator->user_id,
            $creator->user?->name ?? '',
            $creator->user?->email ?? '',
            $creator->brand_name ?? '',
            $creator->status ?? '',
            $creator->is_verified ? 'oui' : 'non',
            $creator->subscription?->plan?->name ?? 'FREE',
            $creator->products()->count(),
            $creator->overall_score ?? '',
            $creator->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

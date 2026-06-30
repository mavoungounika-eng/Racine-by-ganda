<?php

namespace App\Exports\Admin\Sheets;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function title(): string
    {
        return 'Clients';
    }

    public function query()
    {
        return User::whereHas('roles', fn ($q) => $q->where('slug', 'client'))
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'id',
            'nom',
            'email',
            'telephone',
            'statut',
            'email_verifie',
            'nb_commandes',
            'total_depense',
            'inscription',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone ?? '',
            $user->is_active ? 'actif' : 'desactive',
            $user->email_verified_at ? 'oui' : 'non',
            $user->orders()->count(),
            number_format($user->orders()->where('payment_status', 'paid')->sum('total_amount'), 0, '.', ''),
            $user->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

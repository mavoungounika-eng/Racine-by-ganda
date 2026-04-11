<?php

namespace Modules\CRM\Exports;

use Modules\CRM\Models\CrmContact;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class ContactsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = CrmContact::query();

        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Prénom',
            'Nom',
            'Email',
            'Téléphone',
            'Entreprise',
            'Poste',
            'Type',
            'Statut',
            'Source',
            'Date Création',
        ];
    }

    public function map($contact): array
    {
        return [
            $contact->id,
            $contact->first_name,
            $contact->last_name ?? '-',
            $contact->email ?? '-',
            $contact->phone ?? '-',
            $contact->company ?? '-',
            $contact->position ?? '-',
            ucfirst($contact->type),
            ucfirst($contact->status),
            $contact->source ?? '-',
            $contact->created_at->format('d/m/Y'),
        ];
    }
}

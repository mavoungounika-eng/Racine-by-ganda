<?php

namespace Modules\ERP\Exports;

use Modules\ERP\Models\ErpStockMovement;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class StockMovementsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = ErpStockMovement::with(['stockable', 'user']);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Type',
            'Produit/Matière',
            'Quantité',
            'Raison',
            'De',
            'Vers',
            'Utilisateur',
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->id,
            $movement->created_at->format('d/m/Y H:i'),
            $movement->type === 'in' ? 'Entrée' : 'Sortie',
            $movement->stockable ? $movement->stockable->title ?? $movement->stockable->name : 'N/A',
            $movement->quantity,
            $movement->reason ?? '-',
            $movement->from_location ?? '-',
            $movement->to_location ?? '-',
            $movement->user ? $movement->user->name : 'Système',
        ];
    }
}

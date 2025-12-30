<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Product::with('category');

        if (!empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (!empty($this->filters['status'])) {
            if ($this->filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($this->filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if (!empty($this->filters['stock'])) {
            if ($this->filters['stock'] === 'low') {
                $query->where('stock', '<', 10)->where('stock', '>', 0);
            } elseif ($this->filters['stock'] === 'out') {
                $query->where('stock', '<=', 0);
            }
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Titre',
            'Catégorie',
            'Prix (XAF)',
            'Stock',
            'Statut',
            'Date de création',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->title,
            $product->category->name ?? '-',
            number_format($product->price, 0, ',', ' '),
            $product->stock,
            $product->is_active ? 'Actif' : 'Inactif',
            $product->created_at->format('d/m/Y H:i'),
        ];
    }
}


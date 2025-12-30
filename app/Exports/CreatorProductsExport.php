<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CreatorProductsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;
    protected $userId;

    public function __construct(array $filters = [], int $userId = null)
    {
        $this->filters = $filters;
        $this->userId = $userId ?? Auth::id();
    }

    public function query(): Builder
    {
        $query = Product::where('user_id', $this->userId)
            ->with('category');

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
            'Ventes',
            'Date de création',
        ];
    }

    public function map($product): array
    {
        // Calculer les ventes (nombre d'items vendus)
        $sales = \App\Models\OrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($q) {
                $q->where('status', 'completed')
                  ->where('payment_status', 'paid');
            })
            ->sum('quantity');

        return [
            $product->id,
            $product->title,
            $product->category->name ?? '-',
            number_format($product->price, 0, ',', ' '),
            $product->stock,
            $product->is_active ? 'Actif' : 'Inactif',
            $sales,
            $product->created_at->format('d/m/Y H:i'),
        ];
    }
}


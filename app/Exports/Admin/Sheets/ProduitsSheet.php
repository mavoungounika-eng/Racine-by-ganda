<?php

namespace App\Exports\Admin\Sheets;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProduitsSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function title(): string
    {
        return 'Produits';
    }

    public function query()
    {
        return Product::with(['category', 'creator'])->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'id',
            'titre',
            'categorie',
            'createur',
            'prix',
            'prix_compare',
            'stock',
            'statut',
            'slug',
            'creation',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->title,
            $product->category?->name ?? '',
            $product->creator?->brand_name ?? '',
            number_format($product->price ?? 0, 0, '.', ''),
            number_format($product->compare_price ?? 0, 0, '.', ''),
            $product->stock ?? 0,
            $product->status ?? 'draft',
            $product->slug,
            $product->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

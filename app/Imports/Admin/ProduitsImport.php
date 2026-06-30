<?php

namespace App\Imports\Admin;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProduitsImport implements ToModel, WithHeadingRow, WithUpserts, WithValidation
{
    private int $updated = 0;

    public function model(array $row): ?Product
    {
        if (empty($row['id'])) {
            return null;
        }

        $product = Product::find((int) $row['id']);
        if (!$product) {
            return null;
        }

        $updatable = array_filter([
            'price'   => isset($row['prix']) && is_numeric($row['prix']) ? (float) $row['prix'] : null,
            'stock'   => isset($row['stock']) && is_numeric($row['stock']) ? (int) $row['stock'] : null,
            'status'  => $row['statut'] ?? null,
        ], fn ($v) => $v !== null);

        if (!empty($updatable)) {
            $product->update($updatable);
            $this->updated++;
        }

        return null;
    }

    public function uniqueBy(): array
    {
        return ['id'];
    }

    public function rules(): array
    {
        return [
            'id'    => 'required|integer',
            'prix'  => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
        ];
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }
}

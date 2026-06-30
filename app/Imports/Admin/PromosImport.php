<?php

namespace App\Imports\Admin;

use App\Models\PromoCode;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class PromosImport implements ToModel, WithHeadingRow, WithUpserts, WithValidation
{
    private int $updated = 0;

    public function model(array $row): ?PromoCode
    {
        if (empty($row['id'])) {
            return null;
        }

        $promo = PromoCode::find((int) $row['id']);
        if (!$promo) {
            return null;
        }

        $updatable = array_filter([
            'is_active' => isset($row['actif'])
                ? in_array(strtolower((string) $row['actif']), ['oui', '1', 'true', 'yes'], true)
                : null,
            'max_uses'  => isset($row['max_utilisations']) && is_numeric($row['max_utilisations'])
                ? (int) $row['max_utilisations']
                : null,
        ], fn ($v) => $v !== null);

        if (!empty($updatable)) {
            $promo->update($updatable);
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
            'id'                => 'required|integer',
            'max_utilisations'  => 'nullable|integer|min:0',
        ];
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }
}

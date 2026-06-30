<?php

namespace App\Imports\Admin;

use App\Models\CreatorProfile;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class CreateursImport implements ToModel, WithHeadingRow, WithUpserts, WithValidation
{
    private int $updated = 0;

    public function model(array $row): ?CreatorProfile
    {
        if (empty($row['id'])) {
            return null;
        }

        $creator = CreatorProfile::find((int) $row['id']);
        if (!$creator) {
            return null;
        }

        $updatable = array_filter([
            'status'      => $row['statut'] ?? null,
            'is_verified' => isset($row['verifie'])
                ? in_array(strtolower((string) $row['verifie']), ['oui', '1', 'true', 'yes'], true)
                : null,
        ], fn ($v) => $v !== null);

        if (!empty($updatable)) {
            $creator->update($updatable);
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
            'id' => 'required|integer',
        ];
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }
}

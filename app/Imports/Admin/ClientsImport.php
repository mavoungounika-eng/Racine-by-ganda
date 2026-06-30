<?php

namespace App\Imports\Admin;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class ClientsImport implements ToModel, WithHeadingRow, WithUpserts, WithValidation
{
    private int $updated = 0;

    public function model(array $row): ?User
    {
        if (empty($row['id'])) {
            return null;
        }

        $user = User::find((int) $row['id']);
        if (!$user) {
            return null;
        }

        $statut = $row['statut'] ?? null;
        if ($statut !== null) {
            $isActive = in_array(strtolower($statut), ['actif', 'active', '1', 'oui'], true);
            $user->update(['is_active' => $isActive]);
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

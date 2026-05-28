<?php

namespace App\Imports\Admin;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class CommandesImport implements ToModel, WithHeadingRow, WithUpserts, WithValidation
{
    private int $updated = 0;

    public function model(array $row): ?Order
    {
        if (empty($row['id'])) {
            return null;
        }

        $order = Order::find((int) $row['id']);
        if (!$order) {
            return null;
        }

        $updatable = array_filter([
            'status'       => $row['statut'] ?? null,
            'admin_notes'  => $row['notes_admin'] ?? null,
        ]);

        if (!empty($updatable)) {
            $order->update($updatable);
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

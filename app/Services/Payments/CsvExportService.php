<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;
use Illuminate\Support\Collection;

/**
 * Service pour exporter des données en CSV avec protection anti-injection
 * 
 * Échappe les cellules commençant par =, +, -, @ pour éviter l'exécution de formules Excel
 */
class CsvExportService
{
    /**
     * Exporter les transactions en CSV
     *
     * @param Collection $transactions
     * @return string
     */
    public function exportTransactions(Collection $transactions): string
    {
        $headers = [
            'ID',
            'Provider',
            'Order ID',
            'Payment Ref',
            'Transaction ID',
            'Amount',
            'Currency',
            'Status',
            'Operator',
            'Phone',
            'Fee',
            'Created At',
        ];

        $rows = [];
        $rows[] = $this->escapeRow($headers);

        foreach ($transactions as $transaction) {
            $row = [
                $transaction->id,
                $transaction->provider,
                $transaction->order_id ?? '',
                $transaction->payment_ref ?? '',
                $transaction->transaction_id ?? '',
                $transaction->amount ?? 0,
                $transaction->currency ?? 'XAF',
                $transaction->status,
                $transaction->operator ?? '',
                $transaction->phone ?? '',
                $transaction->fee ?? 0,
                $transaction->created_at?->format('Y-m-d H:i:s') ?? '',
            ];

            $rows[] = $this->escapeRow($row);
        }

        return implode("\n", $rows);
    }

    /**
     * Échapper une ligne CSV (anti-injection)
     *
     * @param array $row
     * @return string
     */
    private function escapeRow(array $row): string
    {
        $escaped = array_map(function ($cell) {
            return $this->escapeCell($cell);
        }, $row);

        return $this->formatCsvRow($escaped);
    }

    /**
     * Échapper une cellule CSV (anti-injection)
     * 
     * Préfixe avec ' si la valeur commence par =, +, -, @
     *
     * @param mixed $value
     * @return string
     */
    private function escapeCell($value): string
    {
        $stringValue = (string) $value;

        // Vérifier si la valeur commence par un caractère dangereux
        if (preg_match('/^[=+\-@]/', $stringValue)) {
            // Préfixer avec ' pour désactiver l'interprétation Excel
            return "'" . $stringValue;
        }

        return $stringValue;
    }

    /**
     * Formater une ligne CSV
     *
     * @param array $row
     * @return string
     */
    private function formatCsvRow(array $row): string
    {
        $formatted = array_map(function ($cell) {
            // Échapper les guillemets doubles
            $cell = str_replace('"', '""', $cell);
            
            // Encapsuler dans des guillemets si contient des caractères spéciaux
            if (str_contains($cell, ',') || str_contains($cell, '"') || str_contains($cell, "\n")) {
                return '"' . $cell . '"';
            }

            return $cell;
        }, $row);

        return implode(',', $formatted);
    }
}





<?php

namespace App\Exports\Admin\Sheets;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CommandesSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles
{
    public function title(): string
    {
        return 'Commandes';
    }

    public function query()
    {
        return Order::with(['user', 'items'])->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'id',
            'order_number',
            'date',
            'client_nom',
            'client_email',
            'statut',
            'statut_paiement',
            'methode_paiement',
            'nb_articles',
            'sous_total',
            'frais_livraison',
            'remise',
            'total',
            'notes_admin',
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->order_number,
            $order->created_at->format('d/m/Y H:i'),
            $order->user?->name ?? 'Invité',
            $order->user?->email ?? $order->guest_email ?? '',
            $order->status,
            $order->payment_status,
            $order->payment_method ?? '',
            $order->items->count(),
            number_format($order->subtotal ?? 0, 0, '.', ''),
            number_format($order->shipping_cost ?? 0, 0, '.', ''),
            number_format($order->discount ?? 0, 0, '.', ''),
            number_format($order->total_amount ?? 0, 0, '.', ''),
            $order->admin_notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class OrdersExport implements FromQuery, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Order::with(['user', 'items']);

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['payment_status'])) {
            $query->where('payment_status', $this->filters['payment_status']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Client',
            'Email',
            'Téléphone',
            'Montant Total',
            'Statut',
            'Statut Paiement',
            'Nb Articles',
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->created_at->format('d/m/Y H:i'),
            $order->customer_name ?? ($order->user ? $order->user->name : 'Invité'),
            $order->customer_email ?? ($order->user ? $order->user->email : '-'),
            $order->customer_phone ?? '-',
            number_format($order->total_amount, 0, ',', ' ') . ' XAF',
            ucfirst($order->status),
            ucfirst($order->payment_status),
            $order->items->count(),
        ];
    }
}

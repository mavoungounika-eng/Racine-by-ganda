<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CreatorOrdersExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $userId;

    public function __construct(array $filters = [], int $userId = null)
    {
        $this->filters = $filters;
        $this->userId = $userId ?? Auth::id();
    }

    public function collection(): Collection
    {
        $query = Order::whereHas('items.product', function ($q) {
            $q->where('user_id', $this->userId);
        })
        ->with(['items.product' => function ($q) {
            $q->where('user_id', $this->userId);
        }])
        ->with('user');

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

        $orders = $query->latest()->get();

        // Transformer les données pour inclure seulement les produits du créateur
        return $orders->map(function ($order) {
            $creatorItems = $order->items->filter(function ($item) {
                return $item->product && $item->product->user_id === $this->userId;
            });
            
            $creatorTotal = $creatorItems->sum(function ($item) {
                return $item->price * $item->quantity;
            });

            $order->creator_items = $creatorItems;
            $order->creator_total = $creatorTotal;
            $order->creator_items_count = $creatorItems->count();

            return $order;
        });
    }

    public function headings(): array
    {
        return [
            'ID Commande',
            'Date',
            'Client',
            'Email',
            'Nb Produits (moi)',
            'CA Brut (moi)',
            'Commission (20%)',
            'Net (moi)',
            'Statut',
            'Paiement',
        ];
    }

    public function map($order): array
    {
        $commission = $order->creator_total * 0.20;
        $net = $order->creator_total - $commission;

        return [
            $order->id,
            $order->created_at->format('d/m/Y H:i'),
            $order->customer_name ?? ($order->user ? $order->user->name : 'Invité'),
            $order->customer_email ?? ($order->user ? $order->user->email : '-'),
            $order->creator_items_count,
            number_format($order->creator_total, 0, ',', ' ') . ' XAF',
            number_format($commission, 0, ',', ' ') . ' XAF',
            number_format($net, 0, ',', ' ') . ' XAF',
            ucfirst($order->status),
            ucfirst($order->payment_status),
        ];
    }
}


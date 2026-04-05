<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Anomalie de stock critique — broadcast sur canal admin uniquement.
 */
class StockAnomalyDetected implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int     $product_id,
        public readonly ?int    $order_id,
        public readonly int     $requested_qty,
        public readonly int     $available_stock,
        public readonly string  $detected_at,
    ) {}

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.stock'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.anomaly';
    }

    public function broadcastWith(): array
    {
        return [
            'product_id'      => $this->product_id,
            'order_id'        => $this->order_id,
            'requested_qty'   => $this->requested_qty,
            'available_stock' => $this->available_stock,
            'detected_at'     => $this->detected_at,
            'timestamp'       => now()->toIso8601String(),
        ];
    }
}

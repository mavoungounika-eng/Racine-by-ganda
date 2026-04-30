<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Alerte stock bas — broadcast via queue (moins urgent que StockDecremented).
 */
class StockLowAlert implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int     $product_id,
        public readonly string  $product_name,
        public readonly int     $current_stock,
        public readonly int     $threshold,
        public readonly string  $source,    // 'pos_sale' | 'web_order' | 'manual'
    ) {}

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.stock'),
            new PrivateChannel("stock.{$this->product_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.low_alert';
    }

    public function broadcastWith(): array
    {
        return [
            'product_id'    => $this->product_id,
            'product_name'  => $this->product_name,
            'current_stock' => $this->current_stock,
            'threshold'     => $this->threshold,
            'source'        => $this->source,
            'timestamp'     => now()->toIso8601String(),
        ];
    }
}

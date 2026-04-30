<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Décrémentation de stock — broadcast en temps réel (ShouldBroadcastNow = bypass queue).
 * Diffusé sur 3 canaux : produit spécifique, admin global, multi-caisses POS.
 */
class StockDecremented implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int     $product_id,
        public readonly int     $qty_removed,
        public readonly int     $stock_before,
        public readonly int     $stock_after,
        public readonly string  $source,        // 'pos_sale' | 'web_order' | 'manual'
        public readonly ?int    $reference_id,
    ) {}

    /**
     * Canaux de diffusion.
     *
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("stock.{$this->product_id}"),
            new PrivateChannel('admin.stock'),
            new PrivateChannel('pos.broadcast'),
        ];
    }

    /**
     * Nom de l'événement côté client (préfixé par '.' dans Echo).
     */
    public function broadcastAs(): string
    {
        return 'stock.decremented';
    }

    /**
     * Payload broadcast — inclut product_name résolu et device source.
     */
    public function broadcastWith(): array
    {
        return [
            'product_id'        => $this->product_id,
            'product_name'      => Product::find($this->product_id)?->title ?? 'Produit',
            'stock_before'      => $this->stock_before,
            'stock_after'       => $this->stock_after,
            'source'            => $this->source,
            'reference_id'      => $this->reference_id,
            'updated_by_device' => request()->header('X-Device-Id', 'web'),
            'timestamp'         => now()->toIso8601String(),
        ];
    }
}

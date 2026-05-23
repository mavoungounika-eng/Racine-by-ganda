<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id'                 => Order::factory(),
            'product_id'               => Product::factory(),
            'quantity'                 => 1,
            'price'                    => 5000,
            'status'                   => OrderItem::STATUS_ACTIVE,
            'cancelled_at'             => null,
            'previous_cancellation_id' => null,
        ];
    }
}

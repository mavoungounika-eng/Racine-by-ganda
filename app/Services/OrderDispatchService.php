<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderVendor;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderDispatchService
{
    /**
     * Default commission rate (15%)
     */
    const DEFAULT_COMMISSION_RATE = 15.00;

    /**
     * Split an order by vendors and create OrderVendor records
     */
    public function splitOrderByVendors(Order $order): void
    {
        try {
            DB::beginTransaction();

            // Group order items by vendor
            $vendorGroups = $this->groupItemsByVendor($order);

            // Create OrderVendor for each vendor
            foreach ($vendorGroups as $vendorId => $items) {
                $this->createOrderVendor($order, $vendorId, $items);
            }

            DB::commit();

            Log::info("Order #{$order->id} successfully split into " . count($vendorGroups) . " vendor orders");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to split order #{$order->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Group order items by vendor
     */
    protected function groupItemsByVendor(Order $order): array
    {
        $groups = [];

        foreach ($order->items as $item) {
            // Get product to determine vendor
            $product = Product::find($item->product_id);
            
            if (!$product) {
                continue;
            }

            // Determine vendor_id and vendor_type
            if ($product->isBrand()) {
                $vendorId = $product->user_id; // Brand user ID
                $vendorType = 'brand';
            } else {
                $vendorId = $product->user_id; // Creator user ID
                $vendorType = 'creator';
            }

            // Update order item with vendor info
            $item->update([
                'vendor_id' => $vendorId,
                'vendor_type' => $vendorType,
            ]);

            // Group items
            if (!isset($groups[$vendorId])) {
                $groups[$vendorId] = [
                    'vendor_type' => $vendorType,
                    'items' => [],
                ];
            }

            $groups[$vendorId]['items'][] = $item;
        }

        return $groups;
    }

    /**
     * Create OrderVendor record for a specific vendor
     */
    protected function createOrderVendor(Order $order, int $vendorId, array $vendorData): OrderVendor
    {
        $items = $vendorData['items'];
        $vendorType = $vendorData['vendor_type'];

        // Calculate subtotal
        $subtotal = collect($items)->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        // Get commission rate (default 15%, or custom rate if exists)
        $commissionRate = $this->getCommissionRate($vendorId, $vendorType);

        // Calculate commission and payout
        $commissionAmount = ($subtotal * $commissionRate) / 100;
        $vendorPayout = $subtotal - $commissionAmount;

        // Create OrderVendor
        return OrderVendor::create([
            'order_id' => $order->id,
            'vendor_id' => $vendorId,
            'vendor_type' => $vendorType,
            'subtotal' => $subtotal,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'vendor_payout' => $vendorPayout,
            'status' => 'pending',
            'payout_status' => 'pending',
        ]);
    }

    /**
     * Get commission rate for a vendor
     */
    protected function getCommissionRate(int $vendorId, string $vendorType): float
    {
        // Brand products have 0% commission
        if ($vendorType === 'brand') {
            return 0.00;
        }

        // For creators, check if custom rate exists
        // TODO: Implement creator_commissions table lookup in Phase 5
        // For now, return default rate
        return self::DEFAULT_COMMISSION_RATE;
    }

    /**
     * Update vendor order status
     */
    public function updateVendorStatus(OrderVendor $orderVendor, string $status): void
    {
        $orderVendor->update(['status' => $status]);

        // Auto-update timestamps
        if ($status === 'shipped') {
            $orderVendor->update(['shipped_at' => now()]);
        } elseif ($status === 'delivered') {
            $orderVendor->update(['delivered_at' => now()]);
        }

        Log::info("OrderVendor #{$orderVendor->id} status updated to: {$status}");
    }

    /**
     * Mark payout as paid
     */
    public function markPayoutPaid(OrderVendor $orderVendor): void
    {
        $orderVendor->update([
            'payout_status' => 'paid',
            'payout_at' => now(),
        ]);

        Log::info("OrderVendor #{$orderVendor->id} payout marked as paid");
    }
}

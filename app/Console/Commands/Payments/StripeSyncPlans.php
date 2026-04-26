<?php

namespace App\Console\Commands\Payments;

use App\Models\CreatorPlan;
use Illuminate\Console\Command;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class StripeSyncPlans extends Command
{
    protected $signature = 'stripe:sync-plans {--dry-run : Show matches without saving} {--limit=100 : Max Stripe prices to scan}';

    protected $description = 'Synchronize creator_plans stripe_price_id and stripe_product_id from Stripe prices';

    public function handle(): int
    {
        $stripeSecret = config('services.stripe.secret');

        if (empty($stripeSecret)) {
            $this->error('STRIPE_SECRET is missing. Configure Stripe first.');
            return self::FAILURE;
        }

        Stripe::setApiKey($stripeSecret);

        $plans = CreatorPlan::query()
            ->where('is_active', true)
            ->where('price', '>', 0)
            ->orderBy('price')
            ->get();

        if ($plans->isEmpty()) {
            $this->warn('No paid active creator plans found.');
            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $currency = strtolower((string) config('services.stripe.currency', 'xaf'));

        try {
            $prices = Price::all([
                'active' => true,
                'type' => 'recurring',
                'limit' => $limit,
            ])->data;
        } catch (\Throwable $e) {
            $this->error('Unable to query Stripe prices: ' . $e->getMessage());
            return self::FAILURE;
        }

        $rows = [];
        $updated = 0;
        $productCache = [];

        foreach ($plans as $plan) {
            [$matchedPrice, $reason] = $this->matchPriceForPlan($plan, $prices, $currency, $productCache);

            if (!$matchedPrice) {
                $rows[] = [
                    $plan->code,
                    $plan->name,
                    (string) $plan->price,
                    $plan->stripe_price_id ?: '-',
                    '-',
                    'NO MATCH',
                ];
                continue;
            }

            $newPriceId = $matchedPrice->id;
            $newProductId = $matchedPrice->product;
            $alreadySynced = $plan->stripe_price_id === $newPriceId && $plan->stripe_product_id === $newProductId;

            if (!$alreadySynced && !$dryRun) {
                $plan->update([
                    'stripe_price_id' => $newPriceId,
                    'stripe_product_id' => $newProductId,
                ]);
                $updated++;
            }

            $rows[] = [
                $plan->code,
                $plan->name,
                (string) $plan->price,
                $plan->stripe_price_id ?: '-',
                $newPriceId,
                $alreadySynced ? 'UNCHANGED' : ($dryRun ? 'MATCHED (DRY RUN)' : "UPDATED ({$reason})"),
            ];
        }

        $this->table(['code', 'plan', 'price', 'current_price_id', 'matched_price_id', 'status'], $rows);

        if ($dryRun) {
            $this->info('Dry run completed. No database changes were made.');
        } else {
            $this->info("Synchronization completed. {$updated} plan(s) updated.");
        }

        return self::SUCCESS;
    }

    /**
     * @param array<int, \Stripe\Price> $prices
     * @param array<string, \Stripe\Product> $productCache
     * @return array{0: \Stripe\Price|null, 1: string}
     */
    protected function matchPriceForPlan(CreatorPlan $plan, array $prices, string $currency, array &$productCache): array
    {
        $targetAmount = (int) round((float) $plan->price * 100);

        foreach ($prices as $price) {
            $metaPlanId = (string) ($price->metadata->plan_id ?? '');
            $metaPlanCode = (string) ($price->metadata->plan_code ?? '');

            if ($metaPlanId === (string) $plan->id || $metaPlanCode === (string) $plan->code) {
                return [$price, 'metadata'];
            }
        }

        foreach ($prices as $price) {
            $sameCurrency = strtolower((string) $price->currency) === $currency;
            $sameAmount = (int) $price->unit_amount === $targetAmount;
            $monthly = ($price->recurring->interval ?? null) === 'month';

            if (!$sameCurrency || !$sameAmount || !$monthly) {
                continue;
            }

            $productName = $this->productName((string) $price->product, $productCache);
            if ($productName !== null && stripos($productName, $plan->name) !== false) {
                return [$price, 'amount+name'];
            }
        }

        return [null, 'none'];
    }

    /**
     * @param array<string, \Stripe\Product> $productCache
     */
    protected function productName(string $productId, array &$productCache): ?string
    {
        if (isset($productCache[$productId])) {
            return $productCache[$productId]->name;
        }

        try {
            $product = Product::retrieve($productId);
            $productCache[$productId] = $product;
            return $product->name;
        } catch (\Throwable) {
            return null;
        }
    }
}


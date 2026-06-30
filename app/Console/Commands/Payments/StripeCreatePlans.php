<?php

namespace App\Console\Commands\Payments;

use App\Models\CreatorPlan;
use Illuminate\Console\Command;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class StripeCreatePlans extends Command
{
    protected $signature = 'stripe:create-plans
                            {--dry-run : Show what would be created without calling Stripe}
                            {--force   : Re-create prices even if stripe_price_id already set}';

    protected $description = 'Create Stripe products + prices for atelier/maison/signature and link them to DB';

    /**
     * Zero-decimal currencies in Stripe — unit_amount = actual amount (no *100).
     */
    private const ZERO_DECIMAL = ['bif','clp','djf','gnf','jpy','kmf','krw','mga','pyg','rwf','ugx','vnd','vuv','xaf','xof','xpf'];

    public function handle(): int
    {
        $stripeSecret = config('services.stripe.secret');

        if (empty($stripeSecret)) {
            $this->error('STRIPE_SECRET manquant.');
            return self::FAILURE;
        }

        Stripe::setApiKey($stripeSecret);

        $currency = strtolower((string) config('services.stripe.currency', 'xaf'));
        $dryRun   = (bool) $this->option('dry-run');
        $force    = (bool) $this->option('force');

        $plans = CreatorPlan::whereIn('code', ['atelier', 'maison', 'signature'])
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        if ($plans->isEmpty()) {
            $this->error('Plans atelier/maison/signature introuvables. Exécuter db:seed --class=CreatorPlanSeeder d\'abord.');
            return self::FAILURE;
        }

        $rows = [];

        foreach ($plans as $plan) {
            if ($plan->stripe_price_id && ! $force) {
                $rows[] = [$plan->code, $plan->name, $plan->price, $plan->stripe_price_id, 'SKIPPED (already linked, use --force to override)'];
                continue;
            }

            if ($dryRun) {
                $rows[] = [$plan->code, $plan->name, $plan->price, '-', 'DRY RUN — would create product + price'];
                continue;
            }

            try {
                [$priceId, $productId] = $this->createStripeProductAndPrice($plan, $currency);

                $plan->update([
                    'stripe_price_id'    => $priceId,
                    'stripe_product_id'  => $productId,
                ]);

                $rows[] = [$plan->code, $plan->name, $plan->price, $priceId, 'CREATED'];
            } catch (\Throwable $e) {
                $rows[] = [$plan->code, $plan->name, $plan->price, '-', 'ERROR: ' . $e->getMessage()];
                $this->error("Échec pour {$plan->code}: " . $e->getMessage());
            }
        }

        $this->table(['code', 'plan', 'price (FCFA)', 'stripe_price_id', 'status'], $rows);

        if (! $dryRun) {
            $this->info('Exécuter stripe:sync-plans pour vérifier la liaison.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string} [price_id, product_id]
     */
    private function createStripeProductAndPrice(CreatorPlan $plan, string $currency): array
    {
        $unitAmount = $this->toStripeAmount((float) $plan->price, $currency);

        $product = Product::create([
            'name'     => 'Racine — ' . $plan->name,
            'metadata' => [
                'plan_code' => $plan->code,
                'plan_id'   => (string) $plan->id,
                'platform'  => 'racine',
            ],
        ]);

        $price = Price::create([
            'product'     => $product->id,
            'currency'    => $currency,
            'unit_amount' => $unitAmount,
            'recurring'   => ['interval' => 'month'],
            'metadata'    => [
                'plan_code' => $plan->code,
                'plan_id'   => (string) $plan->id,
                'platform'  => 'racine',
            ],
        ]);

        return [$price->id, $product->id];
    }

    private function toStripeAmount(float $price, string $currency): int
    {
        if (in_array($currency, self::ZERO_DECIMAL, true)) {
            return (int) round($price);
        }

        return (int) round($price * 100);
    }
}

<?php

namespace Database\Seeders;

use App\Models\PaymentProvider;
use App\Models\PaymentRoutingRule;
use Illuminate\Database\Seeder;

class PaymentRoutingRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les providers
        $stripe = PaymentProvider::where('code', 'stripe')->first();
        $monetbil = PaymentProvider::where('code', 'monetbil')->first();

        if (!$stripe || !$monetbil) {
            $this->command->warn('Payment providers not found. Run PaymentProviderSeeder first.');
            return;
        }

        // Règle : Card -> Stripe
        PaymentRoutingRule::updateOrCreate(
            [
                'channel' => 'card',
                'currency' => null,
                'country' => null,
            ],
            [
                'primary_provider_id' => $stripe->id,
                'fallback_provider_id' => null,
                'is_active' => true,
                'priority' => 1,
            ]
        );

        // Règle : Mobile Money -> Monetbil
        PaymentRoutingRule::updateOrCreate(
            [
                'channel' => 'mobile_money',
                'currency' => null,
                'country' => null,
            ],
            [
                'primary_provider_id' => $monetbil->id,
                'fallback_provider_id' => null,
                'is_active' => true,
                'priority' => 1,
            ]
        );
    }
}





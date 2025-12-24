<?php

namespace Database\Seeders;

use App\Models\PaymentProvider;
use Illuminate\Database\Seeder;

class PaymentProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Stripe
        PaymentProvider::updateOrCreate(
            ['code' => 'stripe'],
            [
                'name' => 'Stripe',
                'is_enabled' => true,
                'priority' => 1,
                'currency' => 'XAF',
                'health_status' => 'ok',
                'meta' => [
                    'widget_version' => null, // Stripe n'utilise pas de widget
                    'supported_channels' => ['card'],
                ],
            ]
        );

        // Monetbil
        PaymentProvider::updateOrCreate(
            ['code' => 'monetbil'],
            [
                'name' => 'Monetbil',
                'is_enabled' => true,
                'priority' => 2,
                'currency' => 'XAF',
                'health_status' => 'ok',
                'meta' => [
                    'widget_version' => 'v2.1',
                    'supported_channels' => ['mobile_money'],
                ],
            ]
        );
    }
}





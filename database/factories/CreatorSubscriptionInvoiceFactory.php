<?php

namespace Database\Factories;

use App\Models\CreatorSubscription;
use App\Models\CreatorSubscriptionInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreatorSubscriptionInvoiceFactory extends Factory
{
    protected $model = CreatorSubscriptionInvoice::class;

    public function definition(): array
    {
        return [
            'creator_subscription_id' => CreatorSubscription::factory(),
            'stripe_invoice_id' => 'in_' . fake()->unique()->bothify('##########'),
            'stripe_charge_id' => 'ch_' . fake()->unique()->bothify('##########'),
            'amount' => fake()->randomFloat(2, 1000, 20000),
            'currency' => 'xaf',
            'status' => 'paid',
            'paid_at' => now(),
            'due_date' => now(),
            'hosted_invoice_url' => fake()->url(),
            'invoice_pdf' => null,
            'metadata' => [],
        ];
    }
}




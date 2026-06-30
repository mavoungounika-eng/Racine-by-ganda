<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CurrencyRate;

class CurrencyRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['from' => 'XAF', 'to' => 'EUR', 'rate' => 0.001524],
            ['from' => 'XAF', 'to' => 'XOF', 'rate' => 1.000000],
            ['from' => 'XOF', 'to' => 'EUR', 'rate' => 0.001524],
            ['from' => 'XOF', 'to' => 'XAF', 'rate' => 1.000000],
            ['from' => 'EUR', 'to' => 'XAF', 'rate' => 655.957000],
            ['from' => 'EUR', 'to' => 'XOF', 'rate' => 655.957000],
        ];

        foreach ($rates as $rate) {
            DB::table('currency_rates')->updateOrInsert(
                [
                    'from_currency' => $rate['from'],
                    'to_currency' => $rate['to'],
                    'effective_date' => now()->toDateString(),
                ],
                [
                    'rate' => $rate['rate'],
                    'source' => 'fixed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

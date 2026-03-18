<?php

namespace Database\Seeders;

use App\Models\LoyaltyLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LoyaltyLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function up(): void
    {
        $levels = config('crm.loyalty_levels', []);

        foreach ($levels as $level) {
            LoyaltyLevel::updateOrCreate(
                ['slug' => Str::slug($level['name'])],
                [
                    'name'        => $level['name'],
                    'min_points'  => $level['min_points'],
                    'color'       => $level['color'],
                    'description' => $level['description'] ?? null,
                    'benefits'    => $level['benefits'] ?? [],
                ]
            );
        }
    }

    /**
     * Compatibility with older seeder calls
     */
    public function run(): void
    {
        $this->up();
    }
}

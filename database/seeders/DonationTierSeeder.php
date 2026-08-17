<?php

namespace Database\Seeders;

use App\Models\DonationTier;
use Illuminate\Database\Seeder;

/**
 * Default "Support us" donation tiers (bKash Send Money). Amounts are starting
 * defaults — the owner can rename/reprice/reorder/disable any tier any time
 * from the Filament admin without a code change. Run:
 *   php artisan db:seed --class=DonationTierSeeder
 */
class DonationTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['key' => 'coffee', 'label' => 'Coffee', 'amount' => 20, 'icon' => 'cafe-outline', 'sort_order' => 1],
            ['key' => 'cookie_coffee', 'label' => 'Cookie + Coffee', 'amount' => 50, 'icon' => 'ice-cream-outline', 'sort_order' => 2],
            ['key' => 'burger', 'label' => 'Burger', 'amount' => 100, 'icon' => 'fast-food-outline', 'sort_order' => 3],
            ['key' => 'pizza', 'label' => 'Pizza', 'amount' => 200, 'icon' => 'pizza-outline', 'sort_order' => 4],
            ['key' => 't_shirt', 'label' => 'T-Shirt', 'amount' => 500, 'icon' => 'shirt-outline', 'sort_order' => 5],
            ['key' => 'pant', 'label' => 'Pant', 'amount' => 1000, 'icon' => 'bag-outline', 'sort_order' => 6],
        ];

        foreach ($tiers as $tier) {
            DonationTier::query()->updateOrCreate(['key' => $tier['key']], $tier + ['is_active' => true]);
        }
    }
}

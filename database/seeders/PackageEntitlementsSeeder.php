<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * Sets the offline-first plan ENTITLEMENTS on the existing tiers, per the owner's
 * spec — WITHOUT touching price/duration/description (so manual pricing is kept):
 *   Free Trial : 1 business, all categories, basic ledger (no backup)
 *   Monthly    : 10 businesses, all categories, everything (no backup — Yearly-exclusive)
 *   Yearly     : unlimited, all categories, everything incl. backup
 *
 * Idempotent + safe: only updates the three named packages via the model (so
 * JSON casts apply); never deletes or reprices anything. Run:
 *   php artisan db:seed --class=PackageEntitlementsSeeder
 */
class PackageEntitlementsSeeder extends Seeder
{
    public function run(): void
    {
        $ledger = ['products', 'reports', 'notes', 'tagada'];
        $withBackup = array_merge($ledger, ['backup', 'auto_backup']);
        // dark_mode is Diamond-exclusive (not folded into $withLiveSync, which
        // Yearly does not get either, but keeping it named separately makes
        // that exclusivity explicit rather than implied by bundling order).
        $withLiveSync = array_merge($withBackup, ['live_sync', 'dark_mode']);

        $tiers = [
            'Free Trial' => ['max_businesses' => 1, 'allowed_categories' => null, 'features' => $ledger],
            'Monthly' => ['max_businesses' => 10, 'allowed_categories' => null, 'features' => $ledger],
            'Yearly' => ['max_businesses' => null, 'allowed_categories' => null, 'features' => $withBackup],
            'Diamond' => ['max_businesses' => null, 'allowed_categories' => null, 'features' => $withLiveSync],
        ];

        foreach ($tiers as $name => $entitlements) {
            $package = Package::where('name', $name)->first();
            if ($package) {
                $package->update($entitlements); // model update → JSON casts applied
            }
        }
    }
}

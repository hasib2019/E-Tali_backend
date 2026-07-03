<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Free Trial',
                'price' => 0,
                'duration_days' => 14,
                'description' => '14-day trial with limited businesses.',
                'max_businesses' => 1,
                'max_parties' => 50,
            ],
            [
                'name' => 'Monthly',
                'price' => 199,
                'duration_days' => 30,
                'description' => 'Unlimited businesses & parties, billed monthly.',
                'max_businesses' => null,
                'max_parties' => null,
            ],
            [
                'name' => 'Yearly',
                'price' => 1999,
                'duration_days' => 365,
                'description' => 'Unlimited everything, best value.',
                'max_businesses' => null,
                'max_parties' => null,
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(['name' => $package['name']], $package + ['is_active' => true]);
        }
    }
}

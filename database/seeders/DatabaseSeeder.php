<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            PackageSeeder::class,
        ]);

        // A ready-to-use, verified + subscribed test account for the mobile app.
        $monthly = Package::where('name', 'Monthly')->first();

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
                'provider' => 'email',
                'package_id' => $monthly?->id,
                'subscription_status' => 'active',
                'subscribed_at' => now(),
                'subscription_expires_at' => Carbon::now()->addDays(30),
                'is_paid' => true,
            ],
        );
    }
}

<?php

namespace Tests\Feature;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackagePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_packages_api_exposes_admin_controlled_icon_visibility(): void
    {
        Package::create([
            'name' => 'With icon',
            'price' => 100,
            'duration_days' => 30,
            'show_icon' => true,
            'is_active' => true,
        ]);
        Package::create([
            'name' => 'Without icon',
            'price' => 200,
            'duration_days' => 30,
            'show_icon' => false,
            'is_active' => true,
        ]);

        $this->getJson('/api/packages')
            ->assertOk()
            ->assertJsonPath('data.0.show_icon', true)
            ->assertJsonPath('data.1.show_icon', false);
    }
}

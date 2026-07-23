<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name_phone_and_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $png = 'data:image/png;base64,'.base64_encode(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );

        $response = $this->putJson('/api/profile', [
            'name' => 'Updated User',
            'phone' => '01700000000',
            'avatar' => $png,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated User')
            ->assertJsonPath('data.phone', '01700000000');

        $user->refresh();
        $this->assertSame('Updated User', $user->name);
        $this->assertSame('01700000000', $user->phone);
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_user_must_supply_the_current_password_when_changing_it(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);
        Sanctum::actingAs($user);

        $this->putJson('/api/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->putJson('/api/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_google_only_user_can_set_a_password_without_a_current_password(): void
    {
        $user = User::factory()->create([
            'provider' => 'google',
            'password' => null,
        ]);
        Sanctum::actingAs($user);

        $this->putJson('/api/password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginIdentifierTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create([
            'email' => 'karim@example.com',
            'phone' => '8801812340001',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
    }

    public function test_logs_in_with_email(): void
    {
        $this->user();

        $this->postJson('/api/login', ['email' => 'karim@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'karim@example.com');
    }

    /** The number can be typed however the user remembers it. */
    public function test_logs_in_with_phone_in_any_shape(): void
    {
        $this->user();

        foreach (['8801812340001', '01812340001', '+8801812340001', '01812-340001'] as $identifier) {
            $this->postJson('/api/login', ['email' => $identifier, 'password' => 'secret123'])
                ->assertOk()
                ->assertJsonPath('data.user.phone', '8801812340001');
        }
    }

    public function test_wrong_password_is_rejected_for_both_identifiers(): void
    {
        $this->user();

        $this->postJson('/api/login', ['email' => 'karim@example.com', 'password' => 'nope'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/login', ['email' => '01812340001', 'password' => 'nope'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_unknown_identifier_is_rejected(): void
    {
        $this->user();

        $this->postJson('/api/login', ['email' => '01999999999', 'password' => 'secret123'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'secret123'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    /** A Google-only account has no password — it must not log in with one. */
    public function test_passwordless_account_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'google@example.com',
            'phone' => '8801812340002',
            'password' => null,
            'provider' => 'google',
        ]);

        $this->postJson('/api/login', ['email' => '01812340002', 'password' => 'anything'])
            ->assertUnprocessable();
    }
}

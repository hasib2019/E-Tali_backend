<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'reset-me@example.com',
            'phone' => '8801812349001',
            'password' => Hash::make('old-password'),
            'is_active' => true,
        ], $attributes));
    }

    public function test_emails_a_reset_code(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->postJson('/api/password/forgot', [
            'identifier' => 'reset-me@example.com',
            'channel' => 'email',
        ])->assertOk();

        Notification::assertSentTo($user, PasswordResetCodeNotification::class);
        $this->assertSame(1, $user->passwordResetCodes()->count());
    }

    public function test_texts_a_reset_code_when_identified_by_phone(): void
    {
        Http::fake(['sms.qolek.com/*' => Http::response(['status' => 'success'], 200)]);
        config(['services.qolek.api_key' => 'test-key', 'services.qolek.api_secret' => 'test-secret']);
        $this->user();

        // Local spelling of the same number must resolve to the account.
        $this->postJson('/api/password/forgot', [
            'identifier' => '01812349001',
            'channel' => 'phone',
        ])->assertOk();

        Http::assertSent(fn ($request) => $request['recipient'] === '8801812349001');
    }

    public function test_unknown_account_looks_identical_and_sends_nothing(): void
    {
        Notification::fake();

        $this->postJson('/api/password/forgot', [
            'identifier' => 'nobody@example.com',
            'channel' => 'email',
        ])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_code_resets_the_password_and_revokes_existing_tokens(): void
    {
        Notification::fake();
        $user = $this->user();
        $user->createToken('mobile');
        $this->assertSame(1, $user->tokens()->count());

        $this->postJson('/api/password/forgot', [
            'identifier' => 'reset-me@example.com',
            'channel' => 'email',
        ])->assertOk();

        $code = null;
        Notification::assertSentTo($user, PasswordResetCodeNotification::class, function ($notification) use (&$code, $user) {
            preg_match('/\b(\d{6})\b/', $notification->toMail($user)->render()->toHtml(), $m);
            $code = $m[1] ?? null;

            return true;
        });
        $this->assertNotNull($code);

        $this->postJson('/api/password/reset', [
            'identifier' => 'reset-me@example.com',
            'code' => $code,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertSame(0, $user->tokens()->count());

        // The code is single-use.
        $this->postJson('/api/password/reset', [
            'identifier' => 'reset-me@example.com',
            'code' => $code,
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ])->assertUnprocessable();
    }

    public function test_a_wrong_code_is_rejected_and_leaves_the_password_alone(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->postJson('/api/password/forgot', [
            'identifier' => 'reset-me@example.com',
            'channel' => 'email',
        ])->assertOk();

        $this->postJson('/api/password/reset', [
            'identifier' => 'reset-me@example.com',
            'code' => '000000',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_an_expired_code_is_rejected(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->postJson('/api/password/forgot', [
            'identifier' => 'reset-me@example.com',
            'channel' => 'email',
        ])->assertOk();

        $code = null;
        Notification::assertSentTo($user, PasswordResetCodeNotification::class, function ($notification) use (&$code, $user) {
            preg_match('/\b(\d{6})\b/', $notification->toMail($user)->render()->toHtml(), $m);
            $code = $m[1] ?? null;

            return true;
        });

        $user->passwordResetCodes()->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/password/reset', [
            'identifier' => 'reset-me@example.com',
            'code' => $code,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnprocessable();

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}

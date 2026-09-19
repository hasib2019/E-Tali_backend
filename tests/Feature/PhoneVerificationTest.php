<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_requires_a_phone_number_and_verification_method(): void
    {
        $this->postJson('/api/register', [
            'name' => 'No Phone',
            'email' => 'no-phone@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'verification_method']);
    }

    public function test_register_rejects_a_malformed_phone_number(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Bad Phone',
            'email' => 'bad-phone@example.com',
            'phone' => '12345',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'verification_method' => 'email',
        ])->assertUnprocessable()->assertJsonValidationErrors(['phone']);
    }

    public function test_register_normalizes_the_phone_before_checking_uniqueness(): void
    {
        User::factory()->create(['phone' => '8801812345678']);

        // Same number, written with a leading + and country code — must be
        // recognized as a duplicate instead of crashing on the DB constraint.
        $this->postJson('/api/register', [
            'name' => 'Dup',
            'email' => 'dup@example.com',
            'phone' => '+8801812345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'verification_method' => 'email',
        ])->assertUnprocessable()->assertJsonValidationErrors(['phone']);
    }

    public function test_registering_with_email_method_sends_email_verification_and_locks_the_account(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Email Method',
            'email' => 'email-method@example.com',
            'phone' => '01812345001',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'verification_method' => 'email',
        ])->assertCreated();

        $response->assertJsonPath('data.user.verification_method', 'email');
        $response->assertJsonPath('data.user.phone', '8801812345001');
        $response->assertJsonPath('data.user.email_verified', false);

        $token = $response->json('data.token');
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/businesses')
            ->assertForbidden()
            ->assertJsonPath('code', 'EMAIL_UNVERIFIED');
    }

    public function test_registering_with_phone_method_sends_an_otp_and_locks_the_account(): void
    {
        Http::fake(['sms.qolek.com/*' => Http::response([
            'status' => 'success',
            'data' => ['message_id' => 'GW_TEST_MSG_1'],
        ], 200)]);
        config(['services.qolek.api_key' => 'test-key', 'services.qolek.api_secret' => 'test-secret']);

        $response = $this->postJson('/api/register', [
            'name' => 'Phone Method',
            'email' => 'phone-method@example.com',
            'phone' => '01812345002',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'verification_method' => 'phone',
        ])->assertCreated();

        $response->assertJsonPath('data.user.verification_method', 'phone');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sms.qolek.com/api/v1/sms/send'
                && $request['recipient'] === '8801812345002'
                && $request->hasHeader('X-API-KEY', 'test-key')
                && $request->hasHeader('X-API-SECRET', 'test-secret');
        });

        $user = User::where('email', 'phone-method@example.com')->first();
        $this->assertSame('GW_TEST_MSG_1', $user->phoneOtps()->latest('id')->first()->message_id);

        $token = $response->json('data.token');
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/businesses')
            ->assertForbidden()
            ->assertJsonPath('code', 'PHONE_UNVERIFIED');
    }

    public function test_phone_otp_send_then_verify_unlocks_the_account(): void
    {
        Package::create([
            'name' => 'Free Trial',
            'price' => 0,
            'duration_days' => 14,
            'is_active' => true,
        ]);

        $capturedCode = null;
        Http::fake(function ($request) use (&$capturedCode) {
            preg_match('/code is (\d{6})/', $request['message'] ?? '', $m);
            $capturedCode = $m[1] ?? null;

            return Http::response(['status' => 'success'], 200);
        });
        config(['services.qolek.api_key' => 'test-key', 'services.qolek.api_secret' => 'test-secret']);

        $user = User::factory()->create([
            'phone' => '8801812345003',
            'verification_method' => 'phone',
            'email_verified_at' => null,
            // Sanctum::actingAs() pins this exact in-memory object as the
            // authenticated user for every request below — it never reloads
            // from the DB, so a column default (is_active) set only at the
            // DB level would otherwise read as null/false here.
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/phone/send-otp')->assertOk();
        $this->assertNotNull($capturedCode);

        $this->postJson('/api/phone/verify-otp', ['code' => '000000'])
            ->assertUnprocessable();

        $this->postJson('/api/phone/verify-otp', ['code' => $capturedCode])
            ->assertOk()
            ->assertJsonPath('data.phone_verified', true);

        $this->assertNotNull($user->fresh()->phone_verified_at);

        $this->getJson('/api/businesses')->assertOk();
    }

    public function test_changing_the_phone_resets_verification_and_rejects_a_taken_number(): void
    {
        $other = User::factory()->create(['phone' => '8801812345005']);
        $user = User::factory()->create([
            'phone' => '8801812345004',
            'phone_verified_at' => now(),
        ]);
        Sanctum::actingAs($user);

        $this->putJson('/api/profile', ['phone' => $other->phone])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);

        $this->putJson('/api/profile', ['phone' => '01812345006'])
            ->assertOk()
            ->assertJsonPath('data.phone', '8801812345006')
            ->assertJsonPath('data.phone_verified', false);

        $this->assertNull($user->fresh()->phone_verified_at);
    }
}

<?php

namespace Tests\Feature;

use App\Models\PhoneOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_a_wrong_token(): void
    {
        config(['services.qolek.webhook_secret' => 'right-secret']);

        $this->postJson('/api/webhooks/sms/qolek/wrong-secret', ['message_id' => 'X', 'status' => 'delivered'])
            ->assertForbidden();
    }

    public function test_rejects_when_no_secret_is_configured(): void
    {
        config(['services.qolek.webhook_secret' => null]);

        $this->postJson('/api/webhooks/sms/qolek/anything', ['message_id' => 'X', 'status' => 'delivered'])
            ->assertForbidden();
    }

    public function test_records_delivery_status_against_the_matching_otp(): void
    {
        config(['services.qolek.webhook_secret' => 'right-secret']);

        $user = User::factory()->create(['phone' => '8801888880002']);
        $otp = PhoneOtp::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => 'hashed-placeholder',
            'purpose' => 'phone_verification',
            'message_id' => 'GW_MSG_123',
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/webhooks/sms/qolek/right-secret', [
            'message_id' => 'GW_MSG_123',
            'status' => 'delivered',
        ])->assertOk();

        $otp->refresh();
        $this->assertSame('delivered', $otp->delivery_status);
        $this->assertNotNull($otp->delivered_at);
    }

    public function test_an_unknown_message_id_is_ignored_without_error(): void
    {
        config(['services.qolek.webhook_secret' => 'right-secret']);

        $this->postJson('/api/webhooks/sms/qolek/right-secret', [
            'message_id' => 'NO_SUCH_MESSAGE',
            'status' => 'delivered',
        ])->assertOk();
    }
}

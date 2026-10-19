<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Package;
use App\Models\SmsLog;
use App\Models\SmsSetting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Sms\SmsCreditService;
use App\Support\SmsSegments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SmsCreditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.qolek.api_key' => 'k',
            'services.qolek.api_secret' => 's',
            'services.qolek.base_url' => 'https://sms.test/api/v1',
        ]);
    }

    private function fakeGateway(bool $ok = true): void
    {
        Http::fake([
            'sms.test/*' => $ok
                ? Http::response(['status' => 'success', 'data' => ['message_id' => 'MSG1']])
                : Http::response(['status' => 'error'], 500),
        ]);
    }

    public function test_new_users_start_with_the_configured_free_credits(): void
    {
        SmsSetting::current()->update(['free_sms_per_user' => 7]);

        $this->assertSame(7, User::factory()->create()->fresh()->sms_balance);
    }

    public function test_segment_counting_matches_gateway_rules(): void
    {
        $this->assertSame(1, SmsSegments::count(str_repeat('a', 160)));
        $this->assertSame(2, SmsSegments::count(str_repeat('a', 161)));
        $this->assertSame(1, SmsSegments::count(str_repeat('ক', 70)));
        $this->assertSame(2, SmsSegments::count(str_repeat('ক', 71)));
        $this->assertSame(3, SmsSegments::count(str_repeat('ক', 135)));
    }

    public function test_send_spends_credits_and_logs(): void
    {
        $this->fakeGateway();
        $user = User::factory()->create(['sms_balance' => 5]);
        Sanctum::actingAs($user);

        $this->postJson('/api/sms/send', [
            'business_name' => 'Rahman Store',
            'messages' => [['phone' => '01812345678', 'message' => 'আপনার বাকি ৳৫০০', 'party_name' => 'Karim']],
        ])->assertOk()->assertJsonPath('data.sent', 1)->assertJsonPath('data.balance', 4);

        $this->assertSame(1, $user->fresh()->sms_sent_total);
        $this->assertDatabaseHas('sms_logs', ['user_id' => $user->id, 'phone' => '8801812345678', 'status' => 'sent', 'credits' => -1, 'message_id' => 'MSG1']);
    }

    public function test_send_is_refused_when_credits_run_out(): void
    {
        $this->fakeGateway();
        $user = User::factory()->create(['sms_balance' => 1]);
        Sanctum::actingAs($user);

        $this->postJson('/api/sms/send', ['messages' => [
            ['phone' => '01812345678', 'message' => 'one'],
            ['phone' => '01812345679', 'message' => 'two'],
        ]])->assertOk()
            ->assertJsonPath('data.sent', 1)
            ->assertJsonPath('data.results.1.error', 'insufficient_balance')
            ->assertJsonPath('data.balance', 0);
    }

    public function test_failed_gateway_send_refunds_the_credit(): void
    {
        $this->fakeGateway(false);
        $user = User::factory()->create(['sms_balance' => 3]);
        Sanctum::actingAs($user);

        $this->postJson('/api/sms/send', ['messages' => [['phone' => '01812345678', 'message' => 'hi']]])
            ->assertOk()->assertJsonPath('data.results.0.error', 'gateway_failed');

        $this->assertSame(3, $user->fresh()->sms_balance);
    }

    public function test_invalid_phone_and_disabled_switch_are_rejected(): void
    {
        $this->fakeGateway();
        $user = User::factory()->create(['sms_balance' => 3]);
        Sanctum::actingAs($user);

        $this->postJson('/api/sms/send', ['messages' => [['phone' => '12345', 'message' => 'hi']]])
            ->assertJsonPath('data.results.0.error', 'invalid_phone');

        SmsSetting::current()->update(['enabled' => false]);
        $this->postJson('/api/sms/send', ['messages' => [['phone' => '01812345678', 'message' => 'hi']]])
            ->assertJsonPath('data.results.0.error', 'disabled');

        $this->assertSame(3, $user->fresh()->sms_balance);
        Http::assertNothingSent();
    }

    public function test_package_assignment_adds_its_sms_quota(): void
    {
        $user = User::factory()->create(['sms_balance' => 5]);
        $package = Package::create(['name' => 'Premium', 'price' => 199, 'duration_days' => 30, 'sms_quota' => 100]);

        Subscription::create([
            'user_id' => $user->id, 'package_id' => $package->id,
            'starts_at' => now(), 'expires_at' => now()->addDays(30), 'amount' => 199, 'status' => 'active',
        ]);

        $this->assertSame(105, $user->fresh()->sms_balance);
        $this->assertDatabaseHas('sms_logs', ['user_id' => $user->id, 'type' => 'grant', 'credits' => 100]);
    }

    public function test_negative_grant_never_goes_below_zero(): void
    {
        $user = User::factory()->create(['sms_balance' => 3]);

        app(SmsCreditService::class)->grant($user, -10, 'correction');

        $this->assertSame(0, $user->fresh()->sms_balance);
        $this->assertSame(-3, SmsLog::where('user_id', $user->id)->value('credits'));
    }

    public function test_summary_and_me_expose_the_balance(): void
    {
        $user = User::factory()->create(['sms_balance' => 9]);
        Sanctum::actingAs($user);

        $this->getJson('/api/sms/summary')->assertOk()->assertJsonPath('data.balance', 9)->assertJsonPath('data.enabled', true);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.sms_balance', 9);
    }

    public function test_admin_sms_pages_render(): void
    {
        $admin = Admin::create(['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'secret123', 'role' => Admin::ROLE_SUPER_ADMIN, 'is_active' => true]);
        User::factory()->create(['sms_balance' => 4]);

        $this->actingAs($admin, 'admin')->get('/admin/manage-sms-settings')->assertOk()->assertSee('Free SMS for every new account');
        $this->actingAs($admin, 'admin')->get('/admin/sms-logs')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/users')->assertOk()->assertSee('SMS left');
        $this->actingAs($admin, 'admin')->get('/admin/packages/create')->assertOk()->assertSee('SMS quota');
    }
}

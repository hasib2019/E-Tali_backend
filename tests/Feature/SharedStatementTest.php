<?php

namespace Tests\Feature;

use App\Models\SharedStatement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SharedStatementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'business_name' => 'Rahman Store',
            'party_name' => 'Karim',
            'opening_balance' => 0,
            'closing_balance' => 480,
            'lang' => 'bn',
            'entries' => [
                ['date' => '2026-10-20', 'title' => 'বিক্রি INV-0001', 'gave' => 780, 'got' => 300, 'balance' => 480],
            ],
        ];
    }

    public function test_owner_creates_a_link_and_anyone_can_view_it(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $url = $this->postJson('/api/statements/share', $this->payload())
            ->assertCreated()
            ->json('data.url');

        $path = parse_url($url, PHP_URL_PATH);
        $this->app['auth']->forgetGuards();
        $this->get($path)->assertOk()->assertSee('Rahman Store')->assertSee('Karim')->assertSee('INV-0001', false);
        $this->assertSame(1, SharedStatement::first()->views);
    }

    public function test_expired_or_unknown_links_are_not_found(): void
    {
        $user = User::factory()->create();
        $s = SharedStatement::create([
            'user_id' => $user->id, 'token' => str_repeat('a', 32), 'business_name' => 'X', 'party_name' => 'Y',
            'payload' => ['entries' => [], 'generated_at' => now()->toIso8601String()], 'expires_at' => now()->subDay(),
        ]);

        $this->get('/s/'.$s->token)->assertNotFound();
        $this->get('/s/'.str_repeat('b', 32))->assertNotFound();
    }

    public function test_share_requires_auth_and_valid_entries(): void
    {
        $this->postJson('/api/statements/share', $this->payload())->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $bad = $this->payload();
        $bad['entries'][0]['date'] = 'yesterday';
        $this->postJson('/api/statements/share', $bad)->assertUnprocessable();
    }
}

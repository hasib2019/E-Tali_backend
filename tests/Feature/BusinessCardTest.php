<?php

namespace Tests\Feature;

use App\Models\BusinessCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessCardTest extends TestCase
{
    use RefreshDatabase;

    /** 1×1 transparent PNG. */
    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private function payload(array $over = []): array
    {
        return array_merge([
            'business_key' => '12',
            'template' => 'luxury',
            'lang' => 'bn',
            'name' => 'রহমান হোসেন',
            'designation' => 'স্বত্বাধিকারী',
            'business_name' => 'Rahman Store',
            'phone' => '01712345678',
            'email' => 'rahman@example.com',
            'address' => 'মিরপুর ১০, ঢাকা',
            'image' => self::PNG,
            'card_image' => self::PNG,
        ], $over);
    }

    public function test_sharing_again_updates_the_same_link(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $first = $this->postJson('/api/business-cards', $this->payload())->assertCreated()->json('data.url');
        $second = $this->postJson('/api/business-cards', $this->payload(['name' => 'Karim', 'template' => 'desh']))
            ->assertOk()->json('data.url');

        $this->assertSame($first, $second);
        $this->assertSame(1, BusinessCard::count());
        $card = BusinessCard::first();
        $this->assertSame('Karim', $card->data['name']);
        $this->assertSame('desh', $card->template);
        Storage::disk('public')->assertExists($card->card_path);
        // Only the latest images are kept.
        $this->assertCount(2, Storage::disk('public')->allFiles('business-cards/'.$card->id));

        // A different business gets its own card.
        $this->postJson('/api/business-cards', $this->payload(['business_key' => '13']))->assertCreated();
        $this->assertSame(2, BusinessCard::count());
    }

    public function test_public_page_and_contact_file(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
        $url = $this->postJson('/api/business-cards', $this->payload())->json('data.url');
        $path = parse_url($url, PHP_URL_PATH);
        $this->app['auth']->forgetGuards();

        $this->get($path)->assertOk()->assertSee('রহমান হোসেন')->assertSee('Rahman Store')
            ->assertSee('og:image', false)->assertSee('wa.me/8801712345678', false);
        $this->assertSame(1, BusinessCard::first()->views);

        $vcf = $this->get($path.'/contact.vcf')->assertOk();
        $this->assertStringStartsWith('text/vcard', $vcf->headers->get('Content-Type'));
        $this->assertStringContainsString('FN:রহমান হোসেন', $vcf->getContent());
        $this->assertStringContainsString('TEL;TYPE=CELL:01712345678', $vcf->getContent());
    }

    public function test_unknown_token_is_not_found(): void
    {
        $this->get('/card/'.str_repeat('a', 32))->assertNotFound();
        $this->get('/card/'.str_repeat('a', 32).'/contact.vcf')->assertNotFound();
    }

    public function test_requires_auth_and_valid_input(): void
    {
        $this->postJson('/api/business-cards', $this->payload())->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/business-cards', $this->payload(['template' => 'nope']))->assertUnprocessable();
        $this->postJson('/api/business-cards', $this->payload(['image' => 'data:text/html;base64,PGgxPg==']))->assertUnprocessable();
        $this->postJson('/api/business-cards', $this->payload(['name' => '']))->assertUnprocessable();
    }
}

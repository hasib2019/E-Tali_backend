<?php

namespace Tests\Feature;

use App\Filament\Resources\LandingSections\Pages\EditLandingSection;
use App\Models\Admin;
use App\Models\LandingSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_defaults_to_bangla(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('আপনার সব হিসাব থাকুক')
            ->assertSee('ই-টালি-খাতা')
            ->assertSee('id="features"', false)
            ->assertSee('id="pricing"', false);
    }

    public function test_the_landing_page_can_be_viewed_in_english(): void
    {
        $this->get('/?lang=en')
            ->assertOk()
            ->assertSee('Keep every account')
            ->assertSee('Everything you need to manage your accounts')
            ->assertSee('Get the app')
            ->assertDontSee('landing.nav.home');
    }

    public function test_the_selected_landing_locale_is_remembered(): void
    {
        $this->get('/?lang=en')->assertOk();

        $this->get('/')
            ->assertOk()
            ->assertSee('Keep every account');
    }

    public function test_admin_section_changes_are_reflected_on_the_public_page(): void
    {
        $hero = LandingSection::query()->where('key', 'hero')->firstOrFail();
        $heroContent = $hero->content;
        data_set($heroContent, 'bn.title', 'নতুন শিরোনাম <span>এখানে</span>');
        $hero->update(['content' => $heroContent]);

        $ledger = LandingSection::query()->where('key', 'ledger')->firstOrFail();
        $ledgerContent = $ledger->content;
        data_set($ledgerContent, 'bn.points', ['CMS থেকে সম্পাদিত সুবিধা']);
        $ledger->update(['content' => $ledgerContent]);

        $global = LandingSection::query()->where('key', 'global')->firstOrFail();
        $globalContent = $global->content;
        data_set($globalContent, 'settings.android_url', 'https://example.com/download');
        data_set($globalContent, 'bn.nav.home', 'নতুন হোম');
        $global->update(['content' => $globalContent]);

        LandingSection::query()->where('key', 'features')->update(['is_active' => false]);

        $this->get('/?lang=bn')
            ->assertOk()
            ->assertSee('নতুন শিরোনাম', false)
            ->assertSee('CMS থেকে সম্পাদিত সুবিধা')
            ->assertSee('https://example.com/download', false)
            ->assertSee('নতুন হোম')
            ->assertDontSee('id="features"', false)
            ->assertDontSee('href="#features"', false);
    }

    public function test_admin_can_open_the_landing_section_manager(): void
    {
        // This single test renders all 13 edit pages in one PHP process.
        ini_set('memory_limit', '512M');

        $admin = Admin::query()->create([
            'name' => 'Site Admin',
            'email' => 'site-admin@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/landing-sections')
            ->assertOk()
            ->assertSee('Landing Sections');

        foreach (LandingSection::query()->orderBy('sort_order')->get() as $section) {
            $this->actingAs($admin, 'admin')
                ->get("/admin/landing-sections/{$section->getKey()}/edit")
                ->assertOk();
        }
    }

    public function test_admin_can_save_nested_section_content_from_the_filament_form(): void
    {
        // Livewire's testing renderer keeps both request snapshots in memory;
        // the real admin request does not pay this doubled test-only cost.
        ini_set('memory_limit', '512M');

        $admin = Admin::query()->create([
            'name' => 'Content Editor',
            'email' => 'content-editor@example.com',
            'password' => 'password',
        ]);
        $hero = LandingSection::query()->where('key', 'hero')->firstOrFail();

        $this->actingAs($admin, 'admin');

        Livewire::test(EditLandingSection::class, ['record' => $hero->getKey()])
            ->fillForm([
                'content.bn.eyebrow' => 'অ্যাডমিন থেকে নতুন ছোট শিরোনাম',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            'অ্যাডমিন থেকে নতুন ছোট শিরোনাম',
            data_get($hero->fresh()->content, 'bn.eyebrow'),
        );
    }
}

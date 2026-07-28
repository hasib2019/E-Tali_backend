<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('content')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        $bn = require lang_path('bn/landing.php');
        $en = require lang_path('en/landing.php');
        $landingConfig = config('landing');

        $value = static function (array $translations, string $key, mixed $fallback = null): mixed {
            return Arr::get($translations, str($key)->after('landing.')->value(), $fallback);
        };

        $featureItems = collect($landingConfig['features'])->map(fn (array $item): array => [
            'icon' => $item['icon'],
            'title_bn' => $value($bn, $item['title']),
            'title_en' => $value($en, $item['title']),
            'description_bn' => $value($bn, $item['description']),
            'description_en' => $value($en, $item['description']),
        ])->all();

        $categoryItems = collect($landingConfig['categories'])->map(fn (array $item): array => [
            'key' => $item['key'],
            'icon' => $item['icon'],
            'label_bn' => $value($bn, $item['label']),
            'label_en' => $value($en, $item['label']),
        ])->all();

        $faqItems = collect($landingConfig['faqs'])->map(fn (array $item): array => [
            'question_bn' => $value($bn, $item['question']),
            'question_en' => $value($en, $item['question']),
            'answer_bn' => $value($bn, $item['answer']),
            'answer_en' => $value($en, $item['answer']),
        ])->all();

        $pillarItems = collect(['record', 'understand', 'grow'])
            ->map(fn (string $key, int $index): array => [
                'icon' => ['receipt', 'chart', 'arrow'][$index],
                'title_bn' => Arr::get($bn, "pillars.items.{$key}.title"),
                'title_en' => Arr::get($en, "pillars.items.{$key}.title"),
                'description_bn' => Arr::get($bn, "pillars.items.{$key}.description"),
                'description_en' => Arr::get($en, "pillars.items.{$key}.description"),
            ])->all();

        $stepItems = collect($bn['steps']['items'])->map(
            fn (array $item, int $index): array => [
                'title_bn' => $item['title'],
                'title_en' => $en['steps']['items'][$index]['title'],
                'description_bn' => $item['description'],
                'description_en' => $en['steps']['items'][$index]['description'],
            ],
        )->all();

        $sections = [
            [
                'key' => 'global',
                'name' => 'Global settings & footer',
                'description' => 'Brand, SEO, support details, app links and footer content.',
                'sort_order' => 0,
                'content' => [
                    'bn' => [
                        'meta_title' => $bn['meta']['title'],
                        'meta_description' => $bn['meta']['description'],
                        'brand_tagline' => $bn['brand_tagline'],
                        'footer_description' => $bn['footer']['description'],
                        'nav' => $bn['nav'],
                        'footer' => Arr::except($bn['footer'], ['description']),
                    ],
                    'en' => [
                        'meta_title' => $en['meta']['title'],
                        'meta_description' => $en['meta']['description'],
                        'brand_tagline' => $en['brand_tagline'],
                        'footer_description' => $en['footer']['description'],
                        'nav' => $en['nav'],
                        'footer' => Arr::except($en['footer'], ['description']),
                    ],
                    'settings' => [
                        'support_phone' => $landingConfig['support_phone'],
                        'support_email' => $landingConfig['support_email'],
                        'company_address' => $landingConfig['company_address'],
                        'android_url' => $landingConfig['android_url'],
                        'web_app_url' => $landingConfig['web_app_url'],
                        'facebook_url' => $landingConfig['facebook_url'],
                        'youtube_url' => $landingConfig['youtube_url'],
                        'linkedin_url' => $landingConfig['linkedin_url'],
                    ],
                ],
            ],
            [
                'key' => 'hero',
                'name' => 'Hero',
                'description' => 'Top headline, CTA, trust points, dashboard labels and merchant image.',
                'sort_order' => 10,
                'content' => ['bn' => $bn['hero'], 'en' => $en['hero']],
            ],
            [
                'key' => 'stats',
                'name' => 'Statistics',
                'description' => 'Use live platform totals or publish four custom statistics.',
                'sort_order' => 20,
                'content' => [
                    'use_live' => true,
                    'items' => [
                        ['value' => '১৬', 'label_bn' => $bn['stats']['categories'], 'label_en' => $en['stats']['categories']],
                        ['value' => '৮+', 'label_bn' => $bn['stats']['core_tools'], 'label_en' => $en['stats']['core_tools']],
                        ['value' => '২', 'label_bn' => $bn['stats']['languages'], 'label_en' => $en['stats']['languages']],
                        ['value' => '24/7', 'label_bn' => $bn['stats']['access'], 'label_en' => $en['stats']['access']],
                    ],
                ],
            ],
            [
                'key' => 'pillars',
                'name' => 'Overview cards',
                'description' => 'Three introductory benefit cards below the statistics.',
                'sort_order' => 30,
                'content' => [
                    'bn' => Arr::except($bn['pillars'], ['items']),
                    'en' => Arr::except($en['pillars'], ['items']),
                    'items' => $pillarItems,
                ],
            ],
            [
                'key' => 'features',
                'name' => 'Features',
                'description' => 'Feature section heading and all feature cards.',
                'sort_order' => 40,
                'content' => [
                    'bn' => $bn['features_section'],
                    'en' => $en['features_section'],
                    'items' => $featureItems,
                ],
            ],
            [
                'key' => 'ledger',
                'name' => 'Ledger showcase',
                'description' => 'Dark customer and supplier ledger showcase.',
                'sort_order' => 50,
                'content' => [
                    'bn' => $bn['showcase']['ledger'],
                    'en' => $en['showcase']['ledger'],
                ],
            ],
            [
                'key' => 'categories',
                'name' => 'Khata categories',
                'description' => 'Audience section heading and supported khata types.',
                'sort_order' => 60,
                'content' => [
                    'bn' => $bn['categories_section'],
                    'en' => $en['categories_section'],
                    'items' => $categoryItems,
                ],
            ],
            [
                'key' => 'personal',
                'name' => 'Adaptive experience showcase',
                'description' => 'Personalized dashboard and workflow showcase.',
                'sort_order' => 70,
                'content' => [
                    'bn' => $bn['showcase']['personal'],
                    'en' => $en['showcase']['personal'],
                ],
            ],
            [
                'key' => 'steps',
                'name' => 'Getting started steps',
                'description' => 'Three-step onboarding section.',
                'sort_order' => 80,
                'content' => [
                    'bn' => Arr::except($bn['steps'], ['items']),
                    'en' => Arr::except($en['steps'], ['items']),
                    'items' => $stepItems,
                ],
            ],
            [
                'key' => 'backup',
                'name' => 'Backup showcase',
                'description' => 'Cloud backup, restore and security feature section.',
                'sort_order' => 90,
                'content' => [
                    'bn' => $bn['showcase']['backup'],
                    'en' => $en['showcase']['backup'],
                ],
            ],
            [
                'key' => 'pricing',
                'name' => 'Pricing section',
                'description' => 'Pricing section headings. Plan prices and limits come from Packages.',
                'sort_order' => 100,
                'content' => [
                    'bn' => Arr::only($bn['pricing'], ['eyebrow', 'title', 'description']),
                    'en' => Arr::only($en['pricing'], ['eyebrow', 'title', 'description']),
                ],
            ],
            [
                'key' => 'faq',
                'name' => 'Frequently asked questions',
                'description' => 'FAQ section heading and expandable question list.',
                'sort_order' => 110,
                'content' => [
                    'bn' => Arr::only($bn['faq'], ['eyebrow', 'title']),
                    'en' => Arr::only($en['faq'], ['eyebrow', 'title']),
                    'items' => $faqItems,
                ],
            ],
            [
                'key' => 'cta',
                'name' => 'Final download CTA',
                'description' => 'Large download call-to-action before the footer.',
                'sort_order' => 120,
                'content' => ['bn' => $bn['cta'], 'en' => $en['cta']],
            ],
        ];

        $now = now();

        DB::table('landing_sections')->insert(array_map(
            static fn (array $section): array => [
                ...$section,
                'content' => json_encode($section['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $sections,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_sections');
    }
};

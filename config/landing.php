<?php

use App\Support\CategoryRegistry;

return [
    'support_phone' => env('LANDING_SUPPORT_PHONE', '+880 1XXX-XXXXXX'),
    'support_email' => env('LANDING_SUPPORT_EMAIL', 'support@etalikhata.com'),
    'company_address' => env('LANDING_COMPANY_ADDRESS', 'Dhaka, Bangladesh'),
    'android_url' => env(
        'APP_PLAY_STORE_URL',
        'https://play.google.com/store/apps/details?id=com.talikhata.app',
    ),
    'web_app_url' => env('LANDING_WEB_APP_URL', config('app.frontend_url', 'talikhata://')),
    'facebook_url' => env('LANDING_FACEBOOK_URL'),
    'youtube_url' => env('LANDING_YOUTUBE_URL'),
    'linkedin_url' => env('LANDING_LINKEDIN_URL'),

    'features' => [
        ['icon' => 'users', 'title' => 'landing.features.ledger_title', 'description' => 'landing.features.ledger_description'],
        ['icon' => 'wallet', 'title' => 'landing.features.cashbook_title', 'description' => 'landing.features.cashbook_description'],
        ['icon' => 'receipt', 'title' => 'landing.features.voucher_title', 'description' => 'landing.features.voucher_description'],
        ['icon' => 'package', 'title' => 'landing.features.stock_title', 'description' => 'landing.features.stock_description'],
        ['icon' => 'chart', 'title' => 'landing.features.report_title', 'description' => 'landing.features.report_description'],
        ['icon' => 'bell', 'title' => 'landing.features.reminder_title', 'description' => 'landing.features.reminder_description'],
        ['icon' => 'cloud', 'title' => 'landing.features.backup_title', 'description' => 'landing.features.backup_description'],
        ['icon' => 'shield', 'title' => 'landing.features.security_title', 'description' => 'landing.features.security_description'],
    ],

    'categories' => array_map(
        static fn (string $category): array => [
            'key' => $category,
            'icon' => match ($category) {
                'business' => 'store',
                'salaried' => 'briefcase',
                'student', 'teacher', 'coaching' => 'education',
                'homemaker' => 'home',
                'expat' => 'plane',
                'rider' => 'bike',
                'landlord' => 'building',
                'samity', 'gym' => 'community',
                'mess' => 'meal',
                'freelancer' => 'laptop',
                'laborer' => 'tools',
                'isp' => 'wifi',
                'cable' => 'tv',
                default => 'book',
            },
            'label' => "landing.categories.{$category}",
        ],
        CategoryRegistry::CATEGORIES,
    ),

    'faqs' => [
        ['question' => 'landing.faq.items.free.question', 'answer' => 'landing.faq.items.free.answer'],
        ['question' => 'landing.faq.items.offline.question', 'answer' => 'landing.faq.items.offline.answer'],
        ['question' => 'landing.faq.items.backup.question', 'answer' => 'landing.faq.items.backup.answer'],
        ['question' => 'landing.faq.items.multiple.question', 'answer' => 'landing.faq.items.multiple.answer'],
        ['question' => 'landing.faq.items.language.question', 'answer' => 'landing.faq.items.language.answer'],
        ['question' => 'landing.faq.items.security.question', 'answer' => 'landing.faq.items.security.answer'],
    ],
];

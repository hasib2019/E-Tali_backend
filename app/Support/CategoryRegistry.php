<?php

namespace App\Support;

/**
 * The set of khata "categories" (lenses) the app supports. Each category
 * reuses the same ledger engine but is presented differently on the client.
 * The backend only needs the canonical list + a few server-side defaults;
 * all UI/terminology lives in the app's category config.
 */
class CategoryRegistry
{
    public const BUSINESS = 'business';
    public const SALARIED = 'salaried';
    public const STUDENT = 'student';
    public const TEACHER = 'teacher';

    public const DEFAULT = self::BUSINESS;

    /** @var list<string> */
    public const CATEGORIES = [
        self::BUSINESS,
        self::SALARIED,
        self::STUDENT,
        self::TEACHER,
    ];

    public static function isValid(?string $category): bool
    {
        return $category !== null && in_array($category, self::CATEGORIES, true);
    }

    /** Personal-finance categories share the single-balance model. */
    public static function isPersonal(?string $category): bool
    {
        return in_array($category, [self::SALARIED, self::STUDENT], true);
    }

    public static function normalize(?string $category): string
    {
        return self::isValid($category) ? $category : self::DEFAULT;
    }

    /**
     * Default cash-book categories seeded when a khata of this type is created.
     * Shape: ['in' => [[name, icon], …], 'out' => [[name, icon], …]].
     * Used from Phase 2 onward when cash_categories are seeded.
     *
     * @return array{in: list<array{name: string, icon: string}>, out: list<array{name: string, icon: string}>}
     */
    public static function defaultCashCategories(string $category): array
    {
        return match ($category) {
            self::SALARIED => [
                'in' => [
                    ['name' => 'Salary', 'icon' => 'cash'],
                    ['name' => 'Bonus', 'icon' => 'gift'],
                    ['name' => 'Other income', 'icon' => 'add-circle'],
                ],
                'out' => [
                    ['name' => 'Rent', 'icon' => 'home'],
                    ['name' => 'Food', 'icon' => 'fast-food'],
                    ['name' => 'Transport', 'icon' => 'bus'],
                    ['name' => 'Bills', 'icon' => 'receipt'],
                    ['name' => 'EMI', 'icon' => 'card'],
                    ['name' => 'Health', 'icon' => 'medkit'],
                    ['name' => 'Education', 'icon' => 'school'],
                    ['name' => 'Others', 'icon' => 'ellipsis-horizontal'],
                ],
            ],
            self::STUDENT => [
                'in' => [
                    ['name' => 'Allowance', 'icon' => 'wallet'],
                    ['name' => 'Scholarship', 'icon' => 'ribbon'],
                    ['name' => 'Other income', 'icon' => 'add-circle'],
                ],
                'out' => [
                    ['name' => 'Food', 'icon' => 'fast-food'],
                    ['name' => 'Transport', 'icon' => 'bus'],
                    ['name' => 'Books', 'icon' => 'book'],
                    ['name' => 'Mobile', 'icon' => 'phone-portrait'],
                    ['name' => 'Tuition fee', 'icon' => 'school'],
                    ['name' => 'Others', 'icon' => 'ellipsis-horizontal'],
                ],
            ],
            self::TEACHER => [
                'in' => [
                    ['name' => 'Tuition fee', 'icon' => 'school'],
                    ['name' => 'Other income', 'icon' => 'add-circle'],
                ],
                'out' => [
                    ['name' => 'Rent', 'icon' => 'home'],
                    ['name' => 'Materials', 'icon' => 'documents'],
                    ['name' => 'Utility', 'icon' => 'flash'],
                    ['name' => 'Others', 'icon' => 'ellipsis-horizontal'],
                ],
            ],
            default => ['in' => [], 'out' => []],
        };
    }
}

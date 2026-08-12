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
    public const HOMEMAKER = 'homemaker';
    public const EXPAT = 'expat';
    public const RIDER = 'rider';
    public const LANDLORD = 'landlord';
    public const SAMITY = 'samity';
    public const MESS = 'mess';

    public const DEFAULT = self::BUSINESS;

    /** @var list<string> */
    public const CATEGORIES = [
        self::BUSINESS,
        self::SALARIED,
        self::STUDENT,
        self::TEACHER,
        self::HOMEMAKER,
        self::EXPAT,
        self::RIDER,
        self::LANDLORD,
        self::SAMITY,
        self::MESS,
    ];

    public static function isValid(?string $category): bool
    {
        return $category !== null && in_array($category, self::CATEGORIES, true);
    }

    /** Personal-finance categories share the single-balance model. */
    public static function isPersonal(?string $category): bool
    {
        return in_array($category, [self::SALARIED, self::STUDENT, self::HOMEMAKER, self::EXPAT, self::RIDER], true);
    }

    /** Collection categories reuse the teacher fee-collection model. */
    public static function isCollection(?string $category): bool
    {
        return in_array($category, [self::TEACHER, self::LANDLORD, self::SAMITY], true);
    }

    /** Income category label used when adding salary/allowance/income. */
    public static function incomeLabel(string $category): string
    {
        return match ($category) {
            self::STUDENT => 'Allowance',
            self::HOMEMAKER, self::RIDER => 'Income',
            default => 'Salary',
        };
    }

    /** Cash category used when recording a collection (fee/rent/subscription). */
    public static function collectionLabel(string $category): string
    {
        return match ($category) {
            self::LANDLORD => 'House rent',
            self::SAMITY => 'Subscription',
            default => 'Tuition fee',
        };
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
            self::HOMEMAKER => [
                'in' => [
                    ['name' => 'Income', 'icon' => 'cash'],
                    ['name' => 'Other income', 'icon' => 'add-circle'],
                ],
                'out' => [
                    ['name' => 'Bazar', 'icon' => 'cart'],
                    ['name' => 'Rent', 'icon' => 'home'],
                    ['name' => 'Bills', 'icon' => 'receipt'],
                    ['name' => 'Education', 'icon' => 'school'],
                    ['name' => 'Health', 'icon' => 'medkit'],
                    ['name' => 'Transport', 'icon' => 'bus'],
                    ['name' => 'Others', 'icon' => 'ellipsis-horizontal'],
                ],
            ],
            self::EXPAT => [
                'in' => [
                    ['name' => 'Salary', 'icon' => 'cash'],
                    ['name' => 'Other income', 'icon' => 'add-circle'],
                ],
                'out' => [
                    ['name' => 'Family', 'icon' => 'people'],
                    ['name' => 'Rent', 'icon' => 'home'],
                    ['name' => 'Food', 'icon' => 'fast-food'],
                    ['name' => 'Health', 'icon' => 'medkit'],
                    ['name' => 'Others', 'icon' => 'ellipsis-horizontal'],
                ],
            ],
            self::RIDER => [
                'in' => [
                    ['name' => 'Income', 'icon' => 'cash'],
                    ['name' => 'Bonus', 'icon' => 'gift'],
                ],
                'out' => [
                    ['name' => 'Fuel', 'icon' => 'flash'],
                    ['name' => 'Maintenance', 'icon' => 'construct'],
                    ['name' => 'Installment', 'icon' => 'card'],
                    ['name' => 'Food', 'icon' => 'fast-food'],
                    ['name' => 'Others', 'icon' => 'ellipsis-horizontal'],
                ],
            ],
            self::LANDLORD => [
                'in' => [
                    ['name' => 'House rent', 'icon' => 'home'],
                    ['name' => 'Other income', 'icon' => 'add-circle'],
                ],
                'out' => [
                    ['name' => 'Repair', 'icon' => 'construct'],
                    ['name' => 'Utility', 'icon' => 'flash'],
                    ['name' => 'Tax', 'icon' => 'document-text'],
                    ['name' => 'Others', 'icon' => 'ellipsis-horizontal'],
                ],
            ],
            self::SAMITY => [
                'in' => [
                    ['name' => 'Subscription', 'icon' => 'people'],
                    ['name' => 'Donation', 'icon' => 'heart'],
                ],
                'out' => [
                    ['name' => 'Program', 'icon' => 'calendar'],
                    ['name' => 'Charity', 'icon' => 'gift'],
                    ['name' => 'Office', 'icon' => 'briefcase'],
                    ['name' => 'Others', 'icon' => 'ellipsis-horizontal'],
                ],
            ],
            default => ['in' => [], 'out' => []],
        };
    }
}

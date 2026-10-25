<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'business_key', 'token', 'template', 'data', 'image_path', 'card_path', 'views'])]
class BusinessCard extends Model
{
    /**
     * Template palettes for the public page, mirroring the app's
     * src/config/bizCardTemplates.ts (bg, ink, muted, accent). Unknown keys
     * fall back to 'minimal'.
     */
    public const PALETTES = [
        'minimal' => ['bg' => '#FFFFFF', 'ink' => '#1F1B16', 'muted' => '#6B625A', 'accent' => '#07784F'],
        'band' => ['bg' => '#FFFFFF', 'ink' => '#13263A', 'muted' => '#5B6B7A', 'accent' => '#0E5C8A'],
        'luxury' => ['bg' => '#141414', 'ink' => '#F5EFE2', 'muted' => '#BFB59F', 'accent' => '#C9A24B'],
        'block' => ['bg' => '#FFFFFF', 'ink' => '#1D1D1F', 'muted' => '#62626A', 'accent' => '#E4572E'],
        'border' => ['bg' => '#FBF5E8', 'ink' => '#4A1C1C', 'muted' => '#7A5A4A', 'accent' => '#8E1F2F'],
        'photo' => ['bg' => '#F6F8FB', 'ink' => '#162033', 'muted' => '#5D6B80', 'accent' => '#6D4AFF'],
        'logo' => ['bg' => '#FFFFFF', 'ink' => '#202124', 'muted' => '#5F6368', 'accent' => '#0F9D8A'],
        'gradient' => ['bg' => '#1E3A8A', 'ink' => '#FFFFFF', 'muted' => '#DCE6FF', 'accent' => '#22D3EE'],
        'split' => ['bg' => '#F4F1EC', 'ink' => '#13233D', 'muted' => '#5C6677', 'accent' => '#13233D'],
        'desh' => ['bg' => '#006A4E', 'ink' => '#FFFFFF', 'muted' => '#CDE8DE', 'accent' => '#F42A41'],
    ];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array{bg: string, ink: string, muted: string, accent: string} */
    public function palette(): array
    {
        return self::PALETTES[$this->template] ?? self::PALETTES['minimal'];
    }
}

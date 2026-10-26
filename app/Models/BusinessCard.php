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
        'rickshaw' => ['bg' => '#1B1035', 'ink' => '#FFD23F', 'muted' => '#F3E9FF', 'accent' => '#FF3D7F'],
        'nakshi' => ['bg' => '#F7EFDD', 'ink' => '#3B2416', 'muted' => '#6E5440', 'accent' => '#B23A2E'],
        'neon' => ['bg' => '#0A0A12', 'ink' => '#F5F7FF', 'muted' => '#9AA3C7', 'accent' => '#00F0FF'],
        'monogram' => ['bg' => '#FFFFFF', 'ink' => '#111827', 'muted' => '#6B7280', 'accent' => '#7C3AED'],
        'sash' => ['bg' => '#FFFFFF', 'ink' => '#1F2937', 'muted' => '#6B7280', 'accent' => '#F59E0B'],
        'zen' => ['bg' => '#FAFAF7', 'ink' => '#1C1C1C', 'muted' => '#7A7A72', 'accent' => '#C8102E'],
        'ticket' => ['bg' => '#FFFFFF', 'ink' => '#2B2D42', 'muted' => '#8D99AE', 'accent' => '#EF233C'],
        'khata' => ['bg' => '#FFFDF5', 'ink' => '#1F2A44', 'muted' => '#5B6478', 'accent' => '#D93A3A'],
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

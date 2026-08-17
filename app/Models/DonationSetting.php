<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['bkash_number', 'intro_title', 'intro_message'])]
class DonationSetting extends Model
{
    /** The single settings row — whichever one exists, or create the first one. */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'bkash_number' => null,
            'intro_title' => 'Support us!',
            'intro_message' => "Tali Khata is honor-ware — we run on your trust and honesty. We fully depend on your contribution to keep adding new features. Please donate as much as you can.",
        ]);
    }
}

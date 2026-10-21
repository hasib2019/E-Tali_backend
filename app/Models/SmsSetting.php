<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['enabled', 'free_sms_per_user'])]
class SmsSetting extends Model
{
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'free_sms_per_user' => 'integer',
        ];
    }

    /** The single settings row — whichever one exists, or create the first one. */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'enabled' => true,
            'free_sms_per_user' => 5,
        ]);
    }
}

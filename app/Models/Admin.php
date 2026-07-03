<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class Admin extends Authenticatable implements FilamentUser
{
    use Notifiable;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Any admin in this table may access the Filament back-office panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}

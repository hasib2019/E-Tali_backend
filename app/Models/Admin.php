<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class Admin extends Authenticatable implements FilamentUser
{
    use Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    protected $attributes = [
        'role' => self::ROLE_ADMIN,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::updating(function (Admin $admin): void {
            $wasActiveSuperAdmin = $admin->getOriginal('role') === self::ROLE_SUPER_ADMIN
                && (bool) $admin->getOriginal('is_active');
            $willRemainActiveSuperAdmin = $admin->role === self::ROLE_SUPER_ADMIN
                && $admin->is_active;

            if ($wasActiveSuperAdmin && ! $willRemainActiveSuperAdmin && $admin->isLastActiveSuperAdmin()) {
                throw ValidationException::withMessages([
                    'role' => 'At least one active Super Admin must remain.',
                    'is_active' => 'At least one active Super Admin must remain.',
                ]);
            }
        });

        static::deleting(function (Admin $admin): void {
            if ($admin->isSuperAdmin() && $admin->is_active && $admin->isLastActiveSuperAdmin()) {
                throw ValidationException::withMessages([
                    'admin' => 'The last active Super Admin cannot be deleted.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active ?? true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isLastActiveSuperAdmin(): bool
    {
        return ! static::query()
            ->whereKeyNot($this->getKey())
            ->where('role', self::ROLE_SUPER_ADMIN)
            ->where('is_active', true)
            ->exists();
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Models\Admin;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_management_and_profile_pages(): void
    {
        $superAdmin = $this->makeAdmin(Admin::ROLE_SUPER_ADMIN);

        $this->actingAs($superAdmin, 'admin')
            ->get('/admin/admins')
            ->assertOk()
            ->assertSee('Admin Management');

        $this->actingAs($superAdmin, 'admin')
            ->get('/admin/profile')
            ->assertOk();
    }

    public function test_regular_admin_cannot_access_admin_management(): void
    {
        $admin = $this->makeAdmin(Admin::ROLE_ADMIN);

        $this->actingAs($admin, 'admin')
            ->get('/admin/admins')
            ->assertForbidden();
    }

    public function test_super_admin_can_create_an_admin_from_the_filament_form(): void
    {
        ini_set('memory_limit', '512M');

        $superAdmin = $this->makeAdmin(Admin::ROLE_SUPER_ADMIN);
        $this->actingAs($superAdmin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateAdmin::class)
            ->fillForm([
                'name' => 'Operations Admin',
                'email' => 'operations@example.com',
                'role' => Admin::ROLE_ADMIN,
                'is_active' => true,
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('admins', [
            'email' => 'operations@example.com',
            'role' => Admin::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_last_active_super_admin_cannot_be_demoted_deactivated_or_deleted(): void
    {
        $superAdmin = $this->makeAdmin(Admin::ROLE_SUPER_ADMIN);

        foreach ([
            ['role' => Admin::ROLE_ADMIN],
            ['is_active' => false],
        ] as $change) {
            try {
                $superAdmin->fresh()->update($change);
                $this->fail('Expected the last Super Admin protection to reject the update.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        $this->expectException(ValidationException::class);
        $superAdmin->fresh()->delete();
    }

    public function test_inactive_admin_cannot_access_the_panel(): void
    {
        $admin = $this->makeAdmin(Admin::ROLE_ADMIN, false);

        $this->assertFalse($admin->canAccessPanel(Filament::getPanel('admin')));
    }

    private function makeAdmin(string $role, bool $isActive = true): Admin
    {
        return Admin::query()->create([
            'name' => 'Admin User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => $role,
            'is_active' => $isActive,
        ]);
    }
}

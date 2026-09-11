<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserPermissions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserPermissionsAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate(UserPermissions::UPDATE_USER_PERMISSIONS, 'web');
    }

    public function test_admin_without_special_permission_cannot_access_user_permissions_page(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);

        $this->actingAs($admin);

        $this->get(route('admin.user-permissions'))->assertForbidden();
        Livewire::test(UserPermissions::class)->assertForbidden();
    }

    public function test_admin_with_special_permission_can_access_user_permissions_page(): void
    {
        $admin = $this->adminWithUserPermissionsAccess();

        $this->actingAs($admin);

        $this->get(route('admin.user-permissions'))->assertOk();
        Livewire::test(UserPermissions::class)->assertOk();
    }

    public function test_superadmin_can_access_user_permissions_page_without_special_permission(): void
    {
        $superAdmin = User::factory()->create([
            'email' => User::superAdminEmails()[0],
            'user_type' => 'admin',
        ]);

        $this->actingAs($superAdmin);

        $this->assertTrue($superAdmin->canAccessUserPermissions());
        $this->get(route('admin.user-permissions'))->assertOk();
        Livewire::test(UserPermissions::class)->assertOk();
    }

    public function test_dashboard_user_permissions_button_is_hidden_without_special_permission(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);

        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.user-permissions'));
    }

    public function test_dashboard_user_permissions_button_is_visible_with_special_permission(): void
    {
        $admin = $this->adminWithUserPermissionsAccess();

        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.user-permissions'), false);
    }

    public function test_admin_can_grant_user_permissions_access_to_another_admin(): void
    {
        $admin = $this->adminWithUserPermissionsAccess();
        $otherAdmin = User::factory()->create([
            'name' => 'Other Admin',
            'user_type' => 'admin',
        ]);

        $this->actingAs($admin);

        Livewire::test(UserPermissions::class)
            ->set('selectedUserId', $otherAdmin->id)
            ->set('permissions.special.'.UserPermissions::UPDATE_USER_PERMISSIONS, true)
            ->call('save');

        $this->assertTrue($otherAdmin->fresh()->can(UserPermissions::UPDATE_USER_PERMISSIONS));
    }

    public function test_admin_can_revoke_user_permissions_access_from_another_admin(): void
    {
        $admin = $this->adminWithUserPermissionsAccess();
        $otherAdmin = User::factory()->create([
            'name' => 'Other Admin',
            'user_type' => 'admin',
        ]);
        $otherAdmin->givePermissionTo(UserPermissions::UPDATE_USER_PERMISSIONS);

        $this->actingAs($admin);

        Livewire::test(UserPermissions::class)
            ->set('selectedUserId', $otherAdmin->id)
            ->set('permissions.special.'.UserPermissions::UPDATE_USER_PERMISSIONS, false)
            ->call('save');

        $this->assertFalse($otherAdmin->fresh()->can(UserPermissions::UPDATE_USER_PERMISSIONS));
    }

    public function test_admin_cannot_remove_own_user_permissions_access(): void
    {
        $admin = $this->adminWithUserPermissionsAccess();

        $this->actingAs($admin);

        Livewire::test(UserPermissions::class, ['selectedUserId' => $admin->id])
            ->set('permissions.special.'.UserPermissions::UPDATE_USER_PERMISSIONS, false)
            ->call('save');

        $this->assertTrue($admin->fresh()->can(UserPermissions::UPDATE_USER_PERMISSIONS));
    }

    public function test_own_account_locks_user_permissions_special_permission_checkbox(): void
    {
        $admin = $this->adminWithUserPermissionsAccess();
        $otherAdmin = User::factory()->create([
            'name' => 'Other Admin',
            'user_type' => 'admin',
        ]);

        $this->actingAs($admin);

        Livewire::test(UserPermissions::class, ['selectedUserId' => $admin->id])
            ->assertSee(__('You cannot change this permission on your own account.'));

        Livewire::test(UserPermissions::class)
            ->set('selectedUserId', $otherAdmin->id)
            ->assertDontSee(__('You cannot change this permission on your own account.'));
    }

    private function adminWithUserPermissionsAccess(): User
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->givePermissionTo(UserPermissions::UPDATE_USER_PERMISSIONS);

        return $admin;
    }
}

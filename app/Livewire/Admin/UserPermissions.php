<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class UserPermissions extends Component
{
    public const UPDATE_USER_PERMISSIONS = 'update_user_permissions';

    #[Url(as: 'selected')]
    public ?int $selectedUserId = null;

    /**
     * Permission matrix keyed by model then action.
     *
     * @var array<string, array<string, bool>>
     */
    public array $permissions = [];

    /**
     * List of logical models to show in the matrix.
     *
     * @var array<int, string>
     */
    public array $models = [
        'location',
        'event',
        'member',
        'merchant',
        'marketplace',
        'voucher',
        'admin-voucher',
        'point-system',
        'news',
        'news-category',
        'announcement',
        'settings',
    ];

    /**
     * Actions per model.
     *
     * @var array<int, string>
     */
    public array $actions = [
        'view',
        'profile',
        'create',
        'edit',
        'delete',
    ];

    public $specialPermissions = [
        self::UPDATE_USER_PERMISSIONS => 'Can access and update user permissions of Admin Users',
        'reset_password' => 'Can Reset Password of the Member',
        'can_update_email_address_of_member' => 'Can Update Email address of the Member',
        'can_update_mobile_number_of_member' => 'Can Update Mobile Number of the Member',
        'can_view_activities_of_member' => 'Can view the activities of the Member',
        'can_export_data' => 'Can export export data (CSV, Excel, PDF)',
        'can_add_user_to_merchant' => 'Can add USER to the merchant',
        'can_create_new_activity_type' => 'Can create new activity type',
        'can_enable_disable_point_system' => 'Can enable/disable point system',
        'can_manage_lucky_draw' => 'Can manage lucky draw',
        'can_void_member_activity' => 'Can void member activity',
        'can_add_activity_manually' => 'Can add activity manually',
        'can_update_work_type' => 'Can update work type of the member',
        'can_update_user_type' => 'Can update user type of the user',
        'can_backup_database' => 'Can create database backup files',
        'can_access_marketplace_cashier' => 'Can access Marketplace Cashier Page',
    ];

    public ?string $saveSuccessMessage = null;

    public int $saveSuccessBannerKey = 0;

    public function mount(): void
    {
        $this->authorizeAccess();

        if ($this->selectedUserId) {
            $isValidAdmin = User::query()
                ->whereKey($this->selectedUserId)
                ->where('user_type', 'admin')
                ->exists();

            if ($isValidAdmin) {
                $this->loadPermissions();

                return;
            }

            $this->selectedUserId = null;
        }

        $firstAdmin = User::query()
            ->where('user_type', 'admin')
            ->orderBy('name')
            ->first();

        if ($firstAdmin) {
            $this->selectedUserId = $firstAdmin->id;
            $this->loadPermissions();
        }
    }

    public function updatedSelectedUserId(): void
    {
        $this->authorizeAccess();
        $this->saveSuccessMessage = null;
        $this->loadPermissions();
    }

    public function isEditingSelf(): bool
    {
        return $this->selectedUserId !== null
            && (int) $this->selectedUserId === (int) auth()->id();
    }

    public function isLockedSpecialPermission(string $permission): bool
    {
        return $permission === self::UPDATE_USER_PERMISSIONS && $this->isEditingSelf();
    }

    /**
     * True when every persisted action is enabled for the model (UI "select all" column).
     */
    public function modelRowFullyGranted(string $model): bool
    {
        if (! in_array($model, $this->models, true)) {
            return false;
        }

        foreach ($this->actions as $action) {
            if (empty($this->permissions[$model][$action])) {
                return false;
            }
        }

        return true;
    }

    /**
     * UI-only: check all row actions, or uncheck all if already fully selected.
     */
    public function toggleRowAll(string $model): void
    {
        $this->authorizeAccess();

        if (! in_array($model, $this->models, true)) {
            return;
        }

        $new = ! $this->modelRowFullyGranted($model);

        foreach ($this->actions as $action) {
            $this->permissions[$model][$action] = $new;
        }
    }

    protected function loadPermissions(): void
    {
        $this->permissions = [];

        if (! $this->selectedUserId) {
            return;
        }

        $user = User::find($this->selectedUserId);

        if (! $user) {
            return;
        }

        $userPermissionNames = $user->getPermissionNames()->toArray();

        foreach ($this->models as $model) {
            foreach ($this->actions as $action) {
                $name = "{$model}.{$action}";
                $this->permissions[$model][$action] = in_array($name, $userPermissionNames, true);
            }
        }

        foreach (array_keys($this->specialPermissions) as $permissionName) {
            $this->permissions['special'][$permissionName] = in_array($permissionName, $userPermissionNames, true);
        }
    }

    public function save(): void
    {
        $this->authorizeAccess();

        if (! $this->selectedUserId) {
            return;
        }

        $user = User::findOrFail($this->selectedUserId);

        $selectedPermissionNames = [];

        foreach ($this->models as $model) {
            foreach ($this->actions as $action) {
                $name = "{$model}.{$action}";

                if (! empty($this->permissions[$model][$action])) {
                    $selectedPermissionNames[] = $name;
                }
            }
        }

        foreach (array_keys($this->specialPermissions) as $permissionName) {
            if ($this->isLockedSpecialPermission($permissionName)) {
                continue;
            }

            if (! empty($this->permissions['special'][$permissionName])) {
                $selectedPermissionNames[] = $permissionName;
            }
        }

        if ($this->isEditingSelf() && $user->getPermissionNames()->contains(self::UPDATE_USER_PERMISSIONS)) {
            $selectedPermissionNames[] = self::UPDATE_USER_PERMISSIONS;
        }

        $selectedPermissionNames = array_values(array_unique($selectedPermissionNames));

        // Ensure permissions exist in the database
        foreach ($selectedPermissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $user->syncPermissions($selectedPermissionNames);

        $user->unsetRelation('permissions');
        $user->forgetCachedPermissions();

        $this->saveSuccessMessage = __('Permissions updated.');
        $this->saveSuccessBannerKey++;

        $this->js('window.scrollTo({ top: 0, left: 0, behavior: "smooth" })');
    }

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->canAccessUserPermissions(), 403, 'Unauthorized.');
    }

    public function render(): View
    {
        $adminUsers = User::query()
            ->where('user_type', 'admin')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.user-permissions', [
            'adminUsers' => $adminUsers,
        ])->layout('layouts.app');
    }
}

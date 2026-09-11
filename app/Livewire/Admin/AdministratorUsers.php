<?php

namespace App\Livewire\Admin;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class AdministratorUsers extends Component
{
    use PasswordValidationRules;
    use WithPagination;

    public string $search = '';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['as' => 'keyword', 'except' => '', 'history' => true],
    ];

    /**
     * Only these superadmin emails should be able to access this page.
     *
     * Keeping this here (instead of only Blade visibility) ensures the route
     * is protected even if someone manually visits it.
     *
     * @var array<int, string>
     */
    private array $allowedEmails = [
        '+6584533959@hopevillage-user.sg',
        'marnelle24@gmail.com',
        'marnelle.apat@biblesociety.sg',
        'karl.godinez@biblesociety.sg',
    ];

    // Password reset properties
    public ?int $selectedUserId = null;

    public string $password = '';

    public string $password_confirmation = '';

    public bool $showPasswordReset = false;

    public function mount(): void
    {
        $this->authorizeSuperadmin();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $adminUsers = $this->buildAdminUsersQuery()
            ->orderBy('name')
            ->paginate(8);

        $selectedUser = null;
        if ($this->showPasswordReset && $this->selectedUserId) {
            $selectedUser = User::query()
                ->whereKey($this->selectedUserId)
                ->where('user_type', 'admin')
                ->first();
        }

        return view('livewire.admin.administrator-users', [
            'adminUsers' => $adminUsers,
            'selectedUser' => $selectedUser,
        ])->layout('layouts.app');
    }

    /**
     * @return Builder<User>
     */
    private function buildAdminUsersQuery(): Builder
    {
        $query = User::query()->where('user_type', 'admin');

        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('email', 'like', $s)
                    ->orWhere('whatsapp_number', 'like', $s);
            });
        }

        return $query;
    }

    public function updatedShowPasswordReset(bool $show): void
    {
        if (! $show) {
            $this->clearPasswordResetState();
        }
    }

    public function openResetPasswordModal(int $userId): void
    {
        $this->authorizeSuperadmin();

        $userExists = User::query()
            ->whereKey($userId)
            ->where('user_type', 'admin')
            ->exists();

        abort_unless($userExists, 404);

        $this->resetValidation();
        $this->selectedUserId = $userId;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showPasswordReset = true;
    }

    public function cancelPasswordReset(): void
    {
        $this->authorizeSuperadmin();

        $this->showPasswordReset = false;
        $this->clearPasswordResetState();
    }

    public function resetPassword(): void
    {
        $this->authorizeSuperadmin();

        if (! $this->selectedUserId) {
            return;
        }

        Validator::make([
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ], [
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::query()
            ->whereKey($this->selectedUserId)
            ->where('user_type', 'admin')
            ->firstOrFail();

        $user->forceFill([
            'password' => Hash::make($this->password),
        ])->save();

        Log::info('Admin user password reset by superadmin', [
            'admin_user_id' => $user->id,
            'admin_user_email' => $user->email,
            'reset_at' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->showPasswordReset = false;
        $this->clearPasswordResetState();

        session()->flash('message', 'Password reset successfully.');
    }

    public function removeAsAdmin(int $userId): void
    {
        $this->convertAdminUser(
            $userId,
            'member',
            'Administrator removed. The user is now an ordinary member.'
        );
    }

    public function convertToMerchantUser(int $userId): void
    {
        $this->convertAdminUser(
            $userId,
            'merchant_user',
            'Administrator converted to a merchant user.'
        );
    }

    private function convertAdminUser(int $userId, string $newType, string $successMessage): void
    {
        $this->authorizeSuperadmin();

        $allowedTypes = ['member', 'merchant_user'];
        if (! in_array($newType, $allowedTypes, true)) {
            return;
        }

        $user = User::query()
            ->whereKey($userId)
            ->where('user_type', 'admin')
            ->first();

        if (! $user) {
            session()->flash('error', 'Admin user not found.');

            return;
        }

        if ($user->id === auth()->id() || $user->isSuperAdmin()) {
            session()->flash('error', 'This administrator cannot be converted.');

            return;
        }

        $oldUserType = $user->user_type;
        $user->update(['user_type' => $newType]);
        $user->syncPermissions([]);

        Log::info('Admin user type changed by superadmin', [
            'admin_user_id' => auth()->id(),
            'admin_user_email' => auth()->user()?->email,
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'old_user_type' => $oldUserType,
            'new_user_type' => $newType,
            'changed_at' => now()->toIso8601String(),
        ]);

        session()->flash('message', $successMessage);
    }

    private function clearPasswordResetState(): void
    {
        $this->selectedUserId = null;
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetValidation();
    }

    private function authorizeSuperadmin(): void
    {
        $email = auth()->user()?->email;

        abort_unless(in_array($email, $this->allowedEmails, true), 403);
    }
}

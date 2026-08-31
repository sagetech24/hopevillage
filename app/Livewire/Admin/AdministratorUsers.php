<?php

namespace App\Livewire\Admin;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class AdministratorUsers extends Component
{
    use PasswordValidationRules;

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

    public function render(): View
    {
        $adminUsers = User::query()
            ->where('user_type', 'admin')
            ->orderBy('name')
            ->get();

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

    public function openResetPasswordModal(int $userId): void
    {
        $this->authorizeSuperadmin();

        $userExists = User::query()
            ->whereKey($userId)
            ->where('user_type', 'admin')
            ->exists();

        abort_unless($userExists, 404);

        $this->selectedUserId = $userId;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showPasswordReset = true;
    }

    public function cancelPasswordReset(): void
    {
        $this->authorizeSuperadmin();

        $this->selectedUserId = null;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showPasswordReset = false;
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

        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedUserId = null;
        $this->showPasswordReset = false;

        session()->flash('message', 'Password reset successfully.');
    }

    private function authorizeSuperadmin(): void
    {
        $email = auth()->user()?->email;

        abort_unless(in_array($email, $this->allowedEmails, true), 403);
    }
}

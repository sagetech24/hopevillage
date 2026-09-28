<?php

namespace App\Livewire\Merchant;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\On;
use Livewire\Component;

class ProfileModal extends Component
{
    public bool $open = false;

    public string $email = '';

    public string $email_current_password = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $emailStatus = null;

    public ?string $passwordStatus = null;

    #[On('open-merchant-profile')]
    public function open(): void
    {
        $user = $this->merchantUser();

        $this->resetErrorBag();
        $this->reset([
            'email_current_password',
            'current_password',
            'password',
            'password_confirmation',
            'emailStatus',
            'passwordStatus',
        ]);
        $this->email = (string) $user->email;
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
        $this->resetErrorBag();
        $this->reset([
            'email_current_password',
            'current_password',
            'password',
            'password_confirmation',
            'emailStatus',
            'passwordStatus',
        ]);
    }

    public function updateEmail(): void
    {
        $user = $this->merchantUser();

        $rules = [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];

        if ($this->email !== $user->email) {
            $rules['email_current_password'] = ['required', 'string', 'current_password'];
        }

        $this->validate($rules);

        if ($this->email !== $user->email) {
            $user->forceFill([
                'email' => $this->email,
            ])->save();
            $this->emailStatus = 'Email address updated.';
        } else {
            $this->emailStatus = 'Email address is already up to date.';
        }

        $this->email_current_password = '';
    }

    public function updatePassword(): void
    {
        $user = $this->merchantUser();

        $password = Password::min(8)->numbers();

        if (! app()->runningUnitTests()) {
            $password = $password->uncompromised();
        }

        $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', $password, 'confirmed'],
        ]);

        $user->forceFill([
            'password' => $this->password,
        ])->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->passwordStatus = 'Password updated.';
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered. Please use a different email.',
            'email_current_password.required' => 'Enter your current password to update your email address.',
            'email_current_password.current_password' => 'The provided password does not match your current password.',
            'current_password.required' => 'Current password is required.',
            'current_password.current_password' => 'The provided password does not match your current password.',
            'password.required' => 'New password is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.numbers' => 'The password must contain at least one number.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.uncompromised' => 'The given password has appeared in a data leak. Please choose a different password.',
        ];
    }

    public function render()
    {
        $user = auth()->user();
        $storeName = null;
        $mobileNumber = null;
        $profileName = null;
        $profilePhotoUrl = null;

        if ($this->open && $user instanceof User) {
            $profileName = $user->name;
            $mobileNumber = $user->whatsapp_number;
            $profilePhotoUrl = $user->profile_photo_url;
            $storeName = $user->currentMerchant()?->name ?? 'No store assigned yet';
        }

        return view('livewire.merchant.profile-modal', [
            'profileName' => $profileName,
            'mobileNumber' => $mobileNumber,
            'profilePhotoUrl' => $profilePhotoUrl,
            'storeName' => $storeName,
        ]);
    }

    protected function merchantUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $user->isMerchantUser(), 403);

        return $user;
    }
}

<?php

namespace App\Livewire\Merchant;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Jetstream;
use Livewire\Component;
use Livewire\WithFileUploads;

class Apply extends Component
{
    use WithFileUploads;

    public $name = '';

    public $description = '';

    public $contact_name = '';

    public $phone = '';

    public $email = '';

    public $unitNumber = '';

    public $address = '7 Kaki Bukit Avenue 3';

    public $city = 'Singapore';

    public $province = '';

    public $postal_code = '415814';

    public $website = '';

    public $logo;

    public $password = '';

    public $password_confirmation = '';

    public $terms = false;

    public $showSuccess = false;

    public $isSubmitting = false;

    public $gRecaptchaResponse = '';

    protected function rules()
    {
        $password = Password::min(8)->numbers();

        if (! app()->runningUnitTests()) {
            $password = $password->uncompromised();
        }

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'max:12',
                'unique:users,whatsapp_number',
            ],
            'email' => 'nullable|email|max:255|unique:users,email',
            'address' => 'nullable|string|max:255',
            'unitNumber' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
            'password' => [
                'required',
                'string',
                $password,
                'confirmed',
            ],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted'] : ['nullable'],
            // 'gRecaptchaResponse' => config('services.recaptcha.secret_key') && ! app()->runningUnitTests()
            //     ? ['required']
            //     : ['nullable'],
        ];
    }

    protected $messages = [
        'name.required' => 'Merchant name is required.',
        'contact_name.required' => 'Contact person name is required.',
        'phone.required' => 'Phone number is required.',
        'phone.unique' => 'This mobile number is already registered. Please use a different number.',
        'phone.max' => 'The phone number is too long. Please enter a valid phone number.',
        'email.email' => 'Please provide a valid email address.',
        'email.unique' => 'This email address is already registered. Please use a different email.',
        'website.url' => 'Please provide a valid website URL (including https://).',
        'password.required' => 'Password is required.',
        'password.min' => 'The password must be at least 8 characters.',
        'password.mixed' => 'The password must contain at least one uppercase and one lowercase letter.',
        'password.numbers' => 'The password must contain at least one number.',
        'password.symbols' => 'The password must contain at least one special character.',
        'password.uncompromised' => 'The given password has appeared in a data leak. Please choose a different password.',
        'password.confirmed' => 'Password confirmation does not match.',
        'terms.accepted' => 'You must accept the privacy policy to continue.',
        'gRecaptchaResponse.required' => 'Please complete the reCAPTCHA verification.',
    ];

    public function updated($propertyName)
    {
        if ($propertyName === 'phone') {
            $this->normalizePhone();
        }

        // Avoid validating the full password rule set while the confirmation field is still empty.
        if (in_array($propertyName, ['password', 'password_confirmation'], true)
            && (empty($this->password) || empty($this->password_confirmation))
        ) {
            return;
        }

        if ($propertyName === 'gRecaptchaResponse') {
            return;
        }

        $this->validateOnly($propertyName);
    }

    /**
     * Normalize phone number to always include +65 country code
     */
    protected function normalizePhone(): void
    {
        if (empty($this->phone)) {
            return;
        }

        // Remove all non-digit characters except +
        $normalized = preg_replace('/[^\d+]/', '', trim($this->phone));

        // Extract digits only for processing
        $digitsOnly = preg_replace('/\D/', '', $normalized);

        // If doesn't start with +, add +65
        if (! str_starts_with($normalized, '+')) {
            // Check if it already starts with 65 (without +)
            if (str_starts_with($digitsOnly, '65') && strlen($digitsOnly) > 2) {
                // Remove the leading 65 and add +65
                $localNumber = substr($digitsOnly, 2);
                $this->phone = '+65'.$localNumber;
            } else {
                // Just add +65 prefix
                $this->phone = '+65'.$digitsOnly;
            }
        } else {
            // Already has +, ensure it's properly formatted
            // If it starts with +65, keep it
            if (str_starts_with($normalized, '+65')) {
                $this->phone = $normalized;
            } elseif (str_starts_with($normalized, '+') && ! str_starts_with($normalized, '+65')) {
                // Has + but different country code, replace with +65
                $localNumber = preg_replace('/\D/', '', substr($normalized, 1));
                // If local number starts with 65, remove it
                if (str_starts_with($localNumber, '65') && strlen($localNumber) > 2) {
                    $localNumber = substr($localNumber, 2);
                }
                $this->phone = '+65'.$localNumber;
            } else {
                $this->phone = $normalized;
            }
        }
    }

    /**
     * Generate a random email for the user
     */
    protected function generateUserRandomEmail(): string
    {
        // Generate email: user-uuid@hopevillage.sg
        do {
            $uuid = explode('-', Str::uuid()->toString())[0];
            $email = 'user-'.$uuid.'@hopevillage.sg';
        } while (User::where('email', $email)->exists());

        return $email;
    }

    public function submit()
    {
        $this->isSubmitting = true;

        $this->normalizePhone();

        // Empty optional URL/email should not fail validation.
        if (blank($this->website)) {
            $this->website = null;
        }
        if (blank($this->email)) {
            $this->email = null;
        }

        try {
            $this->validate();
        } catch (ValidationException $e) {
            // Reset reCAPTCHA on validation errors since tokens are single-use
            $this->dispatch('reset-recaptcha');
            $this->gRecaptchaResponse = '';
            $this->isSubmitting = false;
            throw $e;
        }

        try {
            DB::transaction(function () {
                $addressToSave = trim($this->address ?? '');
                if (! empty(trim($this->unitNumber ?? ''))) {
                    $addressToSave = trim($addressToSave.' #'.trim($this->unitNumber));
                }

                $merchant = Merchant::create([
                    'name' => $this->name,
                    'description' => $this->description,
                    'contact_name' => $this->contact_name,
                    'phone' => $this->phone,
                    'email' => $this->email,
                    'address' => $addressToSave,
                    'city' => $this->city,
                    'province' => $this->province,
                    'postal_code' => $this->postal_code,
                    'website' => $this->website,
                    'is_active' => false, // Subject for approval
                ]);

                // Handle logo upload
                if ($this->logo) {
                    $merchant->addMedia($this->logo->getPathname())
                        ->usingName($merchant->name.' - Logo')
                        ->usingFileName($this->logo->getClientOriginalName())
                        ->toMediaCollection('logo');
                }

                // Generate email if not provided
                $userEmail = ! empty(trim($this->email ?? ''))
                    ? trim($this->email)
                    : $this->generateUserRandomEmail();

                // Create or get merchant user by phone number
                $user = User::where('whatsapp_number', $this->phone)->first();

                if (! $user) {
                    // Create new user with provided password
                    $user = User::create([
                        'name' => $this->contact_name,
                        'email' => $userEmail,
                        'password' => Hash::make($this->password),
                        'whatsapp_number' => $this->phone,
                        'user_type' => 'merchant_user',
                        'current_merchant_id' => $merchant->id,
                    ]);
                } else {
                    // Update existing user if needed
                    // Update email if it was auto-generated and user doesn't have one
                    if (empty($user->email) || str_ends_with($user->email, '@hopevillage.sg')) {
                        $user->update(['email' => $userEmail]);
                    }

                    // Update user type if not already merchant_user
                    if ($user->user_type !== 'merchant_user') {
                        $user->update(['user_type' => 'merchant_user']);
                    }

                    // Always update password on a fresh merchant application with this phone.
                    $user->update([
                        'password' => Hash::make($this->password),
                        'name' => $this->contact_name,
                    ]);
                }

                // Attach user to merchant if not already attached
                if (! $merchant->users()->where('user_id', $user->id)->exists()) {
                    // Check if this is the user's first merchant
                    $isFirstMerchant = $user->merchants()->count() === 0;

                    $merchant->users()->attach($user->id, [
                        'is_default' => $isFirstMerchant,
                    ]);

                    // Set as current merchant if it's their first merchant
                    if ($isFirstMerchant) {
                        $user->update(['current_merchant_id' => $merchant->id]);
                    }
                }
            });
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('reset-recaptcha');
            $this->gRecaptchaResponse = '';
            $this->isSubmitting = false;
            $this->addError('name', 'Something went wrong while submitting your application. Please try again.');

            return;
        }

        // Reset form
        $this->reset([
            'name',
            'description',
            'contact_name',
            'phone',
            'email',
            'unitNumber',
            'address',
            'city',
            'province',
            'postal_code',
            'website',
            'logo',
            'password',
            'password_confirmation',
            'terms',
            'gRecaptchaResponse',
        ]);

        // Restore sensible address defaults after reset
        $this->address = '7 Kaki Bukit Avenue 3';
        $this->city = 'Singapore';
        $this->postal_code = '415814';

        $this->resetErrorBag();

        $this->showSuccess = true;
        $this->isSubmitting = false;
    }

    public function render()
    {
        return view('livewire.merchant.apply')->layout('layouts.guest');
    }
}

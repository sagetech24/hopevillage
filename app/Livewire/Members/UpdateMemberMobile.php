<?php

namespace App\Livewire\Members;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class UpdateMemberMobile extends Component
{
    private const COUNTRY_CODE = '65';

    /** @var int|null The ID of the member whose mobile number is being updated (passed from parent). */
    public ?int $userId = null;

    /** Local mobile digits only (without +65); UI shows +65 prefix. */
    public string $mobile_number = '';

    public bool $open = true;

    protected $listeners = [
        'closeUpdateMemberMobileModal' => 'close',
    ];

    public function mount(?int $userId = null): void
    {
        $this->userId = $userId;
        $this->open = $userId !== null;

        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                $this->mobile_number = $this->toLocalDigits($user->whatsapp_number ?? '');
            }
        }
    }

    public function close(): void
    {
        $this->open = false;
        $this->mobile_number = '';
        $this->userId = null;
        $this->dispatch('updateMobileModalClosed');
    }

    public function save(): void
    {
        if (! auth()->user()?->canUpdateMemberMobileNumber()) {
            session()->flash('error', 'You do not have permission to update member mobile number.');
            $this->dispatch('updateMobileModalClosed');

            return;
        }

        $user = User::find($this->userId);
        if (! $user) {
            session()->flash('error', 'Member not found.');
            $this->dispatch('updateMobileModalClosed');

            return;
        }

        $localDigits = $this->toLocalDigits($this->mobile_number);

        Validator::make(
            ['mobile_number' => $localDigits],
            [
                'mobile_number' => [
                    'required',
                    'string',
                    'digits:8',
                ],
            ],
            [
                'mobile_number.required' => 'Mobile number is required.',
                'mobile_number.digits' => 'Enter an 8-digit Singapore mobile number (without +65).',
            ]
        )->validate();

        $normalizedNumber = $this->toE164($localDigits);

        if ($this->mobileOwnedByAnotherUser($normalizedNumber, $localDigits, $user->id)) {
            throw ValidationException::withMessages([
                'mobile_number' => 'This mobile number is already registered to another account.',
            ]);
        }

        $previousNumber = $user->whatsapp_number;
        $this->mobile_number = $localDigits;

        $user->forceFill(['whatsapp_number' => $normalizedNumber])->save();

        Log::info('Member mobile number updated by administrator', [
            'admin_user' => [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
            'member_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'previous_whatsapp_number' => $previousNumber,
                'new_whatsapp_number' => $normalizedNumber,
            ],
            'updated_at' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $message = 'Member mobile number updated successfully.';
        $this->close();
        $this->dispatch('updateMobileModalClosed');
        $this->dispatch('hv-toast', type: 'success', message: $message);
    }

    /**
     * Strip non-digits and optional Singapore country code (+65 / 65).
     */
    protected function toLocalDigits(string $input): string
    {
        $digits = preg_replace('/\D+/', '', trim($input)) ?? '';

        if (str_starts_with($digits, self::COUNTRY_CODE) && strlen($digits) > 8) {
            $digits = substr($digits, strlen(self::COUNTRY_CODE));
        }

        return $digits;
    }

    protected function toE164(string $localDigits): string
    {
        return '+'.self::COUNTRY_CODE.$localDigits;
    }

    /**
     * True if another user already owns this number in any common stored format.
     */
    protected function mobileOwnedByAnotherUser(string $e164, string $localDigits, int $ignoreUserId): bool
    {
        $variations = array_values(array_unique([
            $e164,
            self::COUNTRY_CODE.$localDigits,
            $localDigits,
            '+'.$localDigits,
        ]));

        return User::query()
            ->where('id', '!=', $ignoreUserId)
            ->whereIn('whatsapp_number', $variations)
            ->exists();
    }

    public function getUserProperty(): ?User
    {
        if (! $this->userId) {
            return null;
        }

        return User::find($this->userId);
    }

    public function render()
    {
        return view('livewire.members.update-member-mobile');
    }
}

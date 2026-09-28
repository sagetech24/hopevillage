<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use RuntimeException;

class AuthenticateUser
{
    /**
     * Find user by WhatsApp number with flexible matching (supports numbers with/without country code)
     */
    protected function findUserByWhatsAppNumber(string $input): ?User
    {
        // Normalize input: remove spaces, dashes, etc., but keep + and digits
        $normalized = preg_replace('/[^\d+]/', '', $input);
        $digitsOnly = preg_replace('/\D+/', '', $input);

        // Get default country code from config (default to +65 for Singapore)
        $defaultCountryCode = config('services.twilio.default_country_code', '65');

        // Build search variations
        $variations = [
            // Exact matches first
            $input,                    // Original input
            $normalized,               // Normalized (keeps +)
            $digitsOnly,               // Digits only
        ];

        // Add + prefix variation if not already present
        if (! str_starts_with($normalized, '+')) {
            $variations[] = '+'.$digitsOnly;
        }

        // Without + prefix variation
        $withoutPlus = str_replace('+', '', $normalized);
        if ($withoutPlus !== $normalized) {
            $variations[] = $withoutPlus;
        }

        // If input doesn't start with +, try adding default country code
        if (! str_starts_with($normalized, '+') && $defaultCountryCode) {
            $variations[] = '+'.$defaultCountryCode.$digitsOnly;  // +65 + digits
            $variations[] = $defaultCountryCode.$digitsOnly;        // 65 + digits
        }

        // If input appears to start with country code (without +), extract local number
        if ($defaultCountryCode && strlen($digitsOnly) > strlen($defaultCountryCode)) {
            if (str_starts_with($digitsOnly, $defaultCountryCode)) {
                $localNumber = substr($digitsOnly, strlen($defaultCountryCode));
                $variations[] = '+'.$defaultCountryCode.$localNumber;  // +65 + local
                $variations[] = $localNumber;  // Just local number
            }
        }

        // Remove duplicates and empty values
        $variations = array_unique(array_filter($variations));

        // Try each variation
        foreach ($variations as $variation) {
            $user = User::where('whatsapp_number', $variation)->first();
            if ($user) {
                return $user;
            }
        }

        // Last attempt: try matching by last 8 digits (Singapore local number length)
        // This handles cases where user enters just the local number part
        if (strlen($digitsOnly) >= 8) {
            $lastDigits = substr($digitsOnly, -8);
            $users = User::whereNotNull('whatsapp_number')
                ->get()
                ->filter(function ($u) use ($lastDigits) {
                    $storedDigits = preg_replace('/\D+/', '', $u->whatsapp_number);

                    return str_ends_with($storedDigits, $lastDigits);
                });

            if ($users->count() === 1) {
                return $users->first();
            }
        }

        return null;
    }

    /**
     * Handle the incoming request.
     * Fortify expects this to return a User or throw ValidationException.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __invoke(Request $request): User
    {
        $username = trim($request->input(Fortify::username()));
        $password = $request->input('password');

        if (empty($username) || empty($password)) {
            throw ValidationException::withMessages([
                Fortify::username() => [__('The provided credentials are incorrect.')],
            ]);
        }

        // Determine if the input is an email or WhatsApp number
        $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);

        // Find user by email or WhatsApp number
        $user = null;
        if ($isEmail) {
            // Try to find by email
            $user = User::where('email', $username)->first();
        } else {
            // Try to find by WhatsApp number with flexible matching
            $user = $this->findUserByWhatsAppNumber($username);
        }

        // If user not found or password doesn't match
        if (! $user || ! $this->passwordMatches($user, $password)) {
            throw ValidationException::withMessages([
                Fortify::username() => [__('The provided credentials are incorrect.')],
            ]);
        }

        // Update the request with the user's email so Fortify can continue
        // This ensures session management works correctly
        $request->merge([Fortify::username() => $user->email]);

        return $user;
    }

    /**
     * Verify the password without crashing when a stored hash is not bcrypt.
     */
    protected function passwordMatches(User $user, mixed $password): bool
    {
        if (! is_string($password) || $password === '') {
            return false;
        }

        $hash = $user->getRawOriginal('password');

        if (! is_string($hash) || $hash === '') {
            return false;
        }

        try {
            $matches = Hash::check($password, $hash);
        } catch (RuntimeException) {
            $matches = password_verify($password, $hash);
        }

        if ($matches) {
            try {
                $needsRehash = Hash::needsRehash($hash);
            } catch (RuntimeException) {
                $needsRehash = true;
            }

            if ($needsRehash) {
                $user->forceFill([
                    'password' => $password,
                ])->save();
            }
        }

        return $matches;
    }
}

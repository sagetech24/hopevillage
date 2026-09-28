<?php

namespace App\Support;

use App\Models\User;

class PhoneNumberLookup
{
    /**
     * Find a user by phone number with flexible matching (with/without country code, + prefix, etc.).
     */
    public static function findUser(string $input): ?User
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d+]/', '', $input);
        $digitsOnly = preg_replace('/\D+/', '', $input);
        $defaultCountryCode = (string) config('services.twilio.default_country_code', '65');

        $variations = [
            $input,
            $normalized,
            $digitsOnly,
        ];

        if (! str_starts_with($normalized, '+')) {
            $variations[] = '+'.$digitsOnly;
        }

        $withoutPlus = str_replace('+', '', $normalized);
        if ($withoutPlus !== $normalized) {
            $variations[] = $withoutPlus;
        }

        if (! str_starts_with($normalized, '+') && $defaultCountryCode !== '') {
            $variations[] = '+'.$defaultCountryCode.$digitsOnly;
            $variations[] = $defaultCountryCode.$digitsOnly;
        }

        if ($defaultCountryCode !== '' && strlen($digitsOnly) > strlen($defaultCountryCode)) {
            if (str_starts_with($digitsOnly, $defaultCountryCode)) {
                $localNumber = substr($digitsOnly, strlen($defaultCountryCode));
                $variations[] = '+'.$defaultCountryCode.$localNumber;
                $variations[] = $localNumber;
            }
        }

        $variations = array_unique(array_filter($variations));

        foreach ($variations as $variation) {
            $user = User::where('whatsapp_number', $variation)->first();
            if ($user) {
                return $user;
            }
        }

        if (strlen($digitsOnly) >= 8) {
            $lastDigits = substr($digitsOnly, -8);
            $users = User::whereNotNull('whatsapp_number')
                ->get()
                ->filter(function ($user) use ($lastDigits) {
                    $storedDigits = preg_replace('/\D+/', '', $user->whatsapp_number ?? '');

                    return str_ends_with($storedDigits, $lastDigits);
                });

            if ($users->count() === 1) {
                return $users->first();
            }
        }

        return null;
    }
}

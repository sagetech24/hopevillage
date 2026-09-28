<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    /** OTP validity window in minutes. */
    public const EXPIRY_MINUTES = 5;

    /** Minimum seconds between OTP resend requests for the same phone. */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /** Maximum failed verification attempts before lockout. */
    public const MAX_FAILED_ATTEMPTS = 5;

    /**
     * Generate a secure 6-digit OTP and persist it for the given phone number.
     *
     * @throws \RuntimeException When resend cooldown is active.
     */
    public function generateOtp(string $phone): Otp
    {
        $phone = $this->normalizePhone($phone);

        if ($seconds = $this->secondsUntilResendAllowed($phone)) {
            throw new \RuntimeException("Please wait {$seconds} seconds before requesting a new OTP.");
        }

        $otp = Otp::query()->create([
            'purpose' => Otp::PURPOSE_PHONE_VERIFICATION,
            'phone_number' => $phone,
            'otp_code' => (string) random_int(100000, 999999),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        // Fresh OTP resets failed verification attempts for this phone.
        Cache::forget($this->failedAttemptsCacheKey($phone));

        return $otp;
    }

    /**
     * Seconds remaining before a new OTP may be requested, or 0 if allowed.
     */
    public function secondsUntilResendAllowed(string $phone): int
    {
        $phone = $this->normalizePhone($phone);

        $latest = Otp::query()
            ->where('phone_number', $phone)
            ->latest()
            ->first();

        if (! $latest) {
            return 0;
        }

        $elapsed = $latest->created_at->diffInSeconds(now());

        if ($elapsed >= self::RESEND_COOLDOWN_SECONDS) {
            return 0;
        }

        return self::RESEND_COOLDOWN_SECONDS - $elapsed;
    }

    /**
     * Verify an OTP for the given phone number.
     *
     * @return array{success: bool, message: string}
     */
    public function verifyOtp(string $phone, string $otp): array
    {
        $phone = $this->normalizePhone($phone);
        $otp = trim($otp);

        if ($this->isVerificationLocked($phone)) {
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.',
            ];
        }

        $record = Otp::query()
            ->where('phone_number', $phone)
            ->where('otp_code', $otp)
            ->latest()
            ->first();

        if (! $record) {
            $this->incrementFailedAttempts($phone);

            return [
                'success' => false,
                'message' => 'Invalid OTP',
            ];
        }

        if ($record->isVerified()) {
            return [
                'success' => false,
                'message' => 'OTP has already been used',
            ];
        }

        if ($record->isExpired()) {
            $this->incrementFailedAttempts($phone);

            return [
                'success' => false,
                'message' => 'OTP has expired',
            ];
        }

        $record->forceFill(['verified_at' => now()])->save();
        Cache::forget($this->failedAttemptsCacheKey($phone));

        return [
            'success' => true,
            'message' => 'OTP verified successfully',
        ];
    }

    /**
     * Whether verification is locked due to too many failed attempts.
     */
    public function isVerificationLocked(string $phone): bool
    {
        return $this->getFailedAttempts($this->normalizePhone($phone)) >= self::MAX_FAILED_ATTEMPTS;
    }

    /**
     * Normalize phone numbers to a consistent E.164-style format.
     */
    public function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if (str_starts_with($phone, '+')) {
            return '+'.preg_replace('/\D+/', '', substr($phone, 1));
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return $digits !== '' ? '+'.$digits : '';
    }

    /**
     * Cache key for tracking failed verification attempts per phone.
     */
    protected function failedAttemptsCacheKey(string $phone): string
    {
        return 'otp_verify_attempts:'.$phone;
    }

    /**
     * Get the current failed attempt count for a phone number.
     */
    protected function getFailedAttempts(string $phone): int
    {
        return (int) Cache::get($this->failedAttemptsCacheKey($phone), 0);
    }

    /**
     * Increment failed verification attempts and lock out after the maximum.
     */
    protected function incrementFailedAttempts(string $phone): void
    {
        $key = $this->failedAttemptsCacheKey($phone);
        $attempts = $this->getFailedAttempts($phone) + 1;

        // Keep lockout state for at least the OTP expiry window.
        Cache::put($key, $attempts, now()->addMinutes(self::EXPIRY_MINUTES));
    }
}

<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PasswordResetOtpService
{
    /** Maximum failed verification attempts before lockout. */
    public const MAX_FAILED_ATTEMPTS = 5;

    /**
     * OTP validity window in minutes (matches password reset token expiry).
     */
    public function expiryMinutes(): int
    {
        return (int) config('auth.passwords.users.expire', 60);
    }

    /**
     * Minimum seconds between OTP resend requests (matches broker throttle).
     */
    public function resendCooldownSeconds(): int
    {
        return (int) config('auth.passwords.users.throttle', 60);
    }

    /**
     * Generate a password-reset OTP for the given user and channel.
     *
     * @throws \RuntimeException When resend cooldown is active.
     */
    public function generateOtp(User $user, string $channel, ?string $phone = null): Otp
    {
        if ($seconds = $this->secondsUntilResendAllowed($user)) {
            throw new \RuntimeException("Please wait {$seconds} seconds before requesting a new OTP.");
        }

        $this->invalidatePendingOtps($user);

        $otp = Otp::query()->create([
            'purpose' => Otp::PURPOSE_PASSWORD_RESET,
            'user_id' => $user->id,
            'channel' => $channel,
            'email' => $channel === 'email' ? $user->email : null,
            'phone_number' => $phone,
            'otp_code' => (string) random_int(100000, 999999),
            'expires_at' => now()->addMinutes($this->expiryMinutes()),
        ]);

        Cache::forget($this->failedAttemptsCacheKey($user->id));

        return $otp;
    }

    /**
     * Seconds remaining before a new OTP may be requested, or 0 if allowed.
     */
    public function secondsUntilResendAllowed(User $user): int
    {
        $latest = Otp::query()
            ->where('purpose', Otp::PURPOSE_PASSWORD_RESET)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if (! $latest) {
            return 0;
        }

        $elapsed = $latest->created_at->diffInSeconds(now());

        if ($elapsed >= $this->resendCooldownSeconds()) {
            return 0;
        }

        return $this->resendCooldownSeconds() - $elapsed;
    }

    /**
     * Verify a password-reset OTP for the given user.
     *
     * @return array{success: bool, message: string}
     */
    public function verifyOtp(User $user, string $code): array
    {
        $code = trim($code);

        if ($this->isVerificationLocked($user)) {
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.',
            ];
        }

        $record = $this->latestPendingOtp($user);

        if (! $record) {
            $this->incrementFailedAttempts($user);

            return [
                'success' => false,
                'message' => 'Invalid OTP',
            ];
        }

        if ($record->otp_code !== $code) {
            $this->incrementFailedAttempts($user);

            return [
                'success' => false,
                'message' => 'Invalid OTP',
            ];
        }

        if ($record->isExpired()) {
            $this->incrementFailedAttempts($user);

            return [
                'success' => false,
                'message' => 'OTP has expired',
            ];
        }

        $record->forceFill(['verified_at' => now()])->save();
        Cache::forget($this->failedAttemptsCacheKey($user->id));

        return [
            'success' => true,
            'message' => 'OTP verified successfully',
        ];
    }

    /**
     * Whether the password reset token for the given email has expired.
     */
    public function isTokenExpired(string $email): bool
    {
        $record = DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
            ->where('email', $email)
            ->first();

        if (! $record) {
            return true;
        }

        $createdAt = \Carbon\Carbon::parse($record->created_at);

        return $createdAt->addMinutes($this->expiryMinutes())->isPast();
    }

    /**
     * Mask an email address for display (e.g. j***@example.com).
     */
    public function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email, 2);
        $maskedLocal = strlen($local) > 1
            ? $local[0].str_repeat('*', min(3, strlen($local) - 1))
            : '*';

        return $maskedLocal.'@'.$domain;
    }

    /**
     * Mask a phone number for display (e.g. ***4567).
     */
    public function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) <= 4) {
            return '****';
        }

        return str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }

    /**
     * Whether verification is locked due to too many failed attempts.
     */
    public function isVerificationLocked(User $user): bool
    {
        return $this->getFailedAttempts($user->id) >= self::MAX_FAILED_ATTEMPTS;
    }

    protected function failedAttemptsCacheKey(int $userId): string
    {
        return 'password_reset_otp_attempts:'.$userId;
    }

    protected function getFailedAttempts(int $userId): int
    {
        return (int) Cache::get($this->failedAttemptsCacheKey($userId), 0);
    }

    protected function incrementFailedAttempts(User $user): void
    {
        $key = $this->failedAttemptsCacheKey($user->id);
        $attempts = $this->getFailedAttempts($user->id) + 1;

        Cache::put($key, $attempts, now()->addMinutes($this->expiryMinutes()));
    }

    /**
     * The most recent unverified password-reset OTP for a user.
     */
    protected function latestPendingOtp(User $user): ?Otp
    {
        return Otp::query()
            ->where('purpose', Otp::PURPOSE_PASSWORD_RESET)
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest()
            ->first();
    }

    /**
     * Mark all pending password-reset OTPs as superseded when issuing a new one.
     */
    protected function invalidatePendingOtps(User $user): void
    {
        Otp::query()
            ->where('purpose', Otp::PURPOSE_PASSWORD_RESET)
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);
    }
}

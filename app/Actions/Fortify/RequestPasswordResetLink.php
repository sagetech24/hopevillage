<?php

namespace App\Actions\Fortify;

use App\DataTransferObjects\PasswordResetRequestResult;
use App\Exceptions\PasswordResetDeliveryException;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use App\Services\PasswordResetOtpService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class RequestPasswordResetLink
{
    protected const GENERIC_MESSAGE = 'If your account exists, we have sent password reset instructions.';

    public function __construct(
        protected ?PasswordResetOtpService $otpService = null,
    ) {
        $this->otpService ??= app(PasswordResetOtpService::class);
    }

    /**
     * Handle the password reset request.
     */
    public function __invoke(array $input): PasswordResetRequestResult
    {
        $resetMethod = $input['reset_method'] ?? 'email';
        $identifier = $input['identifier'] ?? '';

        if ($resetMethod !== 'email') {
            throw PasswordResetDeliveryException::message(
                'Password reset via '.$resetMethod.' is temporarily unavailable. Please use email instead.'
            );
        }

        $user = User::where('email', $identifier)->first();

        if (! $user) {
            return PasswordResetRequestResult::notSent(__(self::GENERIC_MESSAGE));
        }

        return $this->sendEmailOtp($user);
    }

    /**
     * Resend the password reset OTP for an in-progress session.
     */
    public function resendOtp(User $user, string $channel, string $identifier = ''): PasswordResetRequestResult
    {
        if ($channel !== 'email') {
            throw PasswordResetDeliveryException::message(
                'Resend is only available for email password reset at this time.'
            );
        }

        return $this->sendEmailOtp($user);
    }

    protected function sendEmailOtp(User $user): PasswordResetRequestResult
    {
        if (! $user->email) {
            throw PasswordResetDeliveryException::message(
                'No email address is on file for this account. Please contact support.'
            );
        }

        try {
            $otp = $this->otpService->generateOtp($user, 'email');
        } catch (\RuntimeException $e) {
            throw PasswordResetDeliveryException::message($e->getMessage());
        }

        $code = $otp->makeVisible(['otp_code'])->otp_code;
        $token = Password::broker()->createToken($user);
        $expiresMinutes = $this->otpService->expiryMinutes();

        $this->deliverEmailOtp($user, $code, $expiresMinutes);

        return PasswordResetRequestResult::sent(
            message: __(self::GENERIC_MESSAGE),
            user: $user,
            token: $token,
            channel: 'email',
            destination: $this->otpService->maskEmail($user->email),
            expiresAt: $otp->expires_at,
        );
    }

    protected function deliverEmailOtp(User $user, string $code, int $expiresMinutes): void
    {
        if (config('auth.password_reset_skip_delivery')) {
            Log::info('Password reset OTP (delivery skipped)', [
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $code,
                'expires_minutes' => $expiresMinutes,
            ]);

            return;
        }

        try {
            Mail::to($user->email)->send(new PasswordResetOtpMail($code, $expiresMinutes));
        } catch (\Throwable $e) {
            report($e);

            throw PasswordResetDeliveryException::message(
                'We could not send the verification code to your email. Please try again later.'
            );
        }
    }
}

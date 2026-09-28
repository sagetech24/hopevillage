<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Jobs\SendOtpSmsJob;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function __construct(
        protected OtpService $otpService,
    ) {}

    /**
     * Generate an OTP, store it, and queue an SMS via Infobip.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $phone = $this->otpService->normalizePhone($request->validated('phone'));

        if ($phone === '') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number.',
            ], 422);
        }

        $seconds = $this->otpService->secondsUntilResendAllowed($phone);

        if ($seconds > 0) {
            return response()->json([
                'success' => false,
                'message' => "Please wait {$seconds} seconds before requesting a new OTP.",
            ], 422);
        }

        try {
            $otp = $this->otpService->generateOtp($phone);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $message = "Your verification code is {$otp->otp_code}";

        SendOtpSmsJob::dispatch($phone, $message);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.',
            'expires_in_minutes' => OtpService::EXPIRY_MINUTES,
        ]);
    }

    /**
     * Verify an OTP and mark the user's phone as verified.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $phone = $this->otpService->normalizePhone($validated['phone']);
        $otpCode = $validated['otp'];

        if ($phone === '') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number.',
            ], 422);
        }

        $result = $this->otpService->verifyOtp($phone, $otpCode);

        if (! $result['success']) {
            $status = match ($result['message']) {
                'OTP has already been used' => 409,
                'OTP has expired' => 410,
                'Too many failed attempts. Please request a new OTP.' => 429,
                default => 422,
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $status);
        }

        $user = $this->findUserByPhone($phone);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found for this phone number.',
            ], 404);
        }

        $user->forceFill(['phone_verified_at' => now()])->save();

        return response()->json([
            'success' => true,
            'message' => 'Phone number verified successfully.',
        ]);
    }

    /**
     * Find a user by normalized or raw whatsapp_number variants.
     */
    protected function findUserByPhone(string $phone): ?User
    {
        $digits = ltrim($phone, '+');

        return User::query()
            ->where(function ($query) use ($phone, $digits) {
                $query->where('whatsapp_number', $phone)
                    ->orWhere('whatsapp_number', $digits)
                    ->orWhere('whatsapp_number', '+'.$digits);
            })
            ->first();
    }
}

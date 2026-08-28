<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\RequestPasswordResetLink;
use App\Exceptions\PasswordResetDeliveryException;
use App\Models\User;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PasswordResetController extends Controller
{
    public const SESSION_KEY = 'password_reset.pending';

    public function __construct(
        protected PasswordResetOtpService $otpService,
    ) {}

    /**
     * Show the password reset request form.
     */
    public function show()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle the password reset request.
     */
    public function store(Request $request)
    {
        $resetMethod = $request->input('reset_method', 'email');

        if ($resetMethod === 'email') {
            $identifier = $request->input('email');
        } elseif ($resetMethod === 'sms') {
            $identifier = $request->input('sms_number');
        } else {
            $identifier = $request->input('whatsapp_number');
        }

        $rules = [
            'reset_method' => ['required', 'in:email,whatsapp,sms'],
        ];

        if ($resetMethod === 'email') {
            $rules['email'] = ['required', 'email'];
        } elseif ($resetMethod === 'sms') {
            $rules['sms_number'] = ['required', 'string'];
        } else {
            $rules['whatsapp_number'] = ['required', 'string'];
        }

        $request->validate($rules);

        $action = app(RequestPasswordResetLink::class);

        try {
            $result = $action([
                'reset_method' => $resetMethod,
                'identifier' => $identifier,
            ]);
        } catch (PasswordResetDeliveryException $e) {
            return back()
                ->withInput($request->only(['email', 'whatsapp_number', 'sms_number', 'reset_method']))
                ->withErrors([
                    'reset' => __($e->getMessage()),
                ]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput($request->only(['email', 'whatsapp_number', 'sms_number', 'reset_method']))
                ->withErrors([
                    'reset' => __('We could not complete your request right now. Please try again later, or contact support if this continues.'),
                ]);
        }

        if (! $result->sent) {
            return back()->with('status', $result->message);
        }

        $this->storePendingSession($result, $identifier);

        $lang = $request->query('lang');
        $verifyUrl = route('password.reset.verify');
        if ($lang) {
            $verifyUrl .= '?lang='.$lang;
        }

        return redirect($verifyUrl);
    }

    /**
     * Show the OTP verification form.
     */
    public function showVerifyOtp(Request $request)
    {
        $pending = Session::get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('password.request');
        }

        if ($this->otpService->isTokenExpired($pending['email'])) {
            Session::forget(self::SESSION_KEY);

            return redirect()
                ->route('password.request')
                ->withErrors(['reset' => __('Your password reset session has expired. Please request a new one.')]);
        }

        return view('auth.verify-password-reset-otp', [
            'channel' => $pending['channel'],
            'destination' => $pending['destination'],
            'expiresMinutes' => $this->otpService->expiryMinutes(),
        ]);
    }

    /**
     * Verify the OTP and redirect to the password reset form.
     */
    public function verifyOtp(Request $request)
    {
        $pending = Session::get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $pending['email'])->first();

        if (! $user) {
            Session::forget(self::SESSION_KEY);

            return redirect()
                ->route('password.request')
                ->withErrors(['reset' => __('Your password reset session has expired. Please request a new one.')]);
        }

        if ($this->otpService->isTokenExpired($pending['email'])) {
            Session::forget(self::SESSION_KEY);

            return back()->withErrors(['otp' => __('Your password reset session has expired. Please request a new one.')]);
        }

        $result = $this->otpService->verifyOtp($user, $request->input('otp'));

        if (! $result['success']) {
            $field = match ($result['message']) {
                'Too many failed attempts. Please request a new OTP.' => 'reset',
                default => 'otp',
            };

            return back()->withErrors([$field => __($result['message'])]);
        }

        $token = $pending['token'];
        $email = $pending['email'];

        Session::forget(self::SESSION_KEY);

        return redirect()->route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Resend the password reset OTP.
     */
    public function resendOtp(Request $request)
    {
        $pending = Session::get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('password.request');
        }

        $user = User::where('email', $pending['email'])->first();

        if (! $user) {
            Session::forget(self::SESSION_KEY);

            return redirect()
                ->route('password.request')
                ->withErrors(['reset' => __('Your password reset session has expired. Please request a new one.')]);
        }

        $action = app(RequestPasswordResetLink::class);

        try {
            $result = $action->resendOtp($user, $pending['channel'], $pending['identifier'] ?? '');
        } catch (PasswordResetDeliveryException $e) {
            return back()->withErrors(['reset' => __($e->getMessage())]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['otp' => __($e->getMessage())]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'reset' => __('We could not resend the code right now. Please try again later.'),
            ]);
        }

        if ($result->token !== '') {
            $pending['token'] = $result->token;
        }

        if ($result->expiresAt) {
            $pending['expires_at'] = $result->expiresAt->format(\DateTimeInterface::ATOM);
        }

        Session::put(self::SESSION_KEY, $pending);

        return back()->with('status', __('A new verification code has been sent.'));
    }

    /**
     * @param  \App\DataTransferObjects\PasswordResetRequestResult  $result
     */
    protected function storePendingSession($result, string $identifier): void
    {
        Session::put(self::SESSION_KEY, [
            'email' => $result->user->email,
            'token' => $result->token,
            'channel' => $result->channel,
            'destination' => $result->destination,
            'identifier' => $identifier,
            'expires_at' => $result->expiresAt?->format(\DateTimeInterface::ATOM),
        ]);
    }
}

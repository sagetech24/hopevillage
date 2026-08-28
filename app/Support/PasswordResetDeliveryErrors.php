<?php

namespace App\Support;

class PasswordResetDeliveryErrors
{
    /**
     * @param  array{success?: bool, message?: string, code?: int|string|null}  $result
     */
    public static function forSmsResult(array $result, string $provider): string
    {
        if ($provider === 'infobip') {
            $message = strtolower((string) ($result['message'] ?? ''));

            if (str_contains($message, 'not configured')) {
                return 'SMS is not configured. Please use email or WhatsApp to reset your password.';
            }

            return 'Failed to send password reset OTP via SMS. Please try email or WhatsApp instead.';
        }

        $code = isset($result['code']) ? (int) $result['code'] : null;

        return match ($code) {
            21635 => 'SMS cannot be sent to this number because it is a landline. Please use a mobile number, or reset your password via email or WhatsApp.',
            21408 => 'SMS is not available for this phone number\'s region. Please use email or WhatsApp to reset your password.',
            21659 => 'SMS could not be sent from the configured sender number. Please contact support or use email or WhatsApp.',
            21211 => 'The phone number you entered is not valid for SMS. Please check the number or use email or WhatsApp.',
            21614 => 'This phone number cannot receive SMS messages. Please use a mobile number, or reset via email or WhatsApp.',
            default => 'Failed to send password reset OTP via SMS. Please try email or WhatsApp instead.',
        };
    }

    public static function noPhone(string $channel): string
    {
        return match ($channel) {
            'sms' => 'No mobile number is on file for this account. Please use email or WhatsApp to reset your password.',
            'whatsapp' => 'No WhatsApp number is on file for this account. Please use email or SMS to reset your password.',
            default => 'No contact number is on file for this account. Please try another reset method.',
        };
    }

    public static function whatsappUnavailable(): string
    {
        return 'WhatsApp could not deliver the verification code. Please try email or SMS instead.';
    }
}

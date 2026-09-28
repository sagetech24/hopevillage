<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioSmsService
{
    public function enabled(): bool
    {
        $hasAccount = (bool) config('services.twilio.account_sid');
        $hasFrom = (bool) config('services.twilio.sms_from');

        $hasAuthToken = (bool) config('services.twilio.auth_token');
        $hasApiKey = (bool) (config('services.twilio.api_key_sid') && config('services.twilio.api_key_secret'));

        return $hasAccount && $hasFrom && ($hasAuthToken || $hasApiKey);
    }

    /**
     * Send an SMS message through the Twilio Messages API.
     *
     * @return array{success: bool, message?: string, sid?: string|null, status?: string|null}
     */
    public function sendSms(string $phoneNumber, string $text): array
    {
        if (! $this->enabled()) {
            Log::warning('Twilio SMS not configured.', [
                'has_account_sid' => (bool) config('services.twilio.account_sid'),
                'has_auth_token' => (bool) config('services.twilio.auth_token'),
                'has_api_key_sid' => (bool) config('services.twilio.api_key_sid'),
                'has_api_key_secret' => (bool) config('services.twilio.api_key_secret'),
                'has_sms_from' => (bool) config('services.twilio.sms_from'),
            ]);

            return [
                'success' => false,
                'message' => 'Twilio SMS is not configured. Set TWILIO_ACCOUNT_SID + TWILIO_SMS_FROM, and either TWILIO_AUTH_TOKEN or TWILIO_API_KEY_SID + TWILIO_API_KEY_SECRET.',
            ];
        }

        $to = $this->normalizeE164($phoneNumber);
        if (! $to) {
            Log::warning('Twilio SMS invalid destination number format.', [
                'input' => $phoneNumber,
                'hint' => 'Use +E.164 format like +6591234567 (or set TWILIO_DEFAULT_COUNTRY_CODE).',
            ]);

            return [
                'success' => false,
                'message' => 'Invalid phone number format. Use +E.164 like +6591234567.',
            ];
        }

        $sid = (string) config('services.twilio.account_sid');
        $from = (string) config('services.twilio.sms_from');

        [$username, $password, $authMode] = $this->resolveAuthCredentials($sid);

        try {
            $response = Http::withBasicAuth($username, $password)
                ->asForm()
                ->timeout(30)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $text,
                ]);

            if (! $response->successful()) {
                $payload = $response->json();
                $twilioMessage = is_array($payload) ? ($payload['message'] ?? null) : null;
                $twilioCode = is_array($payload) ? ($payload['code'] ?? null) : null;

                Log::error('Twilio SMS send failed.', [
                    'status' => $response->status(),
                    'to' => $to,
                    'from' => $from,
                    'auth_mode' => $authMode,
                    'body' => $payload ?: $response->body(),
                ]);

                $details = $twilioMessage ?: 'Twilio rejected the request.';
                if ($twilioCode) {
                    $details .= " (Twilio code: {$twilioCode})";
                }

                return [
                    'success' => false,
                    'message' => $details,
                    'code' => $twilioCode,
                ];
            }

            Log::info('Twilio SMS send accepted.', [
                'to' => $to,
                'from' => $from,
                'auth_mode' => $authMode,
                'sid' => $response->json('sid'),
                'status' => $response->json('status'),
            ]);

            return [
                'success' => true,
                'sid' => $response->json('sid'),
                'status' => $response->json('status'),
            ];
        } catch (\Throwable $e) {
            Log::error('Twilio SMS API exception.', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS. Please try again later.',
            ];
        }
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolveAuthCredentials(string $accountSid): array
    {
        $apiKeySid = (string) config('services.twilio.api_key_sid');
        $apiKeySecret = (string) config('services.twilio.api_key_secret');

        if ($apiKeySid !== '' && $apiKeySecret !== '') {
            return [$apiKeySid, $apiKeySecret, 'api_key'];
        }

        return [$accountSid, (string) config('services.twilio.auth_token'), 'auth_token'];
    }

    /**
     * Normalize into E.164 format: +E164
     */
    private function normalizeE164(string $phoneNumber): ?string
    {
        $raw = trim($phoneNumber);
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'whatsapp:')) {
            $raw = substr($raw, strlen('whatsapp:'));
        }

        $normalized = preg_replace('/[^\d\+]/', '', $raw);
        if (! $normalized) {
            return null;
        }

        if (! str_starts_with($normalized, '+')) {
            $digitsOnly = preg_replace('/\D+/', '', $normalized);
            $cc = (string) config('services.twilio.default_country_code');
            if ($cc !== '' && $digitsOnly !== '') {
                $normalized = '+'.preg_replace('/\D+/', '', $cc).$digitsOnly;
            } else {
                return null;
            }
        }

        return $normalized;
    }
}

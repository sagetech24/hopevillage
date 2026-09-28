<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfobipService
{
    /**
     * Whether Infobip credentials are configured.
     */
    public function enabled(): bool
    {
        return (bool) config('services.infobip.base_url')
            && (bool) config('services.infobip.api_key')
            && (bool) config('services.infobip.sender');
    }

    /**
     * Send an SMS message through the Infobip SMS API.
     *
     * @return array{success: bool, message?: string, message_id?: string|null}
     */
    public function sendSms(string $phoneNumber, string $text): array
    {
        if (! $this->enabled()) {
            Log::warning('Infobip SMS not configured.', [
                'has_base_url' => (bool) config('services.infobip.base_url'),
                'has_api_key' => (bool) config('services.infobip.api_key'),
                'has_sender' => (bool) config('services.infobip.sender'),
            ]);

            return [
                'success' => false,
                'message' => 'SMS service is not configured.',
            ];
        }

        $baseUrl = rtrim((string) config('services.infobip.base_url'), '/');
        $apiKey = (string) config('services.infobip.api_key');
        $sender = (string) config('services.infobip.sender');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'App '.$apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/sms/2/text/advanced", [
                    'messages' => [
                        [
                            'from' => $sender,
                            'destinations' => [
                                // Infobip expects international format without the leading "+".
                                ['to' => ltrim($phoneNumber, '+')],
                            ],
                            'text' => $text,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $body = $response->json();
                $messageId = data_get($body, 'messages.0.messageId');
                $status = data_get($body, 'messages.0.status');
                $to = data_get($body, 'messages.0.to');

                Log::info('Infobip SMS API accepted message.', [
                    'message_id' => $messageId,
                    'to' => $to,
                    'status_name' => data_get($status, 'name'),
                    'status_description' => data_get($status, 'description'),
                    'status_group' => data_get($status, 'groupName'),
                ]);

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'status' => $status,
                ];
            }

            Log::error('Infobip SMS API request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'phone' => $phoneNumber,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS. Please try again later.',
            ];
        } catch (\Throwable $e) {
            Log::error('Infobip SMS API exception.', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS. Please try again later.',
            ];
        }
    }
}

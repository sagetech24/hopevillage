<?php

namespace App\Jobs;

use App\Services\InfobipService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOtpSmsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phoneNumber,
        public string $message,
    ) {}

    /**
     * Execute the job — send the OTP SMS via Infobip.
     */
    public function handle(InfobipService $infobipService): void
    {
        $result = $infobipService->sendSms($this->phoneNumber, $this->message);

        if (! $result['success']) {
            Log::error('Queued OTP SMS failed to send.', [
                'phone' => $this->phoneNumber,
                'message' => $result['message'] ?? 'Unknown error',
            ]);
        }
    }
}

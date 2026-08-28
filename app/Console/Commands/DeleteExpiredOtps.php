<?php

namespace App\Console\Commands;

use App\Models\Otp;
use Illuminate\Console\Command;

class DeleteExpiredOtps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otp:delete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired OTPs and OTPs older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = Otp::query()
            ->where('expires_at', '<', now())
            ->delete();

        $oldCount = Otp::query()
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $this->info("Deleted {$expiredCount} expired OTP(s) and {$oldCount} OTP(s) older than 30 days.");

        return self::SUCCESS;
    }
}

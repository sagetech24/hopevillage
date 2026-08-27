<?php

namespace App\Services;

use App\Models\AdminVoucher;
use App\Models\AdminVoucherLedgerEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminVoucherVoidService
{
    public function __construct(
        private PointsService $pointsService,
    ) {}

    /**
     * Void a claimed or redeemed admin voucher assignment for a member.
     * Refunds points when the member originally paid via claim; awards refund 0.
     * Decrements usage_count and resyncs merchant ledger when a redemption is voided.
     *
     * @return array{points_refunded: int, previous_status: string}
     */
    public function voidForMember(
        AdminVoucher $voucher,
        User $member,
        User $admin,
        ?string $reason = null,
    ): array {
        return DB::transaction(function () use ($voucher, $member, $admin, $reason) {
            $lockedVoucher = AdminVoucher::query()
                ->whereKey($voucher->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedMember = User::query()
                ->whereKey($member->id)
                ->lockForUpdate()
                ->firstOrFail();

            $pivotUser = $lockedVoucher->users()
                ->where('users.id', $lockedMember->id)
                ->first();

            if (! $pivotUser) {
                throw new \RuntimeException('This member does not have this voucher.');
            }

            $previousStatus = (string) $pivotUser->pivot->status;

            if (! in_array($previousStatus, ['claimed', 'redeemed'], true)) {
                throw new \RuntimeException('Only claimed or redeemed vouchers can be voided.');
            }

            $merchantId = $pivotUser->pivot->redeemed_at_merchant_id
                ? (int) $pivotUser->pivot->redeemed_at_merchant_id
                : null;
            $redeemedAt = $pivotUser->pivot->redeemed_at
                ? Carbon::parse($pivotUser->pivot->redeemed_at)
                : null;

            $pointsRefunded = $this->pointsService->refundAdminVoucherVoid(
                $lockedMember,
                $lockedVoucher,
                $admin,
                $previousStatus,
                $reason,
            );

            $lockedMember->adminVouchers()->updateExistingPivot($lockedVoucher->id, [
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $admin->id,
                'void_reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
                'points_refunded' => $pointsRefunded,
            ]);

            if ($lockedVoucher->usage_count > 0) {
                $lockedVoucher->decrement('usage_count');
            }

            if ($previousStatus === 'redeemed' && $merchantId && $redeemedAt) {
                $this->resyncLedgerPeriod($merchantId, $lockedVoucher, $redeemedAt);
            }

            return [
                'points_refunded' => $pointsRefunded,
                'previous_status' => $previousStatus,
            ];
        });
    }

    /**
     * Preview refund amount without mutating state (for confirmation UI).
     */
    public function previewRefundAmount(AdminVoucher $voucher, User $member): int
    {
        return $this->pointsService->resolveAdminVoucherRefundAmount($member, $voucher);
    }

    private function resyncLedgerPeriod(int $merchantId, AdminVoucher $voucher, Carbon $redeemedAt): void
    {
        $periodStart = $redeemedAt->copy()->startOfMonth();
        $periodEnd = $redeemedAt->copy()->endOfMonth();
        $periodMonth = $periodStart->toDateString();

        $totalRedemptions = (int) DB::table('user_admin_voucher')
            ->where('admin_voucher_id', $voucher->id)
            ->where('redeemed_at_merchant_id', $merchantId)
            ->where('status', 'redeemed')
            ->whereNotNull('redeemed_at')
            ->where('redeemed_at', '>=', $periodStart->toDateTimeString())
            ->where('redeemed_at', '<=', $periodEnd->toDateTimeString())
            ->count();

        $amountPerRedemption = (float) $voucher->amount_cost * (int) $voucher->points_cost;

        AdminVoucherLedgerEntry::query()->updateOrCreate(
            [
                'merchant_id' => $merchantId,
                'admin_voucher_id' => $voucher->id,
                'period_month' => $periodMonth,
            ],
            [
                'total_redemptions' => $totalRedemptions,
                'total_amount_dispensed' => $totalRedemptions * $amountPerRedemption,
            ],
        );
    }
}

<?php

namespace App\Livewire\Member\AdminVouchers;

use App\Models\AdminVoucher;
use App\Services\PointsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Browse extends Component
{
    public function claim(int $adminVoucherId): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('notify', type: 'error', message: 'You must be logged in to claim vouchers.');
            return;
        }

        $adminVoucher = AdminVoucher::query()->whereKey($adminVoucherId)->first();
        if (!$adminVoucher || !$adminVoucher->isValid()) {
            $this->dispatch('notify', type: 'error', message: 'Admin voucher is not available.');
            return;
        }
        if (!$adminVoucher->isVisibleToMember($user)) {
            $this->dispatch('notify', type: 'error', message: 'Admin voucher is not available.');
            return;
        }

        // Check if already claimed
        if ($user->hasActiveAdminVoucher($adminVoucherId)) {
            $this->dispatch('notify', type: 'info', message: 'You already claimed this admin voucher.');
            return;
        }

        // Check sufficient points balance
        if ($user->total_points < $adminVoucher->points_cost) {
            $this->dispatch('notify', type: 'error', message: 'Insufficient points. You need ' . number_format($adminVoucher->points_cost) . ' points to claim this voucher.');
            return;
        }

        try {
            DB::transaction(function () use ($user, $adminVoucher) {
                // Deduct points
                app(PointsService::class)->deductAdminVoucherClaim($user, $adminVoucher);

                // Attach voucher to user (or reactivate a previously voided assignment)
                $user->claimAdminVoucherAssignment($adminVoucher);

                // Increment usage count
                $adminVoucher->increment('usage_count');
            });

            // Refresh user to get updated points
            $user->refresh();
            
            $this->dispatch('notify', type: 'success', message: 'Admin voucher claimed! ' . number_format($adminVoucher->points_cost) . ' points deducted.');
            // Dispatch event to update points header in real-time
            $this->dispatch('points-updated');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to claim voucher: ' . $e->getMessage());
        }
    }

    public function getActiveAdminVouchersProperty(): Collection
    {
        $user = auth()->user();

        return AdminVoucher::query()
            ->with('merchants')
            ->where('is_active', true)
            ->latest()
            ->get()
            ->filter(fn (AdminVoucher $v) => $v->isVisibleToMember($user))
            ->values();
    }

    public function getClaimableAdminVouchersProperty(): Collection
    {
        $user = auth()->user();

        $adminVouchers = $this->activeAdminVouchers->filter(fn (AdminVoucher $v) => $v->isValid());

        if (!$user) {
            return $adminVouchers->values();
        }

        $claimedIds = $user->adminVouchers()
            ->wherePivotIn('status', ['claimed', 'redeemed'])
            ->pluck('admin_vouchers.id')
            ->all();

        return $adminVouchers
            ->reject(fn (AdminVoucher $v) => in_array($v->id, $claimedIds, true))
            ->values();
    }

    public function render()
    {
        return view('livewire.member.admin-vouchers.browse');
    }
}


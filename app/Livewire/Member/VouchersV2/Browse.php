<?php

namespace App\Livewire\Member\VouchersV2;

use App\Models\Voucher;
use App\Models\AdminVoucher;
use App\Services\PointsService;
use Illuminate\Support\Collection;
use Livewire\Component;

class Browse extends Component
{
    public function claim(int $voucherId): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('notify', type: 'error', message: 'You must be logged in to claim vouchers.');
            return;
        }

        $voucher = Voucher::query()->whereKey($voucherId)->first();
        if (!$voucher || !$voucher->isValid()) {
            $this->dispatch('notify', type: 'error', message: 'Voucher is not available.');
            return;
        }
        if (!$voucher->isVisibleToMember($user)) {
            $this->dispatch('notify', type: 'error', message: 'Voucher is not available.');
            return;
        }

        // Prevent duplicates (unique constraint on pivot also enforces this).
        if ($user->vouchers()->where('vouchers.id', $voucherId)->exists()) {
            $this->dispatch('notify', type: 'info', message: 'You already claimed this voucher.');
            return;
        }

        $user->vouchers()->attach($voucherId, [
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);

        // Increment usage count on claim
        $voucher->increment('usage_count');

        app(PointsService::class)->awardVoucherClaim($user, $voucher);

        $this->dispatch('notify', type: 'success', message: 'Voucher claimed successfully!');
    }

    public function claimAdminVoucher(int $adminVoucherId): void
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
            \Illuminate\Support\Facades\DB::transaction(function () use ($user, $adminVoucher) {
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

    public function getActiveVouchersProperty(): Collection
    {
        $user = auth()->user();

        return Voucher::query()
            ->with('merchant')
            ->where('is_active', true)
            ->latest()
            ->get()
            ->filter(fn (Voucher $v) => $v->isVisibleToMember($user))
            ->values();
    }

    public function getClaimableVouchersProperty(): Collection
    {
        $user = auth()->user();

        $vouchers = $this->activeVouchers->filter(fn (Voucher $v) => $v->isValid());

        if (!$user) {
            return $vouchers->values();
        }

        $claimedIds = $user->vouchers()->pluck('vouchers.id')->all();

        return $vouchers
            ->reject(fn (Voucher $v) => in_array($v->id, $claimedIds, true))
            ->values();
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
        return view('livewire.member.vouchers-v2.browse');
    }
}


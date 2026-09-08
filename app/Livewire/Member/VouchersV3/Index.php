<?php

namespace App\Livewire\Member\VouchersV3;

use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use App\Services\PointsService;
use App\Services\QrCodeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public string $tab = 'active';

    public bool $showQr = false;
    public ?string $qrImage = null;
    public ?string $qrVoucherName = null;
    public ?string $qrVoucherCode = null;
    public ?string $qrRedeemableAt = null;

    public bool $showClaimConfirm = false;
    public ?int $pendingClaimId = null;
    public ?string $pendingClaimType = null;
    public ?string $confirmName = null;
    public ?string $confirmMerchantName = null;
    public ?string $confirmDiscountLabel = null;
    public ?int $confirmPointsCost = null;
    public ?string $confirmValidUntil = null;
    public ?int $confirmUserPoints = null;

    protected $listeners = [
        'voucher-redeemed' => 'handleVoucherRedeemed',
    ];

    public function mount(): void
    {
        $tab = request()->query('tab');
        if (in_array($tab, ['active', 'claimed', 'redeemed'], true)) {
            $this->tab = $tab;
        }
    }

    public function openClaimConfirm(int $id, string $type): void
    {
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('notify', type: 'error', message: 'You must be logged in to claim vouchers.');
            return;
        }

        if ($type === 'admin') {
            $voucher = AdminVoucher::query()->with('merchants')->whereKey($id)->first();
            if (!$voucher || !$voucher->isValid() || !$voucher->isVisibleToMember($user)) {
                $this->dispatch('notify', type: 'error', message: 'Voucher is not available.');
                return;
            }
            if ($user->hasActiveAdminVoucher($id)) {
                $this->dispatch('notify', type: 'info', message: 'You already claimed this admin voucher.');
                return;
            }
            if ($user->total_points < $voucher->points_cost) {
                $this->dispatch('notify', type: 'error', message: 'Insufficient points. You need ' . number_format($voucher->points_cost) . ' points to claim this voucher.');
                return;
            }

            $this->confirmName = $voucher->name;
            $this->confirmMerchantName = $voucher->merchants->pluck('name')->filter()->join(', ') ?: 'All Sellers';
            $this->confirmDiscountLabel = null;
            $this->confirmPointsCost = (int) $voucher->points_cost;
            $this->confirmValidUntil = $voucher->valid_until
                ? $voucher->valid_until->format('d/m/Y g:i A')
                : null;
            $this->confirmUserPoints = (int) $user->total_points;
        } else {
            $voucher = Voucher::query()->with('merchant')->whereKey($id)->first();
            if (!$voucher || !$voucher->isValid() || !$voucher->isVisibleToMember($user)) {
                $this->dispatch('notify', type: 'error', message: 'Voucher is not available.');
                return;
            }
            if ($user->vouchers()->where('vouchers.id', $id)->exists()) {
                $this->dispatch('notify', type: 'info', message: 'You already claimed this voucher.');
                return;
            }

            if ($voucher->discount_type === 'percentage') {
                $discount = rtrim(rtrim((string) $voucher->discount_value, '0'), '.') . '% off';
            } else {
                $discount = '$' . number_format((float) $voucher->discount_value, 2) . ' off';
            }

            $this->confirmName = $voucher->name;
            $this->confirmMerchantName = $voucher->merchant?->name ?: 'All Sellers';
            $this->confirmDiscountLabel = $discount;
            $this->confirmPointsCost = null;
            $this->confirmValidUntil = $voucher->valid_until
                ? $voucher->valid_until->format('d/m/Y g:i A')
                : null;
            $this->confirmUserPoints = (int) $user->total_points;
        }

        $this->pendingClaimId = $id;
        $this->pendingClaimType = $type === 'admin' ? 'admin' : 'merchant';
        $this->showClaimConfirm = true;
    }

    public function closeClaimConfirm(): void
    {
        $this->showClaimConfirm = false;
        $this->pendingClaimId = null;
        $this->pendingClaimType = null;
        $this->confirmName = null;
        $this->confirmMerchantName = null;
        $this->confirmDiscountLabel = null;
        $this->confirmPointsCost = null;
        $this->confirmValidUntil = null;
        $this->confirmUserPoints = null;
    }

    public function confirmClaim(): void
    {
        $id = $this->pendingClaimId;
        $type = $this->pendingClaimType;

        if ($type === 'admin' && $id) {
            $this->claimAdminVoucher($id);
        } elseif ($type === 'merchant' && $id) {
            $this->claim($id);
        }

        $this->closeClaimConfirm();
    }

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

        if ($user->vouchers()->where('vouchers.id', $voucherId)->exists()) {
            $this->dispatch('notify', type: 'info', message: 'You already claimed this voucher.');
            return;
        }

        $user->vouchers()->attach($voucherId, [
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);

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

        if ($user->hasActiveAdminVoucher($adminVoucherId)) {
            $this->dispatch('notify', type: 'info', message: 'You already claimed this admin voucher.');
            return;
        }

        if ($user->total_points < $adminVoucher->points_cost) {
            $this->dispatch('notify', type: 'error', message: 'Insufficient points. You need ' . number_format($adminVoucher->points_cost) . ' points to claim this voucher.');
            return;
        }

        try {
            DB::transaction(function () use ($user, $adminVoucher) {
                app(PointsService::class)->deductAdminVoucherClaim($user, $adminVoucher);

                $user->claimAdminVoucherAssignment($adminVoucher);

                $adminVoucher->increment('usage_count');
            });

            $user->refresh();
            $this->dispatch('notify', type: 'success', message: 'Admin voucher claimed! ' . number_format($adminVoucher->points_cost) . ' points deducted.');
            $this->dispatch('points-updated');
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to claim voucher: ' . $e->getMessage());
        }
    }

    public function showClaimedQr(string $voucherCode, string $type): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        if ($type === 'merchant') {
            $voucher = $user->vouchers()
                ->with('merchant')
                ->where('vouchers.voucher_code', $voucherCode)
                ->wherePivot('status', 'claimed')
                ->first();

            if (!$voucher) {
                $this->dispatch('notify', type: 'error', message: 'Voucher is no longer claimable for QR redemption.');
                return;
            }

            $this->qrVoucherName = $voucher->name;
            $this->qrVoucherCode = $voucher->voucher_code;
            $this->qrRedeemableAt = $voucher->merchant?->name;
        } else {
            $voucher = $user->adminVouchers()
                ->with('merchants')
                ->where('admin_vouchers.voucher_code', $voucherCode)
                ->wherePivot('status', 'claimed')
                ->first();

            if (!$voucher) {
                $this->dispatch('notify', type: 'error', message: 'Voucher is no longer claimable for QR redemption.');
                return;
            }

            $this->qrVoucherName = $voucher->name;
            $this->qrVoucherCode = $voucher->voucher_code;
            $this->qrRedeemableAt = $voucher->merchants->pluck('name')->join(', ');
        }

        $this->qrImage = app(QrCodeService::class)->generateQrCodeImage($voucherCode . '_' . $user->qr_code, 420);
        $this->showQr = true;
    }

    public function closeQr(): void
    {
        $this->showQr = false;
    }

    public function handleVoucherRedeemed($data): void
    {
        $currentUser = auth()->user();
        if (!$currentUser || !isset($data['member_id']) || (int) $data['member_id'] !== (int) $currentUser->id) {
            return;
        }

        $this->dispatch(
            'notify',
            type: $data['type'] ?? 'success',
            message: $data['message'] ?? 'Voucher redemption status updated.'
        );
    }

    /**
     * Admin/dev testing shortcut to mark a claimed voucher redeemed.
     *
     * Never available in production. Members never see or invoke this in
     * deployed environments. Local/testing allows the signed-in tester
     * (usually a member account on this page) to skip merchant QR scanning.
     */
    public function canUseAdminTestRedeem(): bool
    {
        if (app()->isProduction()) {
            return false;
        }

        $user = auth()->user();
        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return app()->environment(['local', 'testing']);
    }

    public function adminTestRedeem(int $id, string $type): void
    {
        if (! $this->canUseAdminTestRedeem()) {
            $this->dispatch('notify', type: 'error', message: 'This action is not available.');
            return;
        }

        $user = auth()->user();
        if (! $user) {
            $this->dispatch('notify', type: 'error', message: 'You must be logged in.');
            return;
        }

        $type = $type === 'admin' ? 'admin' : 'merchant';

        try {
            if ($type === 'admin') {
                $this->adminTestRedeemAdminVoucher($user, $id);
            } else {
                $this->adminTestRedeemMerchantVoucher($user, $id);
            }
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to redeem voucher: ' . $e->getMessage());
        }
    }

    private function adminTestRedeemMerchantVoucher(User $user, int $voucherId): void
    {
        $voucher = $user->vouchers()
            ->where('vouchers.id', $voucherId)
            ->wherePivot('status', 'claimed')
            ->first();

        if (! $voucher) {
            $this->dispatch('notify', type: 'error', message: 'Claimed voucher not found.');
            return;
        }

        DB::transaction(function () use ($user, $voucher) {
            $user->vouchers()->updateExistingPivot($voucher->id, [
                'status' => 'redeemed',
                'redeemed_at' => now(),
            ]);

            app(PointsService::class)->awardVoucherRedeem($user, $voucher);
        });

        $this->dispatch('notify', type: 'success', message: 'Voucher marked as redeemed (admin test).');
    }

    private function adminTestRedeemAdminVoucher(User $user, int $adminVoucherId): void
    {
        $voucher = $user->adminVouchers()
            ->where('admin_vouchers.id', $adminVoucherId)
            ->wherePivot('status', 'claimed')
            ->first();

        if (! $voucher) {
            $this->dispatch('notify', type: 'error', message: 'Claimed voucher not found.');
            return;
        }

        DB::transaction(function () use ($user, $voucher) {
            $user->adminVouchers()->updateExistingPivot($voucher->id, [
                'status' => 'redeemed',
                'redeemed_at' => now(),
            ]);
        });

        $this->dispatch('notify', type: 'success', message: 'Voucher marked as redeemed (admin test).');
    }

    public function getActiveItemsProperty(): Collection
    {
        $user = auth()->user();
        if (!$user) {
            return collect();
        }

        $claimedVoucherIds = $user->vouchers()->pluck('vouchers.id')->all();
        $claimedAdminVoucherIds = $user->adminVouchers()
            ->wherePivotIn('status', ['claimed', 'redeemed'])
            ->pluck('admin_vouchers.id')
            ->all();

        $merchantItems = Voucher::query()
            ->with('merchant')
            ->valid()
            ->latest()
            ->get()
            ->reject(fn (Voucher $voucher) => in_array($voucher->id, $claimedVoucherIds, true) || ! $voucher->isVisibleToMember($user))
            ->map(function (Voucher $voucher) {
                return (object) [
                    'id' => $voucher->id,
                    'type' => 'merchant',
                    'voucher_code' => $voucher->voucher_code,
                    'name' => $voucher->name,
                    'image_url' => $voucher->image_url,
                    'merchant_name' => $voucher->merchant?->name,
                    'description' => $voucher->description,
                    'discount_type' => $voucher->discount_type,
                    'discount_value' => $voucher->discount_value,
                    'min_purchase' => $voucher->min_purchase,
                    'points_cost' => null,
                    'valid_until' => $voucher->valid_until,
                    'created_at' => $voucher->created_at,
                ];
            });

        $adminItems = AdminVoucher::query()
            ->with('merchants')
            ->valid()
            ->latest()
            ->get()
            ->reject(fn (AdminVoucher $voucher) => in_array($voucher->id, $claimedAdminVoucherIds, true) || ! $voucher->isVisibleToMember($user))
            ->map(function (AdminVoucher $voucher) {
                return (object) [
                    'id' => $voucher->id,
                    'type' => 'admin',
                    'voucher_code' => $voucher->voucher_code,
                    'name' => $voucher->name,
                    'image_url' => $voucher->image_url,
                    'merchant_name' => $voucher->merchants->pluck('name')->join(', '),
                    'description' => $voucher->description,
                    'discount_type' => null,
                    'discount_value' => null,
                    'min_purchase' => null,
                    'points_cost' => $voucher->points_cost,
                    'valid_until' => $voucher->valid_until,
                    'created_at' => $voucher->created_at,
                ];
            });

        $userPoints = (int) ($user->total_points ?? 0);

        return $merchantItems
            ->concat($adminItems)
            ->sortBy([
                fn ($item) => ($item->type === 'admin' && $userPoints < (int) ($item->points_cost ?? 0)) ? 1 : 0,
                fn ($item) => $item->created_at ? -strtotime((string) $item->created_at) : 0,
            ])
            ->values();
    }

    public function getClaimedItemsProperty(): Collection
    {
        $user = auth()->user();
        if (!$user) {
            return collect();
        }

        $merchantItems = $user->vouchers()
            ->with('merchant')
            ->wherePivot('status', 'claimed')
            ->where(function ($query) {
                $query->whereNull('vouchers.valid_until')
                    ->orWhere('vouchers.valid_until', '>=', now());
            })
            ->latest('user_voucher.claimed_at')
            ->get()
            ->map(function (Voucher $voucher) {
                return (object) [
                    'id' => $voucher->id,
                    'type' => 'merchant',
                    'voucher_code' => $voucher->voucher_code,
                    'name' => $voucher->name,
                    'image_url' => $voucher->image_url,
                    'merchant_name' => $voucher->merchant?->name,
                    'description' => $voucher->description,
                    'claimed_at' => $voucher->pivot->claimed_at,
                    'valid_until' => $voucher->valid_until,
                    'points_cost' => null,
                ];
            });

        $adminItems = $user->adminVouchers()
            ->with('merchants')
            ->wherePivot('status', 'claimed')
            ->where(function ($query) {
                $query->whereNull('admin_vouchers.valid_until')
                    ->orWhere('admin_vouchers.valid_until', '>=', now());
            })
            ->latest('user_admin_voucher.claimed_at')
            ->get()
            ->map(function (AdminVoucher $voucher) {
                return (object) [
                    'id' => $voucher->id,
                    'type' => 'admin',
                    'voucher_code' => $voucher->voucher_code,
                    'name' => $voucher->name,
                    'image_url' => $voucher->image_url,
                    'merchant_name' => $voucher->merchants->pluck('name')->join(', '),
                    'description' => $voucher->description,
                    'claimed_at' => $voucher->pivot->claimed_at,
                    'valid_until' => $voucher->valid_until,
                    'points_cost' => $voucher->points_cost,
                ];
            });

        return $merchantItems
            ->concat($adminItems)
            ->sortByDesc(fn ($item) => $item->claimed_at ? strtotime((string) $item->claimed_at) : 0)
            ->values();
    }

    public function getRedeemedItemsProperty(): Collection
    {
        $user = auth()->user();
        if (!$user) {
            return collect();
        }

        $merchantItems = $user->vouchers()
            ->with('merchant')
            ->wherePivot('status', 'redeemed')
            ->latest('user_voucher.redeemed_at')
            ->get()
            ->map(function (Voucher $voucher) {
                return (object) [
                    'id' => $voucher->id,
                    'type' => 'merchant',
                    'voucher_code' => $voucher->voucher_code,
                    'name' => $voucher->name,
                    'image_url' => $voucher->image_url,
                    'merchant_name' => $voucher->merchant?->name,
                    'description' => $voucher->description,
                    'valid_until' => $voucher->valid_until,
                    'redeemed_at' => $voucher->pivot->redeemed_at,
                    'redeemed_at_merchant' => $voucher->merchant?->name,
                    'points_cost' => null,
                ];
            });

        $adminItems = $user->adminVouchers()
            ->with('merchants')
            ->wherePivot('status', 'redeemed')
            ->latest('user_admin_voucher.redeemed_at')
            ->get()
            ->map(function (AdminVoucher $voucher) {
                $redeemedAtMerchant = null;
                if ($voucher->pivot->redeemed_at_merchant_id) {
                    $redeemedAtMerchant = Merchant::find($voucher->pivot->redeemed_at_merchant_id)?->name;
                }

                return (object) [
                    'id' => $voucher->id,
                    'type' => 'admin',
                    'voucher_code' => $voucher->voucher_code,
                    'name' => $voucher->name,
                    'image_url' => $voucher->image_url,
                    'merchant_name' => $voucher->merchants->pluck('name')->join(', '),
                    'description' => $voucher->description,
                    'valid_until' => $voucher->valid_until,
                    'redeemed_at' => $voucher->pivot->redeemed_at,
                    'redeemed_at_merchant' => $redeemedAtMerchant,
                    'points_cost' => $voucher->points_cost,
                ];
            });

        return $merchantItems
            ->concat($adminItems)
            ->sortByDesc(fn ($item) => $item->redeemed_at ? strtotime((string) $item->redeemed_at) : 0)
            ->values();
    }

    public function render()
    {
        return view('livewire.member.vouchers-v3.index');
    }
}

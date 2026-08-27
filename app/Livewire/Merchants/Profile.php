<?php

namespace App\Livewire\Merchants;

use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Models\Voucher;
use App\Services\QrCodeService;
use Illuminate\Support\Collection;
use Livewire\Component;

class Profile extends Component
{
    public $merchantCode;
    public $merchant;

    protected $listeners = ['refresh-users' => 'loadMerchant'];

    public function mount($merchant_code)
    {
        $this->merchantCode = $merchant_code;
        $this->loadMerchant();
    }

    public function loadMerchant()
    {
        $this->merchant = Merchant::with([
            'vouchers' => function ($query) {
                $query->latest();
            },
            'adminVouchers' => function ($query) {
                $query->latest();
            },
            'users',
        ])->where('merchant_code', $this->merchantCode)->firstOrFail();

        $this->merchant->setRelation(
            'vouchers',
            $this->sortVouchersByStatus($this->merchant->vouchers, isAdmin: false)
        );
        $this->merchant->setRelation(
            'adminVouchers',
            $this->sortVouchersByStatus($this->merchant->adminVouchers, isAdmin: true)
        );
    }

    /**
     * Active first, then Full, Inactive / other, Expired last.
     * Within the same status, keep newest first.
     */
    protected function sortVouchersByStatus(Collection $vouchers, bool $isAdmin): Collection
    {
        return $vouchers
            ->sortBy(function (Voucher|AdminVoucher $voucher) use ($isAdmin) {
                return [
                    $this->voucherStatusRank($voucher, $isAdmin),
                    -($voucher->created_at?->getTimestamp() ?? 0),
                ];
            })
            ->values();
    }

    protected function voucherStatusRank(Voucher|AdminVoucher $voucher, bool $isAdmin): int
    {
        $statusReason = $voucher->getStatusReason();
        $isExpired = $statusReason === 'Expired' || ($voucher->valid_until && $voucher->valid_until->isPast());
        $isInactive = ! $voucher->is_active;
        $isFull = $isAdmin && $voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit;

        if ($isExpired) {
            return 3;
        }

        if ($isFull) {
            return 1;
        }

        if ($isInactive) {
            return 2;
        }

        if ($voucher->isValid()) {
            return 0;
        }

        return 2;
    }

    public function render()
    {
        $qrCodeService = app(QrCodeService::class);
        $qrCodeImage = $qrCodeService->generateQrCodeImage($this->merchant->merchant_code, 400);

        return view('livewire.merchants.profile', [
            'merchant' => $this->merchant,
            'qrCodeImage' => $qrCodeImage,
        ])->layout('layouts.app');
    }
}

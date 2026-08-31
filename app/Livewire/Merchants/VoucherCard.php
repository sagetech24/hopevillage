<?php

namespace App\Livewire\Merchants;

use App\Models\AdminVoucher;
use App\Models\Voucher;
use App\Services\QrCodeService;
use Livewire\Component;

class VoucherCard extends Component
{
    public $voucherCode;

    public $type; // 'merchant' or 'admin'

    public $voucher;

    public $claimedCount = 0;

    public $redeemedCount = 0;

    public $qrCodeImage;

    /** When true (merchant portal), admin vouchers open a detail modal instead of navigating. */
    public bool $openAsModal = false;

    public bool $showDetailModal = false;

    public function mount($voucherCode, $type, bool $openAsModal = false)
    {
        $this->voucherCode = $voucherCode;
        $this->type = $type;
        $this->openAsModal = $openAsModal;
        $this->loadVoucher();
    }

    public function openDetailModal(): void
    {
        if ($this->openAsModal && $this->type === 'admin' && $this->voucher) {
            $this->showDetailModal = true;
        }
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
    }

    public function loadVoucher()
    {
        if ($this->type === 'admin') {
            $this->voucher = AdminVoucher::with('merchants')
                ->where('voucher_code', $this->voucherCode)
                ->first();
        } else {
            $this->voucher = Voucher::where('voucher_code', $this->voucherCode)->first();
        }

        if ($this->voucher) {
            $claimedOnlyCount = $this->voucher->users()
                ->wherePivot('status', 'claimed')
                ->count();
            $this->redeemedCount = $this->voucher->users()
                ->wherePivot('status', 'redeemed')
                ->count();
            // Claimed = currently claimed + already redeemed (same as admin voucher cards)
            $this->claimedCount = $claimedOnlyCount + $this->redeemedCount;

            $qrCodeService = app(QrCodeService::class);
            $this->qrCodeImage = $qrCodeService->generateQrCodeImage($this->voucher->voucher_code, 200);
        }
    }

    public function render()
    {
        return view('livewire.merchants.voucher-card');
    }
}

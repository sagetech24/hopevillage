<?php

namespace App\Livewire\Merchant\Vouchers;

use App\Models\Voucher;
use App\Services\QrCodeService;
use Livewire\Component;

class Profile extends Component
{
    public $voucherCode;
    public $voucher;
    public $merchant;

    public function mount($voucher_code)
    {
        $this->voucherCode = $voucher_code;
        $this->merchant = auth()->user()->currentMerchant();
        
        if (!$this->merchant) {
            $this->redirect(route('merchant.dashboard'));

            return;
        }

        $this->loadVoucher();
    }

    public function loadVoucher()
    {
        $this->voucher = Voucher::where('voucher_code', $this->voucherCode)
            ->where('merchant_id', $this->merchant->id)
            ->firstOrFail();
    }

    public function getClaimedMembersProperty()
    {
        return $this->voucher->users()
            ->wherePivot('status', 'claimed')
            ->orderByPivot('claimed_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'fin' => $user->fin,
                    'claimed_at' => $user->pivot->claimed_at,
                ];
            });
    }

    public function getRedeemedMembersProperty()
    {
        return $this->voucher->users()
            ->wherePivot('status', 'redeemed')
            ->orderByPivot('redeemed_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'fin' => $user->fin,
                    'claimed_at' => $user->pivot->claimed_at,
                    'redeemed_at' => $user->pivot->redeemed_at,
                ];
            });
    }

    public function render()
    {
        $qrCodeService = app(QrCodeService::class);
        $qrCodeImage = $qrCodeService->generateQrCodeImage($this->voucher->voucher_code, 400);

        return view('livewire.merchant.vouchers.profile', [
            'voucher' => $this->voucher,
            'claimedMembers' => $this->claimedMembers,
            'redeemedMembers' => $this->redeemedMembers,
            'qrCodeImage' => $qrCodeImage,
        ])->layout('layouts.app');
    }
}

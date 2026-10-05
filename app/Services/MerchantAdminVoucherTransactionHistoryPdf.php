<?php

namespace App\Services;

use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Support\MemberNameMask;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class MerchantAdminVoucherTransactionHistoryPdf
{
    /**
     * Redemption history for one Hope Village voucher at one store.
     * Shared by the merchant portal and the admin merchant-invoice row.
     */
    public function stream(Merchant $merchant, AdminVoucher $voucher): Response
    {
        ini_set('memory_limit', '512M');

        $costPerVoucher = $voucher->costPerVoucher();

        $transactions = $voucher->users()
            ->wherePivot('status', 'redeemed')
            ->wherePivot('redeemed_at_merchant_id', $merchant->id)
            ->orderByPivot('redeemed_at')
            ->get()
            ->values()
            ->map(function ($user, int $index) use ($costPerVoucher) {
                return (object) [
                    'row_number' => $index + 1,
                    'member_name' => MemberNameMask::mask($user->name),
                    'member_code' => $user->qr_code,
                    'redeemed_at' => $user->pivot->redeemed_at,
                    'amount' => $costPerVoucher,
                ];
            });

        $pdf = Pdf::loadView('pdf.merchant-admin-voucher-transaction-history', [
            'merchant' => $merchant,
            'voucher' => $voucher,
            'transactions' => $transactions,
            'costPerVoucher' => $costPerVoucher,
            'totalAmount' => round($costPerVoucher * $transactions->count(), 2),
            'logoSrc' => $this->logoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'transaction-history-%s-%s.pdf',
            Str::slug($merchant->name ?? 'merchant'),
            Str::slug($voucher->name ?? 'voucher')
        );

        return $pdf->stream($filename);
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('hv-logo.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}

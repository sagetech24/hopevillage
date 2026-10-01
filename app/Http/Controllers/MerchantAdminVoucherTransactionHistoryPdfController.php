<?php

namespace App\Http\Controllers;

use App\Models\AdminVoucher;
use App\Support\MemberNameMask;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantAdminVoucherTransactionHistoryPdfController extends Controller
{
    /**
     * Generate a PDF of redemptions for this Hope Village voucher at the current store.
     */
    public function __invoke(Request $request, string $voucher_code)
    {
        ini_set('memory_limit', '512M');

        $merchant = $request->user()?->currentMerchant();
        abort_unless($merchant, 403);

        $voucher = AdminVoucher::query()
            ->withTrashed()
            ->where('voucher_code', $voucher_code)
            ->whereHas('merchants', function ($q) use ($merchant) {
                $q->where('merchants.id', $merchant->id);
            })
            ->firstOrFail();

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

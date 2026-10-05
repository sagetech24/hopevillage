<?php

namespace App\Http\Controllers;

use App\Models\AdminVoucher;
use App\Services\MerchantAdminVoucherTransactionHistoryPdf;
use Illuminate\Http\Request;

class MerchantAdminVoucherTransactionHistoryPdfController extends Controller
{
    /**
     * Generate a PDF of redemptions for this Hope Village voucher at the current store.
     */
    public function __invoke(Request $request, string $voucher_code, MerchantAdminVoucherTransactionHistoryPdf $pdf)
    {
        $merchant = $request->user()?->currentMerchant();
        abort_unless($merchant, 403);

        $voucher = AdminVoucher::query()
            ->withTrashed()
            ->where('voucher_code', $voucher_code)
            ->whereHas('merchants', function ($q) use ($merchant) {
                $q->where('merchants.id', $merchant->id);
            })
            ->firstOrFail();

        return $pdf->stream($merchant, $voucher);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AdminVoucher;
use App\Models\MerchantAdminVoucherInvoice;
use App\Services\MerchantAdminVoucherTransactionHistoryPdf;

class AdminMerchantVoucherTransactionHistoryPdfController extends Controller
{
    /**
     * Same store transaction-history PDF the merchant prints, for this invoice's voucher.
     */
    public function __invoke(
        MerchantAdminVoucherInvoice $invoice,
        MerchantAdminVoucherTransactionHistoryPdf $pdf,
    ) {
        $invoice->load('merchant');
        abort_unless($invoice->merchant, 404);

        $voucher = AdminVoucher::query()
            ->withTrashed()
            ->findOrFail($invoice->admin_voucher_id);

        return $pdf->stream($invoice->merchant, $voucher);
    }
}

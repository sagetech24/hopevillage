<?php

namespace App\Http\Controllers;

use App\Models\AdminVoucher;
use App\Models\AdminVoucherLedgerEntry;
use App\Models\AdminVoucherReimbursement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantAdminVoucherReimbursementPdfController extends Controller
{
    /**
     * Generate a PDF of all Hope Village reimbursements for this admin voucher at the current store.
     */
    public function __invoke(Request $request, string $voucher_code)
    {
        $merchant = $request->user()?->currentMerchant();
        abort_unless($merchant, 403);

        $voucher = AdminVoucher::query()
            ->where('voucher_code', $voucher_code)
            ->whereHas('merchants', function ($q) use ($merchant) {
                $q->where('merchants.id', $merchant->id);
            })
            ->firstOrFail();

        $entries = AdminVoucherLedgerEntry::query()
            ->where('merchant_id', $merchant->id)
            ->where('admin_voucher_id', $voucher->id)
            ->with('adminVoucher')
            ->withSum('reimbursements', 'amount')
            ->orderBy('period_month')
            ->get();

        $reimbursements = AdminVoucherReimbursement::query()
            ->whereHas('ledgerEntry', function ($q) use ($merchant, $voucher) {
                $q->where('merchant_id', $merchant->id)
                    ->where('admin_voucher_id', $voucher->id);
            })
            ->with(['ledgerEntry', 'createdBy'])
            ->orderBy('reimbursed_at')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(function ($reimbursement, int $index) {
                $reimbursement->row_number = $index + 1;

                return $reimbursement;
            });

        $totalDispensed = round((float) $entries->sum(fn (AdminVoucherLedgerEntry $entry) => $entry->computedTotalDispensed()), 2);
        $totalReimbursed = round((float) $reimbursements->sum('amount'), 2);

        $pdf = Pdf::loadView('pdf.merchant-admin-voucher-reimbursements', [
            'merchant' => $merchant,
            'voucher' => $voucher,
            'reimbursements' => $reimbursements,
            'totalDispensed' => $totalDispensed,
            'totalReimbursed' => $totalReimbursed,
            'outstanding' => round($totalDispensed - $totalReimbursed, 2),
            'logoSrc' => $this->logoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'reimbursement-history-%s-%s.pdf',
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

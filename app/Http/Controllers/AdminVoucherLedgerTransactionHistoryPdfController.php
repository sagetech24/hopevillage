<?php

namespace App\Http\Controllers;

use App\Models\AdminVoucherLedgerEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminVoucherLedgerTransactionHistoryPdfController extends Controller
{
    /**
     * Generate a PDF of all user_admin_voucher redemptions for the given ledger entry.
     * Shows all members who redeemed the admin voucher at the merchant during the voucher's period month.
     */
    public function __invoke(Request $request, AdminVoucherLedgerEntry $entry)
    {
        // DomPDF's table cellmap grows quickly with hundreds of redemption rows.
        ini_set('memory_limit', '512M');

        $entry->load(['merchant', 'adminVoucher', 'reimbursements']);

        $periodStart = $entry->period_month->copy()->startOfMonth();
        $periodEnd = $entry->period_month->copy()->endOfMonth()->endOfDay();

        $transactions = DB::table('user_admin_voucher')
            ->join('admin_vouchers', 'admin_vouchers.id', '=', 'user_admin_voucher.admin_voucher_id')
            ->join('users', 'users.id', '=', 'user_admin_voucher.user_id')
            ->where('user_admin_voucher.redeemed_at_merchant_id', $entry->merchant_id)
            ->where('user_admin_voucher.admin_voucher_id', $entry->admin_voucher_id)
            ->where('user_admin_voucher.status', 'redeemed')
            ->whereBetween('user_admin_voucher.redeemed_at', [$periodStart, $periodEnd])
            ->whereNull('admin_vouchers.deleted_at')
            ->whereNull('users.deleted_at')
            ->select(
                'users.name as member_name',
                'users.qr_code as member_code',
                'admin_vouchers.name as voucher_name',
                'admin_vouchers.voucher_code',
                'admin_vouchers.amount_cost',
                'user_admin_voucher.redeemed_at'
            )
            ->orderBy('user_admin_voucher.redeemed_at')
            ->get()
            ->values()
            ->map(function ($tx, int $index) {
                $tx->row_number = $index + 1;

                return $tx;
            });

        $pdf = Pdf::loadView('pdf.admin-voucher-ledger-transaction-history', [
            'entry' => $entry,
            'transactions' => $transactions,
            'reimbursements' => $entry->reimbursements,
            'logoSrc' => $this->logoDataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'transaction-history-%s-%s-%s.pdf',
            Str::slug($entry->merchant?->name ?? 'merchant'),
            Str::slug($entry->adminVoucher?->name ?? 'voucher'),
            $entry->period_month->format('Y-m')
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

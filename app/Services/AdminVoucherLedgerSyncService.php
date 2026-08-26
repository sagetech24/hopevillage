<?php

namespace App\Services;

use App\Models\AdminVoucherLedgerEntry;
use Illuminate\Support\Facades\DB;

class AdminVoucherLedgerSyncService
{
    /**
     * Sync ledger entries from redemptions.
     * Aggregates user_admin_voucher (status=redeemed) by merchant, admin_voucher, and month,
     * then upserts into admin_voucher_ledger_entries.
     */
    public function sync(): int
    {
        $rows = DB::table('user_admin_voucher')
            ->join('admin_vouchers', 'admin_vouchers.id', '=', 'user_admin_voucher.admin_voucher_id')
            ->join('merchants', 'merchants.id', '=', 'user_admin_voucher.redeemed_at_merchant_id')
            ->where('user_admin_voucher.status', 'redeemed')
            ->whereNotNull('user_admin_voucher.redeemed_at_merchant_id')
            ->whereNotNull('user_admin_voucher.redeemed_at')
            ->whereNull('admin_vouchers.deleted_at')
            ->whereNull('merchants.deleted_at')
            ->selectRaw('
                user_admin_voucher.redeemed_at_merchant_id as merchant_id,
                user_admin_voucher.admin_voucher_id,
                DATE_FORMAT(user_admin_voucher.redeemed_at, "%Y-%m-01") as period_month,
                COUNT(*) as total_redemptions,
                COUNT(*) * admin_vouchers.amount_cost * admin_vouchers.points_cost as total_amount_dispensed
            ')
            ->groupBy(
                'user_admin_voucher.redeemed_at_merchant_id',
                'user_admin_voucher.admin_voucher_id',
                DB::raw('DATE_FORMAT(user_admin_voucher.redeemed_at, "%Y-%m-01")'),
                'admin_vouchers.amount_cost',
                'admin_vouchers.points_cost'
            )
            ->get();

        $count = 0;
        foreach ($rows as $row) {
            AdminVoucherLedgerEntry::updateOrCreate(
                [
                    'merchant_id' => $row->merchant_id,
                    'admin_voucher_id' => $row->admin_voucher_id,
                    'period_month' => $row->period_month,
                ],
                [
                    'total_redemptions' => $row->total_redemptions,
                    'total_amount_dispensed' => $row->total_amount_dispensed,
                ]
            );
            $count++;
        }

        return $count;
    }
}

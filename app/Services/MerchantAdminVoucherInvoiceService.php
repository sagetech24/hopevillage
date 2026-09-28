<?php

namespace App\Services;

use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Models\MerchantAdminVoucherInvoice;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchantAdminVoucherInvoiceService
{
    /**
     * Finished or inactive admin vouchers assigned to this store, with redemption totals.
     *
     * @return Collection<int, array{voucher: AdminVoucher, redeemed_count: int, cost_per_voucher: float, amount: float, invoice: ?MerchantAdminVoucherInvoice}>
     */
    public function billableVouchers(Merchant $merchant): Collection
    {
        $vouchers = $merchant->adminVouchers()
            ->withTrashed()
            ->where(function ($query) {
                $query->where('admin_vouchers.is_active', false)
                    ->orWhereNotNull('admin_vouchers.deleted_at')
                    ->orWhere(function ($expired) {
                        $expired->whereNotNull('admin_vouchers.valid_until')
                            ->where('admin_vouchers.valid_until', '<', now());
                    });
            })
            ->orderByDesc('admin_vouchers.valid_until')
            ->orderByDesc('admin_vouchers.id')
            ->get();

        if ($vouchers->isEmpty()) {
            return collect();
        }

        $ids = $vouchers->pluck('id');

        $redeemedCounts = DB::table('user_admin_voucher')
            ->select('admin_voucher_id', DB::raw('COUNT(*) as redeemed_count'))
            ->where('redeemed_at_merchant_id', $merchant->id)
            ->where('status', 'redeemed')
            ->whereIn('admin_voucher_id', $ids)
            ->groupBy('admin_voucher_id')
            ->pluck('redeemed_count', 'admin_voucher_id');

        $invoices = MerchantAdminVoucherInvoice::query()
            ->where('merchant_id', $merchant->id)
            ->whereIn('admin_voucher_id', $ids)
            ->get()
            ->keyBy('admin_voucher_id');

        return $vouchers->map(function (AdminVoucher $voucher) use ($redeemedCounts, $invoices) {
            $redeemedCount = (int) ($redeemedCounts[$voucher->id] ?? 0);
            $costPerVoucher = $voucher->costPerVoucher();

            return [
                'voucher' => $voucher,
                'redeemed_count' => $redeemedCount,
                'cost_per_voucher' => $costPerVoucher,
                'amount' => round($redeemedCount * $costPerVoucher, 2),
                'invoice' => $invoices->get($voucher->id),
            ];
        })->values();
    }

    /**
     * @param  array{bank_name: string, account_name: string, account_number: string}  $bankAccount
     */
    public function create(
        Merchant $merchant,
        User $generatedBy,
        int $adminVoucherId,
        ?string $invoiceNumber,
        array $bankAccount,
    ): MerchantAdminVoucherInvoice {
        return DB::transaction(function () use ($merchant, $generatedBy, $adminVoucherId, $invoiceNumber, $bankAccount) {
            $row = $this->billableVouchers($merchant)->first(
                fn (array $item) => $item['voucher']->id === $adminVoucherId
            );

            if ($row === null) {
                throw ValidationException::withMessages([
                    'invoiceNumber' => 'This voucher cannot be invoiced.',
                ]);
            }

            if ($row['redeemed_count'] < 1) {
                throw ValidationException::withMessages([
                    'invoiceNumber' => 'This voucher has no redemptions to invoice.',
                ]);
            }

            if ($row['invoice'] !== null) {
                throw ValidationException::withMessages([
                    'invoiceNumber' => 'An invoice already exists for this voucher.',
                ]);
            }

            $number = trim((string) $invoiceNumber);
            if ($number === '') {
                $number = MerchantAdminVoucherInvoice::nextInvoiceNumber();
            } elseif (MerchantAdminVoucherInvoice::query()->where('invoice_number', $number)->exists()) {
                throw ValidationException::withMessages([
                    'invoiceNumber' => 'This invoice number is already in use.',
                ]);
            }

            /** @var AdminVoucher $voucher */
            $voucher = $row['voucher'];

            return MerchantAdminVoucherInvoice::query()->create([
                'invoice_number' => $number,
                'merchant_id' => $merchant->id,
                'admin_voucher_id' => $voucher->id,
                'generated_by' => $generatedBy->id,
                'voucher_name' => $voucher->name,
                'voucher_code' => $voucher->voucher_code,
                'valid_from' => $voucher->valid_from,
                'valid_until' => $voucher->valid_until,
                'redeemed_count' => $row['redeemed_count'],
                'cost_per_voucher' => $row['cost_per_voucher'],
                'amount' => $row['amount'],
                'bank_account' => [
                    'bank_name' => $bankAccount['bank_name'],
                    'account_name' => $bankAccount['account_name'],
                    'account_number' => $bankAccount['account_number'],
                ],
                'generated_at' => now(),
            ]);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantAdminVoucherInvoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'merchant_id',
        'admin_voucher_id',
        'generated_by',
        'voucher_name',
        'voucher_code',
        'valid_from',
        'valid_until',
        'redeemed_count',
        'cost_per_voucher',
        'amount',
        'bank_account',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'redeemed_count' => 'integer',
            'cost_per_voucher' => 'decimal:2',
            'amount' => 'decimal:2',
            'bank_account' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function adminVoucher(): BelongsTo
    {
        return $this->belongsTo(AdminVoucher::class, 'admin_voucher_id')->withTrashed();
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function validityLabel(): string
    {
        $from = $this->valid_from?->format('d M Y');
        $until = $this->valid_until?->format('d M Y');

        if ($from && $until) {
            return $from.' – '.$until;
        }

        return $from ?? $until ?? '—';
    }

    /**
     * Next auto number for the current year, e.g. INV-2026-0001.
     * Must be called inside a transaction.
     */
    public static function nextInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->year.'-';

        $latest = static::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->pluck('invoice_number');

        $sequence = 0;
        foreach ($latest as $number) {
            if (preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', (string) $number, $matches)) {
                $sequence = max($sequence, (int) $matches[1]);
            }
        }

        return $prefix.str_pad((string) ($sequence + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function pairKey(int $merchantId, int $adminVoucherId): string
    {
        return $merchantId.'-'.$adminVoucherId;
    }
}

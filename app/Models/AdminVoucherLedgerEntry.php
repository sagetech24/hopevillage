<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminVoucherLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'admin_voucher_id',
        'period_month',
        'total_redemptions',
        'total_amount_dispensed',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'total_redemptions' => 'integer',
            'total_amount_dispensed' => 'decimal:2',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function adminVoucher(): BelongsTo
    {
        return $this->belongsTo(AdminVoucher::class, 'admin_voucher_id');
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(AdminVoucherReimbursement::class, 'admin_voucher_ledger_entry_id');
    }

    /**
     * Cost per voucher: cost per point × points per voucher.
     */
    public function costPerVoucher(): float
    {
        return round(
            max(0, (float) ($this->adminVoucher?->amount_cost ?? 0))
            * max(0, (float) ($this->adminVoucher?->points_cost ?? 0)),
            2
        );
    }

    /**
     * Total dispensed: total redeemed × cost per voucher.
     */
    public function computedTotalDispensed(): float
    {
        return round((int) $this->total_redemptions * $this->costPerVoucher(), 2);
    }

    /**
     * Get the total amount reimbursed for this ledger entry.
     */
    public function getTotalReimbursedAttribute(): float
    {
        if (array_key_exists('reimbursements_sum_amount', $this->attributes)) {
            return (float) ($this->attributes['reimbursements_sum_amount'] ?? 0);
        }

        return (float) $this->reimbursements()->sum('amount');
    }

    /**
     * Get the outstanding balance (total dispensed minus total reimbursed).
     */
    public function getOutstandingBalanceAttribute(): float
    {
        return $this->computedTotalDispensed() - $this->total_reimbursed;
    }
}

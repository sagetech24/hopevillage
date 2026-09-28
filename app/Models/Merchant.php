<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Merchant extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'contact_name',
        'phone',
        'email',
        'address',
        'city',
        'province',
        'postal_code',
        'website',
        'is_active',
        'merchant_code',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function adminVouchers(): BelongsToMany
    {
        return $this->belongsToMany(AdminVoucher::class, 'admin_voucher_merchant')
            ->withTimestamps();
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(AdminVoucherLedgerEntry::class);
    }

    public function adminVoucherInvoices(): HasMany
    {
        return $this->hasMany(MerchantAdminVoucherInvoice::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'merchant_user')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * Totals for Hope Village reimbursements on admin vouchers assigned to this store.
     *
     * @return array{reimbursed: float, receivables: float}
     */
    public function adminVoucherReimbursementTotals(): array
    {
        $entries = $this->ledgerEntries()
            ->whereIn('admin_voucher_id', $this->adminVouchers()->select('admin_vouchers.id'))
            ->with('adminVoucher')
            ->withSum('reimbursements', 'amount')
            ->get();

        return [
            'reimbursed' => round((float) $entries->sum(fn (AdminVoucherLedgerEntry $entry) => (float) ($entry->reimbursements_sum_amount ?? 0)), 2),
            'receivables' => round((float) $entries->sum(fn (AdminVoucherLedgerEntry $entry) => $entry->computedTotalDispensed()), 2),
        ];
    }

    /**
     * Register media collections for merchant logo
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Get the logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('logo');

        return $media ? $media->getUrl() : null;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($merchant) {
            if (empty($merchant->merchant_code)) {
                $merchant->merchant_code = static::generateUniqueMerchantCode();
            }
        });
    }

    /**
     * Generate a unique merchant code.
     */
    protected static function generateUniqueMerchantCode(): string
    {
        do {
            $code = 'MER-'.strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (static::where('merchant_code', $code)->exists());

        return $code;
    }
}

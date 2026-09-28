<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AdminVoucher extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'voucher_code',
        'name',
        'description',
        'points_cost',
        'amount_cost',
        'valid_from',
        'valid_until',
        'usage_limit',
        'usage_count',
        'is_active',
        'visibility_to_type_of_work',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'amount_cost' => 'decimal:2',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'is_active' => 'boolean',
            'visibility_to_type_of_work' => 'array',
        ];
    }

    /**
     * Check if this voucher is visible to the given member based on type_of_work.
     * Null or empty visibility = visible to all. Otherwise, member's type_of_work
     * must match one of the selected visibility options.
     * When user is null (guest), only visible if visibility is empty.
     */
    public function isVisibleToMember(?User $user): bool
    {
        $visibility = $this->visibility_to_type_of_work ?? [];
        if (empty($visibility)) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        $memberType = $user->type_of_work;
        if ($memberType === null || $memberType === '') {
            // Treat null/empty as "Others" for consistency with registration default
            return in_array('Others', $visibility, true);
        }

        if (in_array($memberType, $visibility, true)) {
            return true;
        }

        // Custom type: not "Migrant worker" or "Migrant domestic worker"
        $standardTypes = ['Migrant worker', 'Migrant domestic worker'];
        $isCustomType = ! in_array($memberType, $standardTypes, true);

        return $isCustomType && in_array('Others', $visibility, true);
    }

    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'admin_voucher_merchant')
            ->withTimestamps();
    }

    /**
     * Cost per voucher: cost per point × points per voucher.
     */
    public function costPerVoucher(): float
    {
        return round(
            max(0, (float) ($this->amount_cost ?? 0))
            * max(0, (float) ($this->points_cost ?? 0)),
            2
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_admin_voucher')
            ->withPivot([
                'status',
                'claimed_at',
                'redeemed_at',
                'redeemed_at_merchant_id',
                'voided_at',
                'voided_by',
                'void_reason',
                'points_refunded',
            ])
            ->withTimestamps();
    }

    /**
     * Scope: active and valid (within validity period, under usage limit).
     */
    public function scopeValid(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereRaw('usage_count < usage_limit');
            });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($adminVoucher) {
            if (empty($adminVoucher->voucher_code)) {
                $adminVoucher->voucher_code = static::generateUniqueVoucherCode();
            }
        });
    }

    /**
     * Generate a unique voucher code.
     */
    protected static function generateUniqueVoucherCode(): string
    {
        do {
            $code = 'AVOU-'.strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (static::where('voucher_code', $code)->exists());

        return $code;
    }

    /**
     * Check if voucher is valid (active, within validity period, and not exceeded usage limit)
     */
    public function isValid(): bool
    {
        // First check if the voucher is active
        if (! $this->is_active) {
            return false;
        }

        // Get current time
        $now = now();

        // Check if voucher has started (valid_from)
        // If valid_from is set, current time must be >= valid_from (inclusive)
        if ($this->valid_from !== null) {
            $validFrom = $this->valid_from;
            // Ensure we have a Carbon instance
            if (! ($validFrom instanceof \Carbon\Carbon)) {
                $validFrom = \Carbon\Carbon::parse($validFrom);
            }

            // Current time must be greater than or equal to valid_from (inclusive)
            // Using lt() which is exclusive: returns true only if now < validFrom
            // So if now >= validFrom (inclusive), the voucher has started
            if ($now->lt($validFrom)) {
                return false;
            }
        }

        // Check if voucher has expired (valid_until)
        // If valid_until is set, current time must be <= valid_until (inclusive)
        if ($this->valid_until !== null) {
            $validUntil = $this->valid_until;
            // Ensure we have a Carbon instance
            if (! ($validUntil instanceof \Carbon\Carbon)) {
                $validUntil = \Carbon\Carbon::parse($validUntil);
            }

            // Current time must be less than or equal to valid_until (inclusive)
            // Using gt() which is exclusive: returns true only if now > validUntil
            // So if now <= validUntil (inclusive), the voucher is still valid
            if ($now->gt($validUntil)) {
                return false;
            }
        }

        // Check if usage limit has been reached
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Whether a claimed admin voucher may still be redeemed.
     * Usage limit is claim inventory only and is not re-checked here.
     */
    public function isRedeemable(): bool
    {
        return $this->is_active && $this->isWithinValidityDate();
    }

    /**
     * Get the status reason if voucher is not valid
     */
    public function getStatusReason(): ?string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        // Get current time
        $now = now();

        // Check if voucher has started (valid_from)
        // If valid_from is set, check if current time is before valid_from
        if ($this->valid_from !== null) {
            $validFrom = $this->valid_from;
            // Ensure we have a Carbon instance
            if (! ($validFrom instanceof \Carbon\Carbon)) {
                $validFrom = \Carbon\Carbon::parse($validFrom);
            }

            // Check if current time is before the valid_from date/time (exclusive)
            // If now < validFrom, voucher hasn't started yet
            if ($now->lt($validFrom)) {
                return 'Not Yet Valid';
            }
        }

        // Check if voucher has expired (valid_until)
        // If valid_until is set, check if current time is after valid_until
        if ($this->valid_until !== null) {
            $validUntil = $this->valid_until;
            // Ensure we have a Carbon instance
            if (! ($validUntil instanceof \Carbon\Carbon)) {
                $validUntil = \Carbon\Carbon::parse($validUntil);
            }

            // Check if current time is after the valid_until date/time (exclusive)
            // If now > validUntil, voucher has expired
            if ($now->gt($validUntil)) {
                return 'Expired';
            }
        }

        // Check if usage limit has been reached
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return 'Usage Limit Reached';
        }

        return null; // Valid
    }

    /**
     * Status reason when a claimed admin voucher cannot be redeemed.
     * Does not include Usage Limit Reached (claim inventory only).
     */
    public function getRedeemStatusReason(): ?string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        $now = now();

        if ($this->valid_from !== null) {
            $validFrom = $this->valid_from instanceof \Carbon\Carbon
                ? $this->valid_from
                : \Carbon\Carbon::parse($this->valid_from);

            if ($now->lt($validFrom)) {
                return 'Not Yet Valid';
            }
        }

        if ($this->valid_until !== null) {
            $validUntil = $this->valid_until instanceof \Carbon\Carbon
                ? $this->valid_until
                : \Carbon\Carbon::parse($this->valid_until);

            if ($now->gt($validUntil)) {
                return 'Expired';
            }
        }

        return null;
    }

    /**
     * Whether the current time falls within the voucher validity window.
     */
    public function isWithinValidityDate(): bool
    {
        $now = now();

        if ($this->valid_from !== null) {
            $validFrom = $this->valid_from instanceof \Carbon\Carbon
                ? $this->valid_from
                : \Carbon\Carbon::parse($this->valid_from);

            if ($now->lt($validFrom)) {
                return false;
            }
        }

        if ($this->valid_until !== null) {
            $validUntil = $this->valid_until instanceof \Carbon\Carbon
                ? $this->valid_until
                : \Carbon\Carbon::parse($this->valid_until);

            if ($now->gt($validUntil)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the voucher is past its valid-until date.
     */
    public function isBeyondValidityDate(): bool
    {
        if ($this->valid_until === null) {
            return false;
        }

        $validUntil = $this->valid_until instanceof \Carbon\Carbon
            ? $this->valid_until
            : \Carbon\Carbon::parse($this->valid_until);

        return now()->gt($validUntil);
    }

    /**
     * Whether the voucher has a start date that has not been reached yet.
     */
    public function isNotYetStarted(): bool
    {
        if ($this->valid_from === null) {
            return false;
        }

        $validFrom = $this->valid_from instanceof \Carbon\Carbon
            ? $this->valid_from
            : \Carbon\Carbon::parse($this->valid_from);

        return now()->lt($validFrom);
    }

    /**
     * List grouping key: active, pending, not_yet_valid, or expired.
     *
     * - Expired: past the validity end date (any activation status)
     * - Pending: not yet activated and not expired (including future start dates)
     * - Not yet valid: activated, but the start date has not been reached
     * - Active: activated and currently within the validity window
     */
    public function getListStatusGroup(): string
    {
        if ($this->isBeyondValidityDate()) {
            return 'expired';
        }

        if (! $this->is_active) {
            return 'pending';
        }

        if ($this->isNotYetStarted()) {
            return 'not_yet_valid';
        }

        return 'active';
    }

    public function getDisplayStatusCategory(): string
    {
        return match ($this->getListStatusGroup()) {
            'active' => 'active',
            'pending' => 'pending_approval',
            'not_yet_valid' => 'not_yet_valid',
            default => 'expired',
        };
    }

    public function getDisplayStatusLabel(): string
    {
        return match ($this->getDisplayStatusCategory()) {
            'active' => 'Active',
            'pending_approval' => 'Pending Approval',
            'not_yet_valid' => 'Not Yet Valid',
            'expired' => 'Expired',
        };
    }

    public function getDisplayStatusSortOrder(): int
    {
        return match ($this->getListStatusGroup()) {
            'active' => 1,
            'pending' => 2,
            'not_yet_valid' => 3,
            'expired' => 4,
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Get the image URL
     */
    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('image');

        return $media ? $media->getUrl() : null;
    }
}

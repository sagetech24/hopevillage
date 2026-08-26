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
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Voucher extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'merchant_id',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'min_purchase',
        'max_discount',
        'valid_from',
        'valid_until',
        'usage_limit',
        'usage_count',
        'is_active',
        'visibility_to_type_of_work',
        'voucher_code',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'max_discount' => 'decimal:2',
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

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class)->withTrashed();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['status', 'claimed_at', 'redeemed_at'])
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

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (empty($voucher->voucher_code)) {
                $voucher->voucher_code = static::generateUniqueVoucherCode();
            }
        });
    }

    /**
     * Generate a unique voucher code.
     *
     * @return string
     */
    protected static function generateUniqueVoucherCode(): string
    {
        do {
            $code = 'VOU-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (static::where('voucher_code', $code)->exists());

        return $code;
    }

    /**
     * Check if voucher is valid (active, within validity period, and not exceeded usage limit)
     */
    public function isValid(): bool
    {
        // First check if the voucher is active
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        // Check if voucher has started (valid_from)
        // If valid_from is set, current time must be >= valid_from
        if ($this->valid_from !== null) {
            $validFrom = $this->valid_from;
            // Ensure we have a Carbon instance
            if (!($validFrom instanceof \Carbon\Carbon)) {
                $validFrom = \Carbon\Carbon::parse($validFrom);
            }
            // Current time must be greater than or equal to valid_from
            if ($now->lt($validFrom)) {
                return false;
            }
        }

        // Check if voucher has expired (valid_until)
        // If valid_until is set, current time must be <= valid_until
        if ($this->valid_until !== null) {
            $validUntil = $this->valid_until;
            // Ensure we have a Carbon instance
            if (!($validUntil instanceof \Carbon\Carbon)) {
                $validUntil = \Carbon\Carbon::parse($validUntil);
            }
            // Current time must be less than or equal to valid_until
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
     * Get the status reason if voucher is not valid
     */
    public function getStatusReason(): ?string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        $now = now();
        
        // Check if voucher has started (valid_from)
        if ($this->valid_from !== null) {
            if ($now->isBefore($this->valid_from)) {
                return 'Not Yet Valid';
            }
        }

        // Check if voucher has expired (valid_until)
        if ($this->valid_until !== null) {
            if ($now->isAfter($this->valid_until) && $this->is_active) {
                return 'Expired';
            }
        }

        // Check if usage limit has been reached
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return 'Usage Limit Reached';
        }

        return 'Active'; // Valid
    }

    /**
     * Register media collections for voucher images
     */
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

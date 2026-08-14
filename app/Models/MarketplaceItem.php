<?php

namespace App\Models;

use App\Observers\MarketplaceItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([MarketplaceItemObserver::class])]
class MarketplaceItem extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'marketplace_category_id',
        'description',
        'points_cost',
        'amount_cost',
        'per_item_quantity',
        'stock',
        'daily_limit_quantity',
        'is_active',
        'valid_from',
        'valid_until',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'amount_cost' => 'decimal:2',
            'per_item_quantity' => 'integer',
            'stock' => 'integer',
            'daily_limit_quantity' => 'integer',
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('image');

        return $media ? $media->getUrl() : null;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'marketplace_category_id');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'marketplace_item_location')->withTimestamps();
    }

    public function orderLineItems(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'marketplace_item_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(MarketplaceItemAudit::class)->orderByDesc('created_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            });
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('stock')
                ->orWhereRaw(
                    'marketplace_items.stock > (
                        select coalesce(sum(moi.quantity), 0)
                        from marketplace_order_items moi
                        inner join marketplace_orders mo on mo.id = moi.marketplace_order_id
                        where moi.marketplace_item_id = marketplace_items.id
                        and mo.status in (?, ?)
                    )',
                    [
                        MarketplaceOrder::STATUS_PENDING_PICKUP,
                        MarketplaceOrder::STATUS_FULFILLED,
                    ]
                );
        });
    }

    public function scopeAvailableForMembers(Builder $query): Builder
    {
        return $query->active()->published()->inStock();
    }

    public function scopeAvailableAtLocation(Builder $query, ?int $locationId): Builder
    {
        if (! $locationId) {
            return $query;
        }

        return $query->where(function ($q) use ($locationId) {
            $q->whereDoesntHave('locations')
                ->orWhereHas('locations', function ($locationQuery) use ($locationId) {
                    $locationQuery->where('locations.id', $locationId);
                });
        });
    }

    public function isAvailableForPurchase(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }
        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        $remaining = $this->availableQuantity();
        if ($remaining !== null && $remaining <= 0) {
            return false;
        }

        return true;
    }

    public function hasStockFor(int $quantity): bool
    {
        $available = $this->availableQuantity();
        if ($available === null) {
            return true;
        }

        return $available >= $quantity;
    }

    /**
     * Configured total quantity set on the item (null = unlimited).
     */
    public function setQuantity(): ?int
    {
        if ($this->stock === null) {
            return null;
        }

        return (int) $this->stock;
    }

    /**
     * Remaining quantity for display: set quantity minus fulfilled units.
     */
    public function remainingQuantity(): ?int
    {
        if ($this->stock === null) {
            return null;
        }

        return max(0, (int) $this->stock - $this->fulfilledQuantityCount());
    }

    /**
     * Quantity still available to sell (excludes pending pickup and fulfilled).
     */
    public function availableQuantity(): ?int
    {
        if ($this->stock === null) {
            return null;
        }

        return max(
            0,
            (int) $this->stock - $this->pendingQuantityCount() - $this->fulfilledQuantityCount()
        );
    }

    public function fulfilledQuantityCount(): int
    {
        if (array_key_exists('fulfilled_quantity', $this->attributes)) {
            return (int) ($this->attributes['fulfilled_quantity'] ?? 0);
        }

        return (int) $this->orderLineItems()
            ->whereHas('order', function (Builder $query) {
                $query->where('status', MarketplaceOrder::STATUS_FULFILLED);
            })
            ->sum('quantity');
    }

    public function pendingQuantityCount(): int
    {
        if (array_key_exists('pending_quantity', $this->attributes)) {
            return (int) ($this->attributes['pending_quantity'] ?? 0);
        }

        return (int) $this->orderLineItems()
            ->whereHas('order', function (Builder $query) {
                $query->where('status', MarketplaceOrder::STATUS_PENDING_PICKUP);
            })
            ->sum('quantity');
    }

    public function hasDailyLimit(): bool
    {
        return $this->daily_limit_quantity !== null && $this->daily_limit_quantity > 0;
    }

    public function quantityRedeemedTodayBy(int $userId): int
    {
        return (int) $this->orderLineItems()
            ->whereHas('order', function (Builder $query) use ($userId) {
                $query->where('user_id', $userId)
                    ->whereIn('status', [
                        MarketplaceOrder::STATUS_PENDING_PICKUP,
                        MarketplaceOrder::STATUS_FULFILLED,
                    ])
                    ->whereBetween('submitted_at', [now()->startOfDay(), now()->endOfDay()]);
            })
            ->sum('quantity');
    }

    public function remainingDailyQuantityFor(int $userId): ?int
    {
        if (! $this->hasDailyLimit()) {
            return null;
        }

        return max(0, (int) $this->daily_limit_quantity - $this->quantityRedeemedTodayBy($userId));
    }

    public function hasDailyCapacityFor(int $userId, int $quantity): bool
    {
        $remaining = $this->remainingDailyQuantityFor($userId);
        if ($remaining === null) {
            return true;
        }

        return $quantity <= $remaining;
    }

    public function dailyLimitExceededMessage(int $userId, int $quantity): ?string
    {
        if ($this->hasDailyCapacityFor($userId, $quantity)) {
            return null;
        }

        $used = $this->quantityRedeemedTodayBy($userId);

        if ($used >= (int) $this->daily_limit_quantity) {
            return __('Daily limit reached for :item. This member can redeem up to :limit per day and has already redeemed :used today. Try again tomorrow.', [
                'item' => $this->name,
                'limit' => number_format((int) $this->daily_limit_quantity),
                'used' => number_format($used),
            ]);
        }

        return __('Daily limit for :item is :limit per member per day. This member can still redeem :remaining today.', [
            'item' => $this->name,
            'limit' => number_format((int) $this->daily_limit_quantity),
            'remaining' => number_format((int) $this->remainingDailyQuantityFor($userId)),
        ]);
    }
}

<?php

namespace App\Models;

use App\Services\PointsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class MarketplaceOrder extends Model
{
    use HasFactory;

    public const STATUS_CART = 'cart';

    public const STATUS_PENDING_PICKUP = 'pending_pickup';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'user_id',
        'status',
        'submitted_at',
        'points_total',
        'fulfilled_at',
        'fulfilled_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'points_total' => 'integer',
            'submitted_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fulfilledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'marketplace_order_id');
    }

    /**
     * Open cart for member (one active cart per user).
     */
    public static function openCartForUser(int $userId): self
    {
        return DB::transaction(fn () => static::acquireCartForUserInTransaction($userId));
    }

    /**
     * Lock user row and return or create cart. Caller must already be inside {@see DB::transaction}.
     */
    public static function acquireCartForUserInTransaction(int $userId): self
    {
        User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

        $cart = static::query()
            ->where('user_id', $userId)
            ->where('status', self::STATUS_CART)
            ->lockForUpdate()
            ->first();

        if ($cart) {
            return $cart;
        }

        return static::query()->create([
            'user_id' => $userId,
            'status' => self::STATUS_CART,
            'points_total' => 0,
        ]);
    }

    public function recalculatePointsTotal(): int
    {
        $total = 0;
        foreach ($this->orderItems as $line) {
            $total += $line->points_per_item * $line->quantity;
        }

        return $total;
    }

    /**
     * Sync line prices from catalog and persist cart points_total (informational until submit).
     */
    public function refreshLinePricesFromCatalog(): void
    {
        foreach ($this->orderItems as $line) {
            $item = $line->marketplaceItem;
            if ($item) {
                $line->update(['points_per_item' => $item->points_cost]);
            }
        }
        $this->update(['points_total' => $this->recalculatePointsTotal()]);
    }

    /**
     * Checkout: deduct points, decrement stock, set pending_pickup.
     *
     * @throws \Throwable
     */
    public function submit(): void
    {
        DB::transaction(function () {
            $order = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== self::STATUS_CART) {
                throw new \RuntimeException('This cart cannot be submitted.');
            }

            $order->load(['orderItems.marketplaceItem']);

            if ($order->orderItems->isEmpty()) {
                throw new \RuntimeException('Your cart is empty.');
            }

            $user = User::query()->whereKey($order->user_id)->lockForUpdate()->firstOrFail();

            $pointsTotal = 0;

            foreach ($order->orderItems as $line) {
                $item = MarketplaceItem::query()
                    ->whereKey($line->marketplace_item_id)
                    ->lockForUpdate()
                    ->first();

                if (! $item || ! $item->isAvailableForPurchase()) {
                    throw new \RuntimeException('An item in your cart is no longer available: '.($item?->name ?? 'Unknown'));
                }

                if (! $item->hasStockFor($line->quantity)) {
                    throw new \RuntimeException('Insufficient stock for: '.$item->name);
                }

                $dailyLimitMessage = $item->dailyLimitExceededMessage((int) $order->user_id, (int) $line->quantity);
                if ($dailyLimitMessage) {
                    throw new \RuntimeException($dailyLimitMessage);
                }

                $line->update(['points_per_item' => $item->points_cost]);
                $pointsTotal += $item->points_cost * $line->quantity;
            }

            if ($user->total_points < $pointsTotal) {
                throw new \RuntimeException('Insufficient points. You need '.$pointsTotal.' points for this order.');
            }

            app(PointsService::class)->deductWithinTransaction(
                $user,
                $pointsTotal,
                PointsService::ACTIVITY_MARKETPLACE_REDEEM,
                'Marketplace order #'.$order->id.' — checkout',
                null,
                null,
            );

            $order->update([
                'status' => self::STATUS_PENDING_PICKUP,
                'submitted_at' => now(),
                'points_total' => $pointsTotal,
            ]);
        });

        $this->refresh();
    }

    public function fulfillByAdmin(User $admin): void
    {
        DB::transaction(function () use ($admin) {
            $order = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== self::STATUS_PENDING_PICKUP) {
                throw new \RuntimeException('Only pending pickup orders can be marked fulfilled.');
            }

            $order->update([
                'status' => self::STATUS_FULFILLED,
                'fulfilled_at' => now(),
                'fulfilled_by' => $admin->id,
            ]);
        });

        $this->refresh();
    }

    public function cancelByAdmin(User $admin, ?string $notes = null): void
    {
        DB::transaction(function () use ($admin, $notes) {
            $order = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== self::STATUS_PENDING_PICKUP) {
                throw new \RuntimeException('Only pending pickup orders can be cancelled.');
            }

            $user = User::query()->whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $refund = (int) $order->points_total;

            if ($refund > 0) {
                app(PointsService::class)->creditPointsWithinTransaction(
                    $user,
                    $refund,
                    PointsService::ACTIVITY_MARKETPLACE_REFUND,
                    'Refund for cancelled marketplace order #'.$order->id,
                    null,
                    null,
                );
            }

            $order->update([
                'status' => self::STATUS_CANCELLED,
                'fulfilled_by' => $admin->id,
                'notes' => $notes,
            ]);
        });

        $this->refresh();
    }

    /**
     * Void a fulfilled order: restore stock, credit points back, mark as voided.
     */
    public function voidByAdmin(User $admin, ?string $notes = null): void
    {
        DB::transaction(function () use ($admin, $notes) {
            $order = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== self::STATUS_FULFILLED) {
                throw new \RuntimeException('Only fulfilled orders can be voided.');
            }

            $user = User::query()->whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $refund = (int) $order->points_total;

            if ($refund > 0) {
                app(PointsService::class)->creditPointsWithinTransaction(
                    $user,
                    $refund,
                    PointsService::ACTIVITY_MARKETPLACE_REFUND,
                    'Refund for voided marketplace order #'.$order->id,
                    null,
                    null,
                );
            }

            $order->update([
                'status' => self::STATUS_VOIDED,
                'fulfilled_by' => $admin->id,
                'notes' => $notes ?? 'Voided by admin',
            ]);
        });

        $this->refresh();
    }

    /**
     * Shop counter sale: deduct member points, decrement stock, record a fulfilled order.
     * Each line: ['marketplace_item_id' => int, 'quantity' => int].
     *
     * @param  array<int, array{marketplace_item_id: int, quantity: int}>  $lines
     */
    public static function recordCashierSale(
        User $member,
        User $admin,
        array $lines,
        ?string $notes = null,
        ?int $memberActivityLocationId = null,
    ): self {
        return DB::transaction(function () use ($member, $admin, $lines, $notes, $memberActivityLocationId) {
            if ($lines === []) {
                throw new \RuntimeException('No items to checkout.');
            }

            $mergedQuantities = [];
            foreach ($lines as $line) {
                $itemId = (int) ($line['marketplace_item_id'] ?? 0);
                $qty = max(1, (int) ($line['quantity'] ?? 1));
                if ($itemId <= 0) {
                    throw new \RuntimeException('Invalid item.');
                }
                $mergedQuantities[$itemId] = ($mergedQuantities[$itemId] ?? 0) + $qty;
            }

            $lines = collect($mergedQuantities)
                ->map(fn (int $qty, int $itemId) => ['marketplace_item_id' => $itemId, 'quantity' => $qty])
                ->values()
                ->all();

            $member = User::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();
            if (! $member->isMember()) {
                throw new \RuntimeException('Invalid member account.');
            }

            $normalized = [];
            $pointsTotal = 0;

            foreach ($lines as $line) {
                $itemId = (int) ($line['marketplace_item_id'] ?? 0);
                $qty = max(1, (int) ($line['quantity'] ?? 1));

                $item = MarketplaceItem::query()->whereKey($itemId)->lockForUpdate()->first();
                if (! $item || ! $item->isAvailableForPurchase()) {
                    throw new \RuntimeException('Item not available: '.($item?->name ?? 'Unknown'));
                }
                if (! $item->hasStockFor($qty)) {
                    throw new \RuntimeException('Insufficient stock for: '.$item->name);
                }

                $dailyLimitMessage = $item->dailyLimitExceededMessage((int) $member->id, $qty);
                if ($dailyLimitMessage) {
                    throw new \RuntimeException($dailyLimitMessage);
                }

                $pointsPer = (int) $item->points_cost;
                $normalized[] = [
                    'item' => $item,
                    'quantity' => $qty,
                    'points_per_item' => $pointsPer,
                ];
                $pointsTotal += $pointsPer * $qty;
            }

            if ($member->total_points < $pointsTotal) {
                throw new \RuntimeException('Member has insufficient points (needs '.$pointsTotal.').');
            }

            $order = static::query()->create([
                'user_id' => $member->id,
                'status' => self::STATUS_FULFILLED,
                'submitted_at' => now(),
                'points_total' => $pointsTotal,
                'fulfilled_at' => now(),
                'fulfilled_by' => $admin->id,
                'notes' => $notes ?? 'Shop counter sale',
            ]);

            foreach ($normalized as $row) {
                MarketplaceOrderItem::query()->create([
                    'marketplace_order_id' => $order->id,
                    'marketplace_item_id' => $row['item']->id,
                    'quantity' => $row['quantity'],
                    'points_per_item' => $row['points_per_item'],
                ]);
            }

            $pointsService = app(PointsService::class);

            $redeemActivityType = $pointsService->activityTypeForPointMovement(
                PointsService::ACTIVITY_MARKETPLACE_REDEEM,
            );

            $activityLocationId = static::resolveMarketplaceMemberActivityLocationId(
                $memberActivityLocationId,
                $normalized,
            );

            $memberActivity = MemberActivity::query()->create([
                'user_id' => $member->id,
                'activity_type_id' => $redeemActivityType->id,
                'location_id' => $activityLocationId,
                'amenity_id' => null,
                'event_id' => null,
                'activity_time' => now(),
                'description' => 'Marketplace counter order #'.$order->id.' — '.$pointsTotal.' points',
                'metadata' => [
                    'marketplace_order_id' => $order->id,
                    'points_total' => $pointsTotal,
                    'fulfilled_by_user_id' => $admin->id,
                    'lines' => array_map(static fn (array $row): array => [
                        'marketplace_item_id' => $row['item']->id,
                        'item_name' => $row['item']->name,
                        'quantity' => $row['quantity'],
                        'points_per_item' => $row['points_per_item'],
                    ], $normalized),
                ],
            ]);

            $pointsService->deductWithinTransaction(
                $member,
                $pointsTotal,
                PointsService::ACTIVITY_MARKETPLACE_REDEEM,
                'Marketplace counter order #'.$order->id,
                null,
                null,
                $memberActivity->id,
            );

            return $order->fresh(['orderItems']);
        });
    }

    /**
     * @param  array<int, array{item: MarketplaceItem, quantity: int, points_per_item: int}>  $normalizedLines
     */
    private static function resolveMarketplaceMemberActivityLocationId(
        ?int $preferredLocationId,
        array $normalizedLines,
    ): int {
        if ($preferredLocationId && Location::query()->whereKey($preferredLocationId)->exists()) {
            return $preferredLocationId;
        }

        foreach ($normalizedLines as $row) {
            /** @var MarketplaceItem $item */
            $item = $row['item'];
            $item->loadMissing('locations');
            $first = $item->locations->first();
            if ($first) {
                return (int) $first->id;
            }
        }

        $fallback = Location::query()->orderBy('id')->value('id');
        if (! $fallback) {
            throw new \RuntimeException('No location is configured; cannot record marketplace member activity.');
        }

        return (int) $fallback;
    }
}

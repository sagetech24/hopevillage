<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MarketplaceItemAudit extends Model
{
    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_ARCHIVED = 'archived';

    public const EVENT_RESTORED = 'restored';

    public const EVENT_LOCATIONS_CHANGED = 'locations_changed';

    public const EVENT_IMAGE_CHANGED = 'image_changed';

    public const UPDATED_AT = null;

    protected $fillable = [
        'marketplace_item_id',
        'user_id',
        'event',
        'changes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class, 'marketplace_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            self::EVENT_CREATED => __('Created'),
            self::EVENT_UPDATED => __('Updated'),
            self::EVENT_ARCHIVED => __('Archived'),
            self::EVENT_RESTORED => __('Restored'),
            self::EVENT_LOCATIONS_CHANGED => __('Locations changed'),
            self::EVENT_IMAGE_CHANGED => __('Image changed'),
            default => $this->event,
        };
    }

    public static function fieldLabel(string $field): string
    {
        return match ($field) {
            'name' => __('Name'),
            'description' => __('Description'),
            'marketplace_category_id' => __('Category'),
            'points_cost' => __('Points per item'),
            'amount_cost' => __('Cost per item'),
            'stock' => __('Stock'),
            'daily_limit_quantity' => __('Daily limit'),
            'is_active' => __('Status'),
            'valid_from' => __('Valid from'),
            'valid_until' => __('Valid until'),
            'locations' => __('Locations'),
            'image' => __('Image'),
            default => $field,
        };
    }

    public static function isHighlightedField(string $field): bool
    {
        return in_array($field, ['points_cost', 'amount_cost', 'stock'], true);
    }

    /**
     * @param  array{categories?: array<int|string, string>, locations?: array<int|string, string>}  $lookups
     */
    public static function formatValue(string $field, mixed $value, array $lookups = []): string
    {
        if ($field === 'stock' && $value === null) {
            return __('Unlimited');
        }

        if ($field === 'daily_limit_quantity' && $value === null) {
            return __('None');
        }

        if ($field === 'is_active') {
            return $value ? __('Active') : __('Inactive');
        }

        if ($field === 'marketplace_category_id') {
            if ($value === null || $value === '') {
                return __('Uncategorized');
            }

            return $lookups['categories'][$value] ?? (string) $value;
        }

        if ($field === 'locations') {
            $ids = is_array($value) ? $value : [];
            if ($ids === []) {
                return __('All locations');
            }

            $names = array_map(
                fn ($id) => $lookups['locations'][$id] ?? (string) $id,
                $ids
            );

            return implode(', ', $names);
        }

        if ($field === 'image') {
            return $value ? __('Image') : __('None');
        }

        if ($value === null || $value === '') {
            return __('None');
        }

        if ($field === 'points_cost') {
            return number_format((int) $value).' '.__('pts');
        }

        if ($field === 'amount_cost') {
            return 'SGD '.number_format((float) $value, 2);
        }

        if (in_array($field, ['stock', 'daily_limit_quantity'], true)) {
            return number_format((int) $value);
        }

        if (in_array($field, ['valid_from', 'valid_until'], true)) {
            try {
                return Carbon::parse((string) $value)->format('d M Y, h:i A');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        return (string) $value;
    }
}

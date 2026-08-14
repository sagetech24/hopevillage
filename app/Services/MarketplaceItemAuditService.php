<?php

namespace App\Services;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceItemAudit;
use Carbon\CarbonInterface;

class MarketplaceItemAuditService
{
    public const AUDITED_ATTRIBUTES = [
        'name',
        'description',
        'marketplace_category_id',
        'points_cost',
        'amount_cost',
        'stock',
        'daily_limit_quantity',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    public function logCreated(MarketplaceItem $item): void
    {
        $changes = [];

        foreach (self::AUDITED_ATTRIBUTES as $field) {
            $changes[$field] = [
                'old' => null,
                'new' => $this->normalize($field, $item->getAttribute($field)),
            ];
        }

        $this->write($item, MarketplaceItemAudit::EVENT_CREATED, $changes);
    }

    public function logUpdated(MarketplaceItem $item): void
    {
        $changes = [];

        foreach (self::AUDITED_ATTRIBUTES as $field) {
            if (! $item->isDirty($field)) {
                continue;
            }

            $old = $this->normalize($field, $item->getOriginal($field));
            $new = $this->normalize($field, $item->getAttribute($field));

            if ($old === $new) {
                continue;
            }

            $changes[$field] = [
                'old' => $old,
                'new' => $new,
            ];
        }

        if ($changes === []) {
            return;
        }

        $this->write($item, MarketplaceItemAudit::EVENT_UPDATED, $changes);
    }

    public function logArchived(MarketplaceItem $item): void
    {
        $this->write($item, MarketplaceItemAudit::EVENT_ARCHIVED, []);
    }

    public function logRestored(MarketplaceItem $item): void
    {
        $this->write($item, MarketplaceItemAudit::EVENT_RESTORED, []);
    }

    /**
     * @param  list<int>  $oldIds
     * @param  list<int>  $newIds
     */
    public function logLocationsChanged(MarketplaceItem $item, array $oldIds, array $newIds): void
    {
        $oldIds = $this->normalizeIdList($oldIds);
        $newIds = $this->normalizeIdList($newIds);

        if ($oldIds === $newIds) {
            return;
        }

        $this->write($item, MarketplaceItemAudit::EVENT_LOCATIONS_CHANGED, [
            'locations' => [
                'old' => $oldIds,
                'new' => $newIds,
            ],
        ]);
    }

    public function logImageChanged(MarketplaceItem $item, string $action): void
    {
        $changes = match ($action) {
            'added' => ['image' => ['old' => null, 'new' => 'uploaded']],
            'replaced' => ['image' => ['old' => 'uploaded', 'new' => 'uploaded']],
            'removed' => ['image' => ['old' => 'uploaded', 'new' => null]],
            default => ['image' => ['old' => null, 'new' => $action]],
        };

        $this->write($item, MarketplaceItemAudit::EVENT_IMAGE_CHANGED, $changes);
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    protected function write(MarketplaceItem $item, string $event, array $changes): void
    {
        MarketplaceItemAudit::query()->create([
            'marketplace_item_id' => $item->id,
            'user_id' => auth()->id(),
            'event' => $event,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }

    protected function normalize(string $field, mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value === null) {
            return null;
        }

        return match ($field) {
            'is_active' => $value === true || $value === 1 || $value === '1',
            'points_cost', 'stock', 'daily_limit_quantity', 'marketplace_category_id' => (int) $value,
            'amount_cost' => number_format((float) $value, 2, '.', ''),
            'description' => $value === '' ? null : (string) $value,
            default => $value,
        };
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    protected function normalizeIdList(array $ids): array
    {
        $normalized = array_values(array_unique(array_map('intval', $ids)));
        sort($normalized);

        return $normalized;
    }
}

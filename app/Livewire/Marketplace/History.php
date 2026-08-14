<?php

namespace App\Livewire\Marketplace;

use App\Models\Location;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceItemAudit;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WithPagination;

    public MarketplaceItem $item;

    protected $paginationTheme = 'tailwind';

    public function mount(int $id): void
    {
        abort_unless(auth()->user()?->canAccessAdminMarketplace(), 403);

        $this->item = MarketplaceItem::withTrashed()
            ->with(['category'])
            ->findOrFail($id);
    }

    public function render()
    {
        $audits = MarketplaceItemAudit::query()
            ->with('user')
            ->where('marketplace_item_id', $this->item->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $categoryIds = [];
        $locationIds = [];

        foreach ($audits as $audit) {
            foreach ($audit->changes ?? [] as $field => $diff) {
                if ($field === 'marketplace_category_id') {
                    foreach (['old', 'new'] as $side) {
                        if (isset($diff[$side]) && $diff[$side] !== null) {
                            $categoryIds[] = (int) $diff[$side];
                        }
                    }
                }

                if ($field === 'locations') {
                    foreach (['old', 'new'] as $side) {
                        foreach ($diff[$side] ?? [] as $locationId) {
                            $locationIds[] = (int) $locationId;
                        }
                    }
                }
            }
        }

        return view('livewire.marketplace.history', [
            'audits' => $audits,
            'lookups' => [
                'categories' => $categoryIds === []
                    ? []
                    : MarketplaceCategory::query()->whereIn('id', array_unique($categoryIds))->pluck('name', 'id')->all(),
                'locations' => $locationIds === []
                    ? []
                    : Location::query()->whereIn('id', array_unique($locationIds))->pluck('name', 'id')->all(),
            ],
        ])->layout('layouts.app');
    }
}

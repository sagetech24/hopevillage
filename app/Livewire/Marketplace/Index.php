<?php

namespace App\Livewire\Marketplace;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $viewMode = 'list';

    public bool $showFavoritesOnly = false;

    /** @var array<int, int> */
    public array $favoriteIds = [];

    public bool $showMessage = false;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccessAdminMarketplace(), 403);

        $this->showMessage = session()->has('message');
        $this->viewMode = session('marketplace_view_mode', 'list');
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['card', 'list'], true)) {
            return;
        }

        $this->viewMode = $mode;
        session(['marketplace_view_mode' => $mode]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingShowFavoritesOnly(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        abort_unless(auth()->user()?->can('marketplace.edit'), 403);

        $item = MarketplaceItem::query()->findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);
        session()->flash('message', $item->is_active ? 'Item activated.' : 'Item deactivated.');
        $this->showMessage = true;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('marketplace.delete'), 403);

        $item = MarketplaceItem::query()->findOrFail($id);
        $item->delete();
        session()->flash('message', 'Item archived successfully.');
        $this->showMessage = true;
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()?->can('marketplace.delete'), 403);

        $item = MarketplaceItem::onlyTrashed()->findOrFail($id);
        $item->restore();
        session()->flash('message', 'Item restored successfully.');
        $this->showMessage = true;
    }

    public function render()
    {
        $query = MarketplaceItem::query()
            ->with(['createdBy', 'category', 'locations'])
            ->withSum(['orderLineItems as fulfilled_quantity' => function ($q) {
                $q->whereHas('order', function ($orderQuery) {
                    $orderQuery->where('status', MarketplaceOrder::STATUS_FULFILLED);
                });
            }], 'quantity')
            ->withSum(['orderLineItems as pending_quantity' => function ($q) {
                $q->whereHas('order', function ($orderQuery) {
                    $orderQuery->where('status', MarketplaceOrder::STATUS_PENDING_PICKUP);
                });
            }], 'quantity');

        if ($this->statusFilter === 'deleted') {
            $query->onlyTrashed();
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhereHas('category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($this->showFavoritesOnly) {
            $favoriteIds = array_values(array_filter(array_map('intval', $this->favoriteIds)));
            if ($favoriteIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('id', $favoriteIds);
            }
        }

        $items = $query->orderByDesc('created_at')->paginate(10);

        return view('livewire.marketplace.index', [
            'items' => $items,
        ])->layout('layouts.app');
    }
}

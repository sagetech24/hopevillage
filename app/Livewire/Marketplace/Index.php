<?php

namespace App\Livewire\Marketplace;

use App\Models\MarketplaceItem;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public bool $showMessage = false;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccessAdminMarketplace(), 403);

        $this->showMessage = session()->has('message');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        $query = MarketplaceItem::query()->with(['createdBy', 'category', 'locations']);

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

        $items = $query->orderByDesc('created_at')->paginate(12);

        return view('livewire.marketplace.index', [
            'items' => $items,
        ])->layout('layouts.app');
    }
}

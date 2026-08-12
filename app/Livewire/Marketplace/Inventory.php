<?php

namespace App\Livewire\Marketplace;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use Livewire\Component;
use Livewire\WithPagination;

class Inventory extends Component
{
    use WithPagination;

    public MarketplaceItem $item;

    public string $statusFilter = 'all';

    protected $paginationTheme = 'tailwind';

    public function mount(int $id): void
    {
        abort_unless(auth()->user()?->canAccessAdminMarketplace(), 403);

        $this->item = MarketplaceItem::withTrashed()
            ->with(['category', 'locations'])
            ->findOrFail($id);
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function fulfill(int $orderId): void
    {
        abort_unless(auth()->user()?->can('marketplace.edit'), 403);

        try {
            $order = MarketplaceOrder::query()->findOrFail($orderId);
            $order->fulfillByAdmin(auth()->user());
            $this->dispatch('notify', type: 'success', message: 'Order marked as collected.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function cancelOrder(int $orderId, ?string $notes = null): void
    {
        abort_unless(auth()->user()?->can('marketplace.edit'), 403);

        try {
            $order = MarketplaceOrder::query()->findOrFail($orderId);
            $order->cancelByAdmin(auth()->user(), $notes);
            $this->dispatch('notify', type: 'success', message: 'Order cancelled and points refunded.');
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $this->item = MarketplaceItem::withTrashed()
            ->with(['category', 'locations'])
            ->withSum(['orderLineItems as fulfilled_quantity' => function ($q) {
                $q->whereHas('order', function ($orderQuery) {
                    $orderQuery->where('status', MarketplaceOrder::STATUS_FULFILLED);
                });
            }], 'quantity')
            ->withSum(['orderLineItems as pending_quantity' => function ($q) {
                $q->whereHas('order', function ($orderQuery) {
                    $orderQuery->where('status', MarketplaceOrder::STATUS_PENDING_PICKUP);
                });
            }], 'quantity')
            ->findOrFail($this->item->id);

        $query = MarketplaceOrder::query()
            ->with(['user', 'orderItems.marketplaceItem', 'fulfilledByUser'])
            ->where('status', '!=', MarketplaceOrder::STATUS_CART)
            ->whereHas('orderItems', function ($q) {
                $q->where('marketplace_item_id', $this->item->id);
            })
            ->orderByDesc('updated_at');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $orders = $query->paginate(15);

        $soldQuantity = $this->item->fulfilledQuantityCount() + $this->item->pendingQuantityCount();
        $soldAmount = (float) $this->item->amount_cost * $this->item->fulfilledQuantityCount();

        return view('livewire.marketplace.inventory', [
            'orders' => $orders,
            'soldQuantity' => (int) $soldQuantity,
            'soldAmount' => $soldAmount,
        ])->layout('layouts.app');
    }
}

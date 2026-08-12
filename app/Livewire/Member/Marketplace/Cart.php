<?php

namespace App\Livewire\Member\Marketplace;

use App\Models\MarketplaceOrder;
use Livewire\Attributes\On;
use Livewire\Component;

class Cart extends Component
{
    #[On('marketplace-cart-updated')]
    public function refreshCart(): void
    {
        //
    }

    public function getCartProperty(): ?MarketplaceOrder
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        return MarketplaceOrder::query()
            ->where('user_id', $user->id)
            ->where('status', MarketplaceOrder::STATUS_CART)
            ->with(['orderItems.marketplaceItem'])
            ->first();
    }

    public function incrementQty(int $lineId): void
    {
        $cart = $this->cart;
        if (! $cart) {
            return;
        }

        $line = $cart->orderItems()->whereKey($lineId)->first();
        if (! $line || ! $line->marketplaceItem) {
            return;
        }

        $item = $line->marketplaceItem;
        if (! $item->isAvailableForPurchase()) {
            $this->dispatch('notify', type: 'error', message: 'This item is no longer available.');

            return;
        }

        $newQty = $line->quantity + 1;
        if (! $item->hasStockFor($newQty)) {
            $this->dispatch('notify', type: 'error', message: 'Not enough stock.');

            return;
        }

        $dailyLimitMessage = $item->dailyLimitExceededMessage((int) $cart->user_id, $newQty);
        if ($dailyLimitMessage) {
            $this->dispatch('notify', type: 'error', message: $dailyLimitMessage);

            return;
        }

        $line->update([
            'quantity' => $newQty,
            'points_per_item' => $item->points_cost,
        ]);
        $cart->refreshLinePricesFromCatalog();
        $this->dispatch('marketplace-cart-updated');
    }

    public function decrementQty(int $lineId): void
    {
        $cart = $this->cart;
        if (! $cart) {
            return;
        }

        $line = $cart->orderItems()->whereKey($lineId)->first();
        if (! $line) {
            return;
        }

        if ($line->quantity <= 1) {
            $line->delete();
        } else {
            $line->decrement('quantity');
            if ($line->marketplaceItem) {
                $line->update(['points_per_item' => $line->marketplaceItem->points_cost]);
            }
        }

        $cart->refresh();
        if ($cart->orderItems()->count() === 0) {
            $cart->update(['points_total' => 0]);
        } else {
            $cart->refreshLinePricesFromCatalog();
        }
        $this->dispatch('marketplace-cart-updated');
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->cart;
        if (! $cart) {
            return;
        }

        $cart->orderItems()->whereKey($lineId)->delete();
        $cart->refresh();
        if ($cart->orderItems()->count() === 0) {
            $cart->update(['points_total' => 0]);
        } else {
            $cart->refreshLinePricesFromCatalog();
        }
        $this->dispatch('marketplace-cart-updated');
    }

    public function checkout(): void
    {
        $cart = $this->cart;
        if (! $cart || $cart->orderItems->isEmpty()) {
            $this->dispatch('notify', type: 'error', message: 'Your cart is empty.');

            return;
        }

        try {
            $cart->submit();
            $this->dispatch('notify', type: 'success', message: 'Order placed! Show your QR code at the counter to collect your items.');
            $this->dispatch('points-updated');
            $this->dispatch('marketplace-cart-updated');
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.member.marketplace.cart', [
            'cart' => $this->cart,
        ])->layout('layouts.app', [
            'title' => __('Cart'),
        ]);
    }
}

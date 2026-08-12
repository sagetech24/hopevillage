<?php

namespace App\Livewire\Marketplace;

use App\Models\Location;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Component;

class Cashier extends Component
{
    /**
     * @var array<int, array{id: string, marketplace_item_id: int, quantity: int, selected: bool}>
     */
    public array $basket = [];

    public string $catalogSearch = '';

    public string $catalogCategory = '';

    public string $catalogLocation = '';

    public bool $awaitingMemberPayment = false;

    /**
     * Lines removed from basket pending payment (snapshot).
     *
     * @var array<int, array{marketplace_item_id: int, quantity: int}>
     */
    public array $pendingLines = [];

    public int $pendingPointsTotal = 0;

    public string $memberQrInput = '';

    public ?int $resolvedMemberId = null;

    protected $listeners = [
        'qr-code-scanned' => 'onQrCodeScanned',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('marketplace.edit'), 403);
    }

    public function onQrCodeScanned($value = null): void
    {
        if (! $this->awaitingMemberPayment) {
            return;
        }
        if ($value === null) {
            return;
        }
        if (is_array($value)) {
            $value = $value[0] ?? reset($value);
        }
        $this->memberQrInput = trim((string) $value);
        $this->lookupMember();
    }

    public function lookupMember(): void
    {
        if (! $this->awaitingMemberPayment) {
            return;
        }

        $code = trim($this->memberQrInput);
        if ($code === '') {
            $this->resolvedMemberId = null;

            return;
        }

        $member = User::query()
            ->where('user_type', 'member')
            ->where('qr_code', $code)
            ->first();

        $this->resolvedMemberId = $member?->id;
        if (! $member) {
            $this->dispatch('notify', type: 'error', message: __('No member found with this QR code.'));
        }
    }

    public function addToBasket(int $itemId): void
    {
        $item = MarketplaceItem::query()->availableForMembers()->find($itemId);
        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('This item is not available.'));

            return;
        }

        foreach ($this->basket as $idx => $line) {
            if ((int) $line['marketplace_item_id'] === $itemId) {
                $newQty = (int) $line['quantity'] + 1;
                if (! $item->hasStockFor($newQty)) {
                    $this->dispatch('notify', type: 'error', message: __('Not enough stock for this item.'));

                    return;
                }
                if ($item->hasDailyLimit() && $newQty > (int) $item->daily_limit_quantity) {
                    $this->dispatch('notify', type: 'error', message: __('Daily limit for :item is :limit per member per day.', [
                        'item' => $item->name,
                        'limit' => number_format((int) $item->daily_limit_quantity),
                    ]));

                    return;
                }
                $this->basket[$idx]['quantity'] = $newQty;

                return;
            }
        }

        if (! $item->hasStockFor(1)) {
            $this->dispatch('notify', type: 'error', message: __('This item is out of stock.'));

            return;
        }

        $this->basket[] = [
            'id' => (string) Str::uuid(),
            'marketplace_item_id' => $itemId,
            'quantity' => 1,
            'selected' => false,
        ];
    }

    public function incrementQty(string $lineId): void
    {
        foreach ($this->basket as $idx => $line) {
            if ($line['id'] !== $lineId) {
                continue;
            }
            $item = MarketplaceItem::query()->find($line['marketplace_item_id']);
            if (! $item) {
                return;
            }
            $newQty = (int) $line['quantity'] + 1;
            if (! $item->hasStockFor($newQty)) {
                $this->dispatch('notify', type: 'error', message: __('Not enough stock.'));

                return;
            }
            if ($item->hasDailyLimit() && $newQty > (int) $item->daily_limit_quantity) {
                $this->dispatch('notify', type: 'error', message: __('Daily limit for :item is :limit per member per day.', [
                    'item' => $item->name,
                    'limit' => number_format((int) $item->daily_limit_quantity),
                ]));

                return;
            }
            $this->basket[$idx]['quantity'] = $newQty;

            return;
        }
    }

    public function decrementQty(string $lineId): void
    {
        foreach ($this->basket as $idx => $line) {
            if ($line['id'] !== $lineId) {
                continue;
            }
            if ((int) $line['quantity'] <= 1) {
                unset($this->basket[$idx]);
                $this->basket = array_values($this->basket);
            } else {
                $this->basket[$idx]['quantity'] = (int) $line['quantity'] - 1;
            }

            return;
        }
    }

    public function removeLine(string $lineId): void
    {
        $this->basket = array_values(array_filter($this->basket, fn ($line) => $line['id'] !== $lineId));
    }

    public function beginCheckoutSelected(): void
    {
        $ids = [];
        foreach ($this->basket as $line) {
            if (! empty($line['selected'])) {
                $ids[] = $line['id'];
            }
        }
        if ($ids === []) {
            $this->dispatch('notify', type: 'error', message: __('Select at least one line to checkout.'));

            return;
        }
        $this->moveLinesToPayment($ids);
    }

    public function beginCheckoutAll(): void
    {
        if ($this->basket === []) {
            $this->dispatch('notify', type: 'error', message: __('Basket is empty.'));

            return;
        }
        $ids = array_column($this->basket, 'id');
        $this->moveLinesToPayment($ids);
    }

    /**
     * @param  array<int, string>  $lineIds
     */
    protected function moveLinesToPayment(array $lineIds): void
    {
        $pending = [];
        $remaining = [];
        $idSet = array_flip($lineIds);

        foreach ($this->basket as $line) {
            if (isset($idSet[$line['id']])) {
                $pending[] = [
                    'marketplace_item_id' => (int) $line['marketplace_item_id'],
                    'quantity' => (int) $line['quantity'],
                ];
            } else {
                $remaining[] = $line;
            }
        }

        if ($pending === []) {
            $this->dispatch('notify', type: 'error', message: __('Nothing to checkout.'));

            return;
        }

        $total = 0;
        foreach ($pending as $pl) {
            $item = MarketplaceItem::query()->find($pl['marketplace_item_id']);
            if (! $item) {
                $this->dispatch('notify', type: 'error', message: __('Invalid item in basket.'));

                return;
            }
            $total += (int) $item->points_cost * $pl['quantity'];
        }

        $this->basket = $remaining;
        $this->pendingLines = $pending;
        $this->pendingPointsTotal = $total;
        $this->awaitingMemberPayment = true;
        $this->memberQrInput = '';
        $this->resolvedMemberId = null;
    }

    public function cancelPayment(): void
    {
        if (! $this->awaitingMemberPayment || $this->pendingLines === []) {
            $this->awaitingMemberPayment = false;
            $this->pendingLines = [];
            $this->pendingPointsTotal = 0;
            $this->memberQrInput = '';
            $this->resolvedMemberId = null;

            return;
        }

        foreach ($this->pendingLines as $pl) {
            $itemId = (int) $pl['marketplace_item_id'];
            $qty = (int) $pl['quantity'];
            $merged = false;
            foreach ($this->basket as $idx => $line) {
                if ((int) $line['marketplace_item_id'] === $itemId) {
                    $this->basket[$idx]['quantity'] = (int) $line['quantity'] + $qty;
                    $merged = true;
                    break;
                }
            }
            if (! $merged) {
                $this->basket[] = [
                    'id' => (string) Str::uuid(),
                    'marketplace_item_id' => $itemId,
                    'quantity' => $qty,
                    'selected' => false,
                ];
            }
        }

        $this->awaitingMemberPayment = false;
        $this->pendingLines = [];
        $this->pendingPointsTotal = 0;
        $this->memberQrInput = '';
        $this->resolvedMemberId = null;
    }

    public function confirmPayment(): void
    {
        if (! $this->awaitingMemberPayment || $this->pendingLines === []) {
            $this->dispatch('notify', type: 'error', message: __('Nothing to pay for.'));

            return;
        }

        if (! $this->resolvedMemberId) {
            $this->dispatch('notify', type: 'error', message: __('Look up or scan the member QR code first.'));

            return;
        }

        $member = User::query()->find($this->resolvedMemberId);
        if (! $member) {
            $this->dispatch('notify', type: 'error', message: __('Member not found.'));

            return;
        }

        try {
            MarketplaceOrder::recordCashierSale(
                $member,
                auth()->user(),
                $this->pendingLines,
                null,
                (int) $this->catalogLocation > 0 ? (int) $this->catalogLocation : null,
            );
            $this->dispatch('notify', type: 'success', message: __('Sale completed. Points deducted.'));
            $this->awaitingMemberPayment = false;
            $this->pendingLines = [];
            $this->pendingPointsTotal = 0;
            $this->memberQrInput = '';
            $this->resolvedMemberId = null;
            session()->flash('message', __('Points payment successful.'));
            session()->flash('message_type', 'success');
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function clearBasket(): void
    {
        if ($this->awaitingMemberPayment) {
            $this->dispatch('notify', type: 'error', message: __('Cancel payment first.'));

            return;
        }
        $this->basket = [];
    }

    public function getCatalogItemsProperty()
    {
        $q = MarketplaceItem::query()
            ->with(['category', 'locations'])
            ->availableForMembers()
            ->orderBy('name');

        if ($this->catalogSearch !== '') {
            $s = '%'.$this->catalogSearch.'%';
            $q->where(function ($query) use ($s) {
                $query->where('name', 'like', $s)
                    ->orWhere('description', 'like', $s);
            });
        }
        if ($this->catalogCategory !== '') {
            $q->where('marketplace_category_id', (int) $this->catalogCategory);
        }
        if ($this->catalogLocation !== '') {
            $q->availableAtLocation((int) $this->catalogLocation);
        }

        return $q->limit(80)->get();
    }

    public function render()
    {
        $basketRows = [];
        foreach ($this->basket as $idx => $line) {
            $item = MarketplaceItem::query()->find($line['marketplace_item_id']);
            if (! $item) {
                continue;
            }
            $basketRows[] = [
                'index' => $idx,
                'line' => $line,
                'item' => $item,
                'line_points' => (int) $item->points_cost * (int) $line['quantity'],
            ];
        }

        $basketTotal = array_sum(array_column($basketRows, 'line_points'));

        $pendingLabels = [];
        foreach ($this->pendingLines as $pl) {
            $item = MarketplaceItem::query()->find($pl['marketplace_item_id']);
            $pendingLabels[] = [
                'name' => $item?->name ?? '#'.$pl['marketplace_item_id'],
                'qty' => $pl['quantity'],
                'points' => ($item ? (int) $item->points_cost * (int) $pl['quantity'] : 0),
                'image_url' => $item?->image_url,
                'description' => $item?->description
                    ? Str::limit(trim(strip_tags((string) $item->description)), 180)
                    : null,
                'per_item_quantity' => $item ? max(1, (int) $item->per_item_quantity) : 1,
            ];
        }

        $dailyLimitWarnings = [];
        if ($this->resolvedMemberId) {
            foreach ($this->pendingLines as $pl) {
                $item = MarketplaceItem::query()->find($pl['marketplace_item_id']);
                if (! $item) {
                    continue;
                }
                $message = $item->dailyLimitExceededMessage((int) $this->resolvedMemberId, (int) $pl['quantity']);
                if ($message) {
                    $dailyLimitWarnings[] = $message;
                }
            }
        }

        return view('livewire.marketplace.cashier', [
            'catalogItems' => $this->catalogItems,
            'basketRows' => $basketRows,
            'basketTotal' => $basketTotal,
            'pendingLabels' => $pendingLabels,
            'dailyLimitWarnings' => $dailyLimitWarnings,
            'categories' => MarketplaceCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(),
            'resolvedMember' => $this->resolvedMemberId ? User::query()->find($this->resolvedMemberId) : null,
        ])->layout('layouts.app');
    }
}

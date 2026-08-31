<?php

namespace App\Livewire\Marketplace;

use App\Models\Location;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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

    public ?string $lastSaleMessage = null;

    public ?string $lastScannedQr = null;

    public ?int $lastScannedAt = null;

    /** @var array<int, int> */
    public array $favoriteIds = [];

    protected $listeners = [
        'qr-code-scanned' => 'onQrCodeScanned',
    ];

    private const SCAN_COOLDOWN_SECONDS = 2;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccessMarketplaceCashier(), 403);

        $this->restoreCashierState();
    }

    public function dehydrate(): void
    {
        $this->persistCashierState();
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
            $value = $value['value'] ?? $value[0] ?? reset($value);
        }

        $code = trim((string) $value);
        if ($code === '') {
            return;
        }

        if ($this->shouldIgnoreScan($code)) {
            $this->dispatch('resume-qr-camera');

            return;
        }

        $this->rememberScan($code);
        $this->memberQrInput = $code;
        $this->resolveMemberFromInput(notify: false);

        if (! $this->resolvedMemberId) {
            $this->presentChargeResult(
                success: false,
                member: null,
                balanceBefore: 0,
                deducted: $this->pendingPointsTotal,
                message: __('No member found with this QR code.'),
            );

            return;
        }

        $this->chargeResolvedMember();
    }

    public function lookupMember(): void
    {
        $this->resolveMemberFromInput(true);
    }

    public function updatedMemberQrInput(): void
    {
        $this->resolveMemberFromInput(notify: false);
    }

    protected function resolveMemberFromInput(bool $notify): void
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
        if (! $member && $notify) {
            $this->dispatch('notify', type: 'error', message: __('No member found with this QR code.'));
        }
    }

    public function addToBasket(int $itemId): void
    {
        if ($this->awaitingMemberPayment) {
            $this->addToPendingCheckout($itemId);

            return;
        }

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
                $this->basket[$idx]['selected'] = true;
                $this->notifyBasketQuantityUpdated($item, $newQty);

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
            'selected' => true,
        ];
        $this->notifyBasketItemAdded($item);
    }

    public function addFavoriteToBasket(int $itemId): void
    {
        if ($this->awaitingMemberPayment) {
            if ($this->pendingContainsItem($itemId)) {
                return;
            }
            $this->addToPendingCheckout($itemId);

            return;
        }

        if ($this->basketContainsItem($itemId)) {
            return;
        }

        $this->addToBasket($itemId);
    }

    public function replaceBasketWithItem(int $itemId): void
    {
        if ($this->awaitingMemberPayment) {
            $this->replacePendingWithItem($itemId);

            return;
        }

        $item = MarketplaceItem::query()->availableForMembers()->find($itemId);
        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('This item is not available.'));

            return;
        }

        if (! $item->hasStockFor(1)) {
            $this->dispatch('notify', type: 'error', message: __('This item is out of stock.'));

            return;
        }

        $this->basket = [[
            'id' => (string) Str::uuid(),
            'marketplace_item_id' => $itemId,
            'quantity' => 1,
            'selected' => true,
        ]];

        $this->dispatch('notify', type: 'success', message: __('Basket cleared. :item added.', [
            'item' => $item->name,
        ]));
    }

    public function addToPendingCheckout(int $itemId): void
    {
        if (! $this->awaitingMemberPayment) {
            $this->addToBasket($itemId);

            return;
        }

        $item = MarketplaceItem::query()->availableForMembers()->find($itemId);
        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('This item is not available.'));

            return;
        }

        foreach ($this->pendingLines as $idx => $line) {
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
                $this->pendingLines[$idx]['quantity'] = $newQty;
                $this->syncCheckoutStateAfterPendingChange();
                $this->notifyBasketQuantityUpdated($item, $newQty);

                return;
            }
        }

        if (! $item->hasStockFor(1)) {
            $this->dispatch('notify', type: 'error', message: __('This item is out of stock.'));

            return;
        }

        $this->pendingLines[] = [
            'marketplace_item_id' => $itemId,
            'quantity' => 1,
        ];
        $this->syncCheckoutStateAfterPendingChange();
        $this->notifyBasketItemAdded($item);
    }

    public function replacePendingWithItem(int $itemId): void
    {
        if (! $this->awaitingMemberPayment) {
            $this->replaceBasketWithItem($itemId);

            return;
        }

        $item = MarketplaceItem::query()->availableForMembers()->find($itemId);
        if (! $item) {
            $this->dispatch('notify', type: 'error', message: __('This item is not available.'));

            return;
        }

        if (! $item->hasStockFor(1)) {
            $this->dispatch('notify', type: 'error', message: __('This item is out of stock.'));

            return;
        }

        $this->pendingLines = [[
            'marketplace_item_id' => $itemId,
            'quantity' => 1,
        ]];
        $this->syncCheckoutStateAfterPendingChange();

        $this->dispatch('notify', type: 'success', message: __('Checkout cleared. :item added.', [
            'item' => $item->name,
        ]));
    }

    public function incrementPendingQty(int $index): void
    {
        if (! isset($this->pendingLines[$index])) {
            return;
        }

        $line = $this->pendingLines[$index];
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

        $this->pendingLines[$index]['quantity'] = $newQty;
        $this->syncCheckoutStateAfterPendingChange();
        $this->notifyBasketQuantityUpdated($item, $newQty);
    }

    public function decrementPendingQty(int $index): void
    {
        if (! isset($this->pendingLines[$index])) {
            return;
        }

        $line = $this->pendingLines[$index];
        $item = MarketplaceItem::query()->find($line['marketplace_item_id']);
        if (! $item) {
            return;
        }

        if ((int) $line['quantity'] <= 1) {
            unset($this->pendingLines[$index]);
            $this->pendingLines = array_values($this->pendingLines);
            $this->syncCheckoutStateAfterPendingChange();
            $this->notifyBasketItemRemoved($item);

            return;
        }

        $newQty = (int) $line['quantity'] - 1;
        $this->pendingLines[$index]['quantity'] = $newQty;
        $this->syncCheckoutStateAfterPendingChange();
        $this->notifyBasketQuantityUpdated($item, $newQty);
    }

    public function removePendingLine(int $index): void
    {
        if (! isset($this->pendingLines[$index])) {
            return;
        }

        $item = MarketplaceItem::query()->find($this->pendingLines[$index]['marketplace_item_id']);
        unset($this->pendingLines[$index]);
        $this->pendingLines = array_values($this->pendingLines);
        $this->syncCheckoutStateAfterPendingChange();

        if ($item) {
            $this->notifyBasketItemRemoved($item);
        }
    }

    public function clearPendingCheckout(): void
    {
        if (! $this->awaitingMemberPayment) {
            $this->clearBasket();

            return;
        }

        $this->pendingLines = [];
        $this->pendingPointsTotal = 0;
        $this->awaitingMemberPayment = false;
        $this->memberQrInput = '';
        $this->resolvedMemberId = null;
        $this->lastSaleMessage = null;
        $this->lastScannedQr = null;
        $this->lastScannedAt = null;
    }

    protected function pendingContainsItem(int $itemId): bool
    {
        foreach ($this->pendingLines as $line) {
            if ((int) $line['marketplace_item_id'] === $itemId) {
                return true;
            }
        }

        return false;
    }

    protected function syncCheckoutStateAfterPendingChange(): void
    {
        $this->recalculatePendingPointsTotal();
        $this->clearResolvedMember();

        if ($this->pendingLines === []) {
            $this->awaitingMemberPayment = false;
            $this->pendingPointsTotal = 0;
        }
    }

    protected function basketContainsItem(int $itemId): bool
    {
        foreach ($this->basket as $line) {
            if ((int) $line['marketplace_item_id'] === $itemId) {
                return true;
            }
        }

        return false;
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
            $this->notifyBasketQuantityUpdated($item, $newQty);

            return;
        }
    }

    public function decrementQty(string $lineId): void
    {
        foreach ($this->basket as $idx => $line) {
            if ($line['id'] !== $lineId) {
                continue;
            }
            $item = MarketplaceItem::query()->find($line['marketplace_item_id']);
            if (! $item) {
                return;
            }
            if ((int) $line['quantity'] <= 1) {
                unset($this->basket[$idx]);
                $this->basket = array_values($this->basket);
                $this->notifyBasketItemRemoved($item);
            } else {
                $newQty = (int) $line['quantity'] - 1;
                $this->basket[$idx]['quantity'] = $newQty;
                $this->notifyBasketQuantityUpdated($item, $newQty);
            }

            return;
        }
    }

    public function removeLine(string $lineId): void
    {
        foreach ($this->basket as $line) {
            if ($line['id'] !== $lineId) {
                continue;
            }
            $item = MarketplaceItem::query()->find($line['marketplace_item_id']);
            if ($item) {
                $this->notifyBasketItemRemoved($item);
            }
            break;
        }

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
        $this->lastSaleMessage = null;
        $this->lastScannedQr = null;
        $this->lastScannedAt = null;
    }

    public function cancelPayment(): void
    {
        $this->dispatch('closeQrScanner');

        if (! $this->awaitingMemberPayment || $this->pendingLines === []) {
            $this->awaitingMemberPayment = false;
            $this->pendingLines = [];
            $this->pendingPointsTotal = 0;
            $this->memberQrInput = '';
            $this->resolvedMemberId = null;
            $this->lastSaleMessage = null;

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
                    'selected' => true,
                ];
            }
        }

        $this->awaitingMemberPayment = false;
        $this->pendingLines = [];
        $this->pendingPointsTotal = 0;
        $this->memberQrInput = '';
        $this->resolvedMemberId = null;
        $this->lastSaleMessage = null;
        $this->lastScannedQr = null;
        $this->lastScannedAt = null;
    }

    public function confirmPayment(): void
    {
        $this->chargeResolvedMember();
    }

    public function clearBasket(): void
    {
        if ($this->awaitingMemberPayment) {
            $this->clearPendingCheckout();

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

    /**
     * @return \Illuminate\Support\Collection<int, MarketplaceItem>
     */
    protected function favoriteItems()
    {
        $ids = array_values(array_filter(array_map('intval', $this->favoriteIds)));
        if ($ids === []) {
            return collect();
        }

        $items = MarketplaceItem::query()
            ->with('category')
            ->availableForMembers()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $items->get($id))
            ->filter();
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
        foreach ($this->pendingLines as $idx => $pl) {
            $item = MarketplaceItem::query()->find($pl['marketplace_item_id']);
            $pendingLabels[] = [
                'index' => $idx,
                'marketplace_item_id' => (int) $pl['marketplace_item_id'],
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
            'favoriteItems' => $this->favoriteItems(),
            'checkoutItemIds' => $this->awaitingMemberPayment
                ? array_map(fn (array $line) => (int) $line['marketplace_item_id'], $this->pendingLines)
                : array_map(fn (array $line) => (int) $line['marketplace_item_id'], $this->basket),
            'categories' => MarketplaceCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(),
            'resolvedMember' => $this->resolvedMemberId ? User::query()->find($this->resolvedMemberId) : null,
        ])->layout('layouts.app');
    }

    protected function chargeResolvedMember(): void
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
            $this->presentChargeResult(
                success: false,
                member: null,
                balanceBefore: 0,
                deducted: $this->pendingPointsTotal,
                message: __('Member not found.'),
            );
            $this->clearResolvedMember();

            return;
        }

        $lock = Cache::lock('marketplace-cashier-charge-'.(auth()->id() ?? 0), 8);
        if (! $lock->get()) {
            return;
        }

        $balanceBefore = (int) $member->total_points;
        $chargedPoints = $this->pendingPointsTotal;

        try {
            MarketplaceOrder::recordCashierSale(
                $member,
                auth()->user(),
                $this->pendingLines,
                null,
                (int) $this->catalogLocation > 0 ? (int) $this->catalogLocation : null,
            );

            $this->recalculatePendingPointsTotal();
            $this->lastSaleMessage = __('Charged :name — :points pts. Scan the next member.', [
                'name' => $member->name,
                'points' => number_format($chargedPoints),
            ]);
            $this->presentChargeResult(
                success: true,
                member: $member,
                balanceBefore: $balanceBefore,
                deducted: $chargedPoints,
            );
            $this->clearResolvedMember();
        } catch (\Throwable $e) {
            $this->presentChargeResult(
                success: false,
                member: $member,
                balanceBefore: $balanceBefore,
                deducted: $chargedPoints,
                message: $e->getMessage(),
            );
            $this->clearResolvedMember();
        } finally {
            $lock->release();
        }
    }

    protected function clearResolvedMember(): void
    {
        $this->memberQrInput = '';
        $this->resolvedMemberId = null;
    }

    protected function presentChargeResult(
        bool $success,
        ?User $member,
        int $balanceBefore,
        int $deducted,
        ?string $message = null,
    ): void {
        $this->dispatch('cashier-charge-result',
            success: $success,
            hasMember: $member !== null,
            memberName: $member?->name,
            balanceBefore: $balanceBefore,
            deducted: $deducted,
            remaining: $success ? max(0, $balanceBefore - $deducted) : $balanceBefore,
            message: $message,
            items: $this->pendingTransactionItems(),
        );
    }

    /**
     * @return array<int, array{name: string, qty: int, points: int}>
     */
    protected function pendingTransactionItems(): array
    {
        $items = [];
        foreach ($this->pendingLines as $pl) {
            $item = MarketplaceItem::query()->find($pl['marketplace_item_id']);
            $qty = (int) $pl['quantity'];
            $items[] = [
                'name' => $item?->name ?? '#'.$pl['marketplace_item_id'],
                'qty' => $qty,
                'points' => $item ? (int) $item->points_cost * $qty : 0,
            ];
        }

        return $items;
    }

    protected function rememberScan(string $code): void
    {
        $this->lastScannedQr = $code;
        $this->lastScannedAt = now()->timestamp;
    }

    protected function shouldIgnoreScan(string $code): bool
    {
        if ($this->lastScannedQr !== $code || $this->lastScannedAt === null) {
            return false;
        }

        return (now()->timestamp - $this->lastScannedAt) < self::SCAN_COOLDOWN_SECONDS;
    }

    protected function cashierSessionKey(): string
    {
        return 'marketplace.cashier.'.(auth()->id() ?? 0);
    }

    protected function persistCashierState(): void
    {
        if (! auth()->id()) {
            return;
        }

        session()->put($this->cashierSessionKey(), [
            'basket' => $this->basket,
            'pendingLines' => $this->pendingLines,
            'pendingPointsTotal' => $this->pendingPointsTotal,
            'awaitingMemberPayment' => $this->awaitingMemberPayment,
        ]);
    }

    protected function restoreCashierState(): void
    {
        $payload = session()->get($this->cashierSessionKey());
        if (! is_array($payload)) {
            return;
        }

        $this->basket = $this->sanitizeBasket(is_array($payload['basket'] ?? null) ? $payload['basket'] : []);
        [$this->pendingLines, $this->pendingPointsTotal] = $this->sanitizePendingLines(
            is_array($payload['pendingLines'] ?? null) ? $payload['pendingLines'] : []
        );

        $this->awaitingMemberPayment = (bool) ($payload['awaitingMemberPayment'] ?? false) && $this->pendingLines !== [];
        if (! $this->awaitingMemberPayment) {
            $this->pendingLines = [];
            $this->pendingPointsTotal = 0;
        }

        $this->clearResolvedMember();
    }

    /**
     * @param  array<int, mixed>  $basket
     * @return array<int, array{id: string, marketplace_item_id: int, quantity: int, selected: bool}>
     */
    protected function sanitizeBasket(array $basket): array
    {
        $clean = [];
        foreach ($basket as $line) {
            if (! is_array($line)) {
                continue;
            }
            $itemId = (int) ($line['marketplace_item_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            if ($itemId <= 0 || $qty <= 0) {
                continue;
            }
            if (! MarketplaceItem::query()->whereKey($itemId)->exists()) {
                continue;
            }
            $clean[] = [
                'id' => (string) ($line['id'] ?? Str::uuid()),
                'marketplace_item_id' => $itemId,
                'quantity' => $qty,
                'selected' => ! empty($line['selected']),
            ];
        }

        return $clean;
    }

    /**
     * @param  array<int, mixed>  $lines
     * @return array{0: array<int, array{marketplace_item_id: int, quantity: int}>, 1: int}
     */
    protected function sanitizePendingLines(array $lines): array
    {
        $valid = [];
        $total = 0;
        foreach ($lines as $pl) {
            if (! is_array($pl)) {
                continue;
            }
            $itemId = (int) ($pl['marketplace_item_id'] ?? 0);
            $qty = (int) ($pl['quantity'] ?? 0);
            if ($itemId <= 0 || $qty <= 0) {
                continue;
            }
            $item = MarketplaceItem::query()->find($itemId);
            if (! $item || ! $item->isAvailableForPurchase()) {
                continue;
            }
            $valid[] = [
                'marketplace_item_id' => $itemId,
                'quantity' => $qty,
            ];
            $total += (int) $item->points_cost * $qty;
        }

        return [$valid, $total];
    }

    protected function recalculatePendingPointsTotal(): void
    {
        $total = 0;
        foreach ($this->pendingLines as $pl) {
            $item = MarketplaceItem::query()->find($pl['marketplace_item_id']);
            if (! $item) {
                continue;
            }
            $total += (int) $item->points_cost * (int) $pl['quantity'];
        }
        $this->pendingPointsTotal = $total;
    }

    protected function notifyBasketItemAdded(MarketplaceItem $item): void
    {
        $this->dispatch('notify', type: 'success', message: __(':item added to basket.', [
            'item' => $item->name,
        ]));
    }

    protected function notifyBasketQuantityUpdated(MarketplaceItem $item, int $quantity): void
    {
        $this->dispatch('notify', type: 'success', message: __(':item quantity updated to :qty.', [
            'item' => $item->name,
            'qty' => $quantity,
        ]));
    }

    protected function notifyBasketItemRemoved(MarketplaceItem $item): void
    {
        $this->dispatch('notify', type: 'success', message: __(':item removed from basket.', [
            'item' => $item->name,
        ]));
    }
}

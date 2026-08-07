<?php

namespace App\Livewire\Marketplace;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public string $memberQrLookup = '';

    public ?int $selectedMemberId = null;

    public string $trendMetric = 'quantity';

    protected $paginationTheme = 'tailwind';

    protected $listeners = [
        'qr-code-scanned' => 'onQrCodeScanned',
    ];

    private const TREND_DAYS = 30;

    private const CHART_TOP_N = 5;

    private const RANKING_TOP_N = 10;

    private const CHART_COLORS = [
        ['border' => 'rgb(249, 115, 22)', 'bg' => 'rgba(249, 115, 22, 0.1)'],
        ['border' => 'rgb(16, 185, 129)', 'bg' => 'rgba(16, 185, 129, 0.1)'],
        ['border' => 'rgb(59, 130, 246)', 'bg' => 'rgba(59, 130, 246, 0.1)'],
        ['border' => 'rgb(168, 85, 247)', 'bg' => 'rgba(168, 85, 247, 0.1)'],
        ['border' => 'rgb(236, 72, 153)', 'bg' => 'rgba(236, 72, 153, 0.1)'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccessAdminMarketplace(), 403);
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setTrendMetric(string $metric): void
    {
        if (! in_array($metric, ['quantity', 'points'], true)) {
            return;
        }

        $this->trendMetric = $metric;
    }

    public function onQrCodeScanned($value = null): void
    {
        if ($value === null) {
            return;
        }
        if (is_array($value)) {
            $value = $value[0] ?? reset($value);
        }
        $this->memberQrLookup = trim((string) $value);
        $this->lookupMember();
    }

    public function lookupMember(): void
    {
        $code = trim($this->memberQrLookup);
        if ($code === '') {
            $this->selectedMemberId = null;

            return;
        }

        $member = User::query()
            ->where('user_type', 'member')
            ->where('qr_code', $code)
            ->first();

        $this->selectedMemberId = $member?->id;
        if (! $member) {
            $this->dispatch('notify', type: 'error', message: 'No member found with this QR code.');
        }
    }

    public function clearMember(): void
    {
        $this->memberQrLookup = '';
        $this->selectedMemberId = null;
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
        $query = MarketplaceOrder::query()
            ->with(['user', 'orderItems.marketplaceItem', 'fulfilledByUser'])
            ->where('status', '!=', MarketplaceOrder::STATUS_CART)
            ->orderByDesc('updated_at');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->selectedMemberId) {
            $query->where('user_id', $this->selectedMemberId);
        }

        $orders = $query->paginate(15);

        $selectedMember = $this->selectedMemberId
            ? User::query()->find($this->selectedMemberId)
            : null;

        return view('livewire.marketplace.orders', [
            'orders' => $orders,
            'selectedMember' => $selectedMember,
            'productTrend' => $this->buildProductTrend(),
        ])->layout('layouts.app');
    }

    /**
     * @return array{
     *     labels: list<string>,
     *     datasets: list<array{label: string, data: list<int|float>, borderColor: string, backgroundColor: string, tension: float, fill: bool}>,
     *     ranking: list<array{name: string, quantity: int, points: int, share: float}>,
     *     totals: array{quantity: int, points: int},
     *     hasData: bool
     * }
     */
    private function buildProductTrend(): array
    {
        $startDate = now()->subDays(self::TREND_DAYS - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $empty = [
            'labels' => $this->trendDayLabels(),
            'datasets' => [],
            'ranking' => [],
            'totals' => ['quantity' => 0, 'points' => 0],
            'hasData' => false,
        ];

        $baseQuery = MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->where('marketplace_orders.status', MarketplaceOrder::STATUS_FULFILLED)
            ->whereNotNull('marketplace_orders.fulfilled_at')
            ->whereBetween('marketplace_orders.fulfilled_at', [$startDate, $endDate]);

        $totalsRow = (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(marketplace_order_items.quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(marketplace_order_items.quantity * marketplace_order_items.points_per_item), 0) as total_points')
            ->first();

        $totalQuantity = (int) ($totalsRow->total_quantity ?? 0);
        $totalPoints = (int) ($totalsRow->total_points ?? 0);

        if ($totalQuantity === 0) {
            return $empty;
        }

        $productTotals = (clone $baseQuery)
            ->selectRaw('marketplace_order_items.marketplace_item_id')
            ->selectRaw('SUM(marketplace_order_items.quantity) as total_quantity')
            ->selectRaw('SUM(marketplace_order_items.quantity * marketplace_order_items.points_per_item) as total_points')
            ->groupBy('marketplace_order_items.marketplace_item_id')
            ->orderByDesc('total_quantity')
            ->get();

        $itemIds = $productTotals->pluck('marketplace_item_id')->all();
        $itemNames = MarketplaceItem::withTrashed()
            ->whereIn('id', $itemIds)
            ->pluck('name', 'id');

        $ranking = $productTotals
            ->take(self::RANKING_TOP_N)
            ->map(function ($row) use ($itemNames, $totalQuantity) {
                $quantity = (int) $row->total_quantity;

                return [
                    'name' => $itemNames[$row->marketplace_item_id] ?? __('Item removed'),
                    'quantity' => $quantity,
                    'points' => (int) $row->total_points,
                    'share' => $totalQuantity > 0
                        ? round(($quantity / $totalQuantity) * 100, 1)
                        : 0.0,
                ];
            })
            ->values()
            ->all();

        $chartItemIds = $productTotals->take(self::CHART_TOP_N)->pluck('marketplace_item_id')->all();

        $dailyRows = (clone $baseQuery)
            ->whereIn('marketplace_order_items.marketplace_item_id', $chartItemIds)
            ->selectRaw('marketplace_order_items.marketplace_item_id')
            ->selectRaw('DATE(marketplace_orders.fulfilled_at) as day')
            ->selectRaw('SUM(marketplace_order_items.quantity) as daily_quantity')
            ->selectRaw('SUM(marketplace_order_items.quantity * marketplace_order_items.points_per_item) as daily_points')
            ->groupBy('marketplace_order_items.marketplace_item_id', 'day')
            ->get()
            ->groupBy('marketplace_item_id');

        $labels = $this->trendDayLabels();
        $dateKeys = $this->trendDayKeys();
        $datasets = [];

        foreach ($chartItemIds as $index => $itemId) {
            $color = self::CHART_COLORS[$index % count(self::CHART_COLORS)];
            $byDay = ($dailyRows->get($itemId) ?? collect())->keyBy(function ($row) {
                return Carbon::parse($row->day)->format('Y-m-d');
            });

            $data = [];
            foreach ($dateKeys as $dateKey) {
                $row = $byDay->get($dateKey);
                if (! $row) {
                    $data[] = 0;
                    continue;
                }
                $data[] = $this->trendMetric === 'points'
                    ? (int) $row->daily_points
                    : (int) $row->daily_quantity;
            }

            $datasets[] = [
                'label' => $itemNames[$itemId] ?? __('Item removed'),
                'data' => $data,
                'borderColor' => $color['border'],
                'backgroundColor' => $color['bg'],
                'tension' => 0.4,
                'fill' => true,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'ranking' => $ranking,
            'totals' => [
                'quantity' => $totalQuantity,
                'points' => $totalPoints,
            ],
            'hasData' => true,
        ];
    }

    /**
     * @return list<string>
     */
    private function trendDayLabels(): array
    {
        $labels = [];
        for ($i = self::TREND_DAYS - 1; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->format('M d');
        }

        return $labels;
    }

    /**
     * @return list<string>
     */
    private function trendDayKeys(): array
    {
        $keys = [];
        for ($i = self::TREND_DAYS - 1; $i >= 0; $i--) {
            $keys[] = now()->subDays($i)->format('Y-m-d');
        }

        return $keys;
    }
}

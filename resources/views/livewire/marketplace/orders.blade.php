<div>
    <x-slot name="header">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 flex flex-wrap justify-between items-center gap-3">
            <div class="flex justify-between w-full items-center gap-3">
                @can('marketplace.edit')
                    <a href="{{ route('admin.marketplace.cashier') }}" class="text-white bg-green-500 rounded-full px-3 py-1 font-medium hover:bg-green-600 text-sm">{{ __('Cashier Checkout') }}</a>
                @endcan
                <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                    {{ __('Marketplace Order History') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-md rounded-lg p-6 space-y-4">
                <h3 class="font-semibold text-gray-900">{{ __('Find member by QR code') }}</h3>
                <p class="text-sm text-gray-600">{{ __('For counter sales, use Marketplace Cashier (basket → member QR → points). This page lists orders: use QR below to filter by member when confirming older “pick up” orders still in pending status.') }}</p>
                <div class="flex flex-wrap gap-2">
                    <input
                        type="text"
                        wire:model="memberQrLookup"
                        wire:keydown.enter="lookupMember"
                        placeholder="{{ __('Member QR code') }}"
                        class="flex-1 min-w-[200px] px-4 py-2 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500"
                    >
                    <button type="button" wire:click="lookupMember" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">{{ __('Lookup') }}</button>
                    <button type="button" @click="$dispatch('openQrScanner')" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">{{ __('Scan QR') }}</button>
                    @if ($selectedMemberId)
                        <button type="button" wire:click="clearMember" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">{{ __('Clear') }}</button>
                    @endif
                </div>
                @if ($selectedMember)
                    <p class="text-sm text-green-700 font-medium">{{ __('Member') }}: {{ $selectedMember->name }} ({{ $selectedMember->qr_code }})</p>
                @elseif ($memberQrLookup !== '' && ! $selectedMemberId)
                    <p class="text-sm text-red-600">{{ __('No member found for this code.') }}</p>
                @endif
            </div>

            <div class="bg-white shadow-md rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ __('Product trends (fulfilled, last 30 days)') }}</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ __('Top products by fulfilled units, points redeemed, and amount cost.') }}</p>
                    </div>
                    @if ($productTrend['hasData'])
                        <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-sm">
                            <button
                                type="button"
                                wire:click="setTrendMetric('quantity')"
                                class="px-3 py-1.5 {{ $trendMetric === 'quantity' ? 'bg-orange-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}"
                            >
                                {{ __('Quantity') }}
                            </button>
                            <button
                                type="button"
                                wire:click="setTrendMetric('points')"
                                class="px-3 py-1.5 border-l border-gray-200 {{ $trendMetric === 'points' ? 'bg-orange-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}"
                            >
                                {{ __('Points') }}
                            </button>
                        </div>
                    @endif
                </div>

                @if ($productTrend['hasData'])
                    <div class="flex flex-wrap gap-3">
                        <div class="rounded-lg bg-orange-50 px-4 py-2">
                            <div class="text-xs font-medium text-orange-700 uppercase tracking-wide">{{ __('Units fulfilled') }}</div>
                            <div class="text-lg font-semibold text-orange-900">{{ number_format($productTrend['totals']['quantity']) }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 px-4 py-2">
                            <div class="text-xs font-medium text-gray-600 uppercase tracking-wide">{{ __('Points redeemed') }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ number_format($productTrend['totals']['points']) }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 px-4 py-2">
                            <div class="text-xs font-medium text-gray-600 uppercase tracking-wide">{{ __('Amount cost') }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ __('SGD') }} {{ number_format($productTrend['totals']['amount'], 2) }}</div>
                        </div>
                    </div>

                    <div class="relative h-80" wire:key="product-trend-chart-{{ $trendMetric }}">
                        <canvas
                            wire:ignore
                            x-data="{
                                chart: null,
                                init() {
                                    const ctx = this.$el.getContext('2d');
                                    this.chart = new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels: @js($productTrend['labels']),
                                            datasets: @js($productTrend['datasets'])
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            plugins: {
                                                legend: {
                                                    position: 'bottom'
                                                }
                                            },
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    ticks: {
                                                        precision: 0
                                                    },
                                                    grid: {
                                                        color: 'rgba(0, 0, 0, 0.05)'
                                                    }
                                                },
                                                x: {
                                                    grid: {
                                                        display: false
                                                    },
                                                    ticks: {
                                                        maxRotation: 45,
                                                        minRotation: 45
                                                    }
                                                }
                                            }
                                        }
                                    });
                                },
                                destroy() {
                                    if (this.chart) {
                                        this.chart.destroy();
                                        this.chart = null;
                                    }
                                }
                            }"
                        ></canvas>
                    </div>

                    <div class="overflow-x-auto border border-gray-100 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">{{ __('Rank') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Product') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Quantity') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Point Value') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Amount') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Total Cost') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($productTrend['ranking'] as $index => $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            @if ($row['id'])
                                                <a href="{{ route('admin.marketplace.inventory', $row['id']) }}" class="text-orange-600 hover:text-orange-700 hover:underline">
                                                    {{ $row['name'] }}
                                                </a>
                                            @else
                                                {{ $row['name'] }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ number_format($row['quantity']) }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-orange-600 font-medium">{{ number_format($row['points']) }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ __('SGD') }} {{ number_format($row['amount'], 2) }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-700">{{ __('SGD') }} {{ number_format($row['total_cost'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-600 py-6 text-center">
                        {{ __('No fulfilled items in the last 30 days.') }}
                    </p>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-md sm:rounded-lg">
                <div class="p-4 border-b border-gray-200 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Order status filter') }}</label>
                        <select wire:model.live="statusFilter" class="w-full md:w-64 rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="all">{{ __('All') }}</option>
                            <option value="pending_pickup">{{ __('Pending pickup') }}</option>
                            <option value="fulfilled">{{ __('Fulfilled') }}</option>
                            <option value="cancelled">{{ __('Cancelled') }}</option>
                            <option value="voided">{{ __('Voided') }}</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Order') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Member') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Items') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Total') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($orders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap w-10">
                                        <div class="text-sm font-semibold text-gray-900">#{{ $order->id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-md text-gray-900 font-medium">{{ $order->user?->name ?? __('Unknown') }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->user?->qr_code }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <ul class="space-y-1">
                                            @foreach ($order->orderItems as $line)
                                                <li class="text-sm text-gray-700">
                                                    {{ $line->marketplaceItem?->name ?? __('Item removed') }}
                                                    <span class="text-gray-500">× {{ $line->quantity }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-orange-600">{{ number_format($order->points_total) }} {{ __('pts') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex text-xs font-semibold capitalize px-2 py-1 rounded-full
                                            @if ($order->status === 'pending_pickup') bg-amber-100 text-amber-900
                                            @elseif($order->status === 'fulfilled') bg-green-100 text-green-900
                                            @elseif($order->status === 'cancelled') bg-gray-200 text-gray-800
                                            @elseif($order->status === 'voided') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-700 @endif">
                                            {{ str_replace('_', ' ', $order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ ($order->fulfilled_at ?? $order->updated_at)?->format('Y-m-d') }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ ($order->fulfilled_at ?? $order->updated_at)?->format('H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if ($order->status === 'pending_pickup')
                                            @can('marketplace.edit')
                                                <div class="flex justify-end flex-wrap gap-2">
                                                    <button type="button" wire:click="fulfill({{ $order->id }})" class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-medium">
                                                        {{ __('Mark collected') }}
                                                    </button>
                                                    <button type="button" wire:click="cancelOrder({{ $order->id }})" wire:confirm="{{ __('Cancel this order and refund points?') }}" class="px-3 py-1.5 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 text-xs font-medium">
                                                        {{ __('Cancel & refund') }}
                                                    </button>
                                                </div>
                                            @endcan
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-600">
                                        {{ __('No orders match this filter.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</div>

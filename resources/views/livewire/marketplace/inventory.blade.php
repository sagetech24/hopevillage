<div>
    <x-slot name="header">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-between items-center gap-3">
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                        {{ __('Orders') }}: {{ $item->name }}
                    </h2>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.marketplace.index') }}" class="text-white bg-zinc-700 hover:bg-zinc-600 px-3 py-1.5 rounded-full text-sm font-medium">
                        <span>{{ __('← Items') }}</span>
                    </a>
                    @can('marketplace.edit')
                        <a href="{{ route('admin.marketplace.edit', $item->id) }}" class="text-sm bg-slate-600 hover:bg-slate-700 text-white transition-all duration-300 py-2 px-3 rounded-full font-medium">
                            {{ __('Edit item') }}
                        </a>
                    @endcan
                    <a href="{{ route('admin.marketplace.orders') }}" class="text-sm bg-orange-500 hover:bg-orange-600 text-white transition-all duration-300 py-2 px-3 rounded-full font-medium">
                        {{ __('All orders') }}
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-md rounded-lg p-6">
                <div class="flex flex-wrap gap-6 items-start">
                    <div class="w-24 h-24 rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center shrink-0">
                        @if ($item->image_url)
                            <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-xs text-gray-400">{{ __('No image') }}</span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $item->name }}</h3>
                            @if ($item->trashed())
                                <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full">{{ __('Deleted') }}</span>
                            @elseif ($item->is_active)
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">{{ __('Active') }}</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full">{{ __('Inactive') }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-2">{{ $item->description }}</p>
                        <div class="flex flex-wrap gap-4 text-sm text-gray-700">
                            <span>
                                <span class="text-gray-500">{{ __('Points') }}:</span>
                                <span class="font-medium text-orange-600">{{ number_format($item->points_cost) }}</span>
                            </span>
                            <span>
                                <span class="text-gray-500">{{ __('Stock') }}:</span>
                                <span class="font-medium">{{ $item->stock === null ? __('Unlimited') : number_format($item->stock) }}</span>
                            </span>
                            <span>
                                <span class="text-gray-500">{{ __('Sold') }}:</span>
                                <span class="font-medium">{{ number_format($soldQuantity) }}</span>
                            </span>
                            <span>
                                <span class="text-gray-500">{{ __('Category') }}:</span>
                                <span class="font-medium capitalize">{{ $item->category?->name ?? __('Uncategorized') }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-md sm:rounded-lg">
                <div class="p-4 border-b border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Order status filter') }}</label>
                    <select wire:model.live="statusFilter" class="w-full md:w-64 rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        <option value="all">{{ __('All') }}</option>
                        <option value="pending_pickup">{{ __('Pending pickup') }}</option>
                        <option value="fulfilled">{{ __('Fulfilled') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                        <option value="voided">{{ __('Voided') }}</option>
                    </select>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Order') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Member') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Qty') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Line total') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($orders as $order)
                                @php
                                    $line = $order->orderItems->firstWhere('marketplace_item_id', $item->id);
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">#{{ $order->id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($order->user?->qr_code)
                                            <a href="{{ route('admin.members.profile', $order->user->qr_code) }}" class="text-sm font-semibold text-orange-700 hover:text-orange-800 hover:underline transition-all duration-300">
                                                {{ $order->user->name }}
                                            </a>
                                            <div class="text-xs text-gray-500">{{ $order->user->qr_code }}</div>
                                        @else
                                            <div class="text-sm text-gray-900">{{ __('Unknown') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $line?->quantity ?? 0 }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-orange-600">
                                            {{ number_format($line?->linePointsTotal() ?? 0) }} {{ __('pts') }}
                                        </div>
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
                                        {{ __('No orders found for this item.') }}
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

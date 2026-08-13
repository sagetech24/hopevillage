<div>
    <x-slot name="header">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
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
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $item->name }}</h3>
                                @if ($item->trashed())
                                    <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full">{{ __('Deleted') }}</span>
                                @elseif ($item->is_active)
                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">{{ __('Active') }}</span>
                                @else
                                    <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full">{{ __('Inactive') }}</span>
                                @endif
                                @can('marketplace.edit')
                                    @if (! $item->trashed())
                                        <a
                                            href="{{ route('admin.marketplace.edit', $item->id) }}"
                                            class="inline-flex items-center justify-center rounded-full p-1 text-slate-500 hover:text-orange-600 hover:bg-orange-50 transition-colors"
                                            title="{{ __('Edit item') }}"
                                            aria-label="{{ __('Edit item') }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.823H3v-3.823L16.862 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                    @endif
                                @endcan
                            </div>
                            <p>
                                <span class="text-gray-500 text-xs">{{ __('Description') }}:</span>
                                <span class="text-gray-700 text-xs italic">{{ $item->description }}</span>
                            </p>
                            <p class="inline-flex items-center gap-1" title="{{ __('Category') }}">
                                <span class="text-gray-500 text-xs">{{ __('Category') }}:</span>
                                <span class="text-orange-400 text-xs font-medium capitalize bg-orange-100 rounded-full px-2 py-0.5">{{ $item->category?->name ?? __('Uncategorized') }}</span>
                            </p>
                        </div>
                        <div class="flex flex-wrap md:w-2/3 w-full gap-2 text-sm text-gray-700">
                            <span class="inline-flex items-center gap-1" title="{{ __('Points') }}">
                                <span class="inline-flex items-center gap-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-orange-500" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                    </svg>
                                    <span class="text-xs ">{{ __('Points: ') }}</span>
                                </span>
                                <span class="font-medium text-orange-600">{{ number_format($item->points_cost) }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1" title="{{ __('Item cost') }}">
                                <span class="inline-flex items-center gap-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-500" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                    </svg>
                                    <span class="text-xs">{{ __('Item cost: ') }}</span>
                                </span>
                                <span class="font-medium">{{ __('SGD') }} {{ number_format((float) $item->amount_cost, 2) }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1" title="{{ __('Set quantity') }}">
                                <span class="inline-flex items-center gap-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-500" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                    </svg>
                                    <span class="text-xs">{{ __('Max Quantity: ') }}</span>
                                </span>
                                <span class="font-medium">{{ $item->stock === null ? __('Unlimited') : number_format($item->stock) }}</span>
                            </span>
                            @if ($item->stock !== null)
                                <span class="inline-flex items-center gap-1" title="{{ __('Remaining') }}">
                                    <span class="inline-flex items-center gap-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-500" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                        </svg>
                                        <span class="text-xs">{{ __('Remaining: ') }}</span>
                                    </span>
                                    <span class="font-medium text-orange-600">{{ number_format($item->remainingQuantity()) }}</span>
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1" title="{{ __('Daily limit') }}">
                                <span class="inline-flex items-center gap-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-500" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span class="text-xs">{{ __('Daily limit: ') }}</span>
                                </span>
                                <span class="font-medium">{{ $item->daily_limit_quantity === null ? __('None') : number_format($item->daily_limit_quantity) }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1" title="{{ __('Sold') }}">
                                <span class="inline-flex items-center gap-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-500" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    <span class="text-xs">{{ __('Sold: ') }}</span>
                                </span>
                                <span class="font-medium">{{ number_format($soldQuantity) }}</span>
                            </span>
                            @if ((float) $item->amount_cost > 0)
                                <span class="inline-flex items-center gap-1" title="{{ __('Sold value') }}">
                                    <span class="inline-flex items-center gap-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-500" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                        </svg>
                                        <span class="text-xs">{{ __('Total value: ') }}</span>
                                    </span>
                                    <span class="font-medium">{{ __('SGD') }} {{ number_format($soldAmount, 2) }}</span>
                                </span>
                            @endif
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

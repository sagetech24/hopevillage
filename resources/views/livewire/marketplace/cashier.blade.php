<div
    x-data="{
        favorites: [],
        storageKey: 'marketplace_favorites_{{ auth()->id() }}',
        init() {
            this.loadFavorites();
        },
        loadFavorites() {
            try {
                const raw = sessionStorage.getItem(this.storageKey);
                this.favorites = raw ? JSON.parse(raw) : [];
                if (! Array.isArray(this.favorites)) {
                    this.favorites = [];
                }
                this.favorites = this.favorites.map(id => Number(id)).filter(id => id > 0);
            } catch (e) {
                this.favorites = [];
            }
            $wire.set('favoriteIds', this.favorites);
        },
    }"
    x-on:marketplace-favorites-updated.window="loadFavorites()"
>
    <x-slot name="header">
        <div class="max-w-5xl mx-auto md:px-0 px-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold md:text-xl text-xl text-gray-800 leading-tight">{{ __('Marketplace Cashier') }}</h2>
            </div>
            <div class="flex items-center gap-3 justify-end">
                <a href="{{ route('admin.marketplace.index') }}" class="md:text-sm text-xs bg-orange shrink-0 px-3 py-1.5 font-semibold rounded-full text-gray-600 hover:bg-orange-50 disabled:opacity-50">
                    {{ __('← Back to Item list') }}
                </a>
                @if (auth()->user()?->canAccessAdminMarketplace())
                    <a href="{{ route('admin.marketplace.orders') }}" class="md:text-sm text-xs bg-orange shrink-0 px-3 py-1.5 font-semibold rounded-full bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-50">{{ __('Order history') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto md:px-4 xl:px-0 px-3 space-y-6">
            @if (session('message'))
                <div class="bg-green-50 flex gap-2 items-center border border-green-400 text-green-900 rounded-lg px-4 py-3 text-sm">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">{{ session('message') }}</span>
                </div>
            @endif
            @if ($awaitingMemberPayment)
                <div class="bg-white border border-gray-200 rounded-xl shadow p-6 space-y-4 mx-auto">
                        <h3 class="font-semibold text-gray-900 text-2xl">{{ __('Marketplace Checkout') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('These items stay on this screen. Scan each member’s QR code to charge the same items. Use Back to basket when the queue is done.') }}</p>
                    </div>
                    @if ($lastSaleMessage)
                        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                            {{ $lastSaleMessage }}
                        </div>
                    @endif

                    @if (count($pendingLabels) <= 3)
                        <div class="flex md:flex-row flex-col md:gap-2 gap-2 w-full mt-6">
                            <div class="relative flex-1 min-w-[200px]">
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="memberQrInput"
                                    placeholder="{{ __('Member code') }}"
                                    class="w-full lg:p-2 p-3 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500"
                                >
                                <div wire:loading wire:target="memberQrInput" class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 animate-spin text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                            <button type="button" wire:click="confirmPayment" wire:loading.attr="disabled" @if(! empty($dailyLimitWarnings)) disabled @endif class="text-xs lg:p-2 p-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ __('Confirm Payment') }}
                            </button>
                            <div class="grid grid-cols-2 lg:grid-cols-2 gap-2">
                                <button type="button" wire:click="cancelPayment" class="flex justify-center items-center gap-1 text-xs lg:p-2 p-3 border border-gray-300 rounded-lg bg-gray-200 hover:bg-gray-50">
                                    {{-- back arrow icon --}}
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    {{ __('Back to basket') }}
                                </button>
                                <button type="button" @click="$dispatch('openQrScannerKeepOpen')" class="text-xs lg:p-2 p-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                                    {{ __('Scan QR') }}
                                </button>
                            </div>
                        </div>
                    @endif
                    
                    @if ($resolvedMember)
                        <div wire:loading.remove wire:target="memberQrInput" class="space-y-1 mb-6">
                            <p class="text-sm font-medium text-green-700 capitalize ">{{ __('Member') }}: {{ $resolvedMember->name }} ({{ $resolvedMember->qr_code }})</p>
                            <p class="text-sm font-semibold text-green-700 capitalize font-mono">{{ __('Points balance') }}: {{ number_format($resolvedMember->total_points) }}</p>
                        </div>
                    @endif
                    <div wire:loading wire:target="memberQrInput" class="space-y-2 mb-6">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <svg class="h-4 w-4 animate-spin text-orange-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ __('Looking up member…') }}</span>
                        </div>
                        <div class="h-4 w-56 max-w-full bg-gray-200 rounded animate-pulse"></div>
                        <div class="h-4 w-36 max-w-full bg-gray-200 rounded animate-pulse"></div>
                    </div>
                    @if (! empty($dailyLimitWarnings))
                        <div wire:loading.remove wire:target="memberQrInput" class="space-y-1 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                            @foreach ($dailyLimitWarnings as $warning)
                                <p class="text-sm text-red-700">{{ $warning }}</p>
                            @endforeach
                        </div>
                    @endif



                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Product Checkout Summary') }}</h3>
                            <p class="text-sm text-gray-600">{{ __('Adjust items before scanning or entering the member code. The same checkout items will be charged to each member.') }}</p>
                        </div>
                        @if (count($pendingLabels) > 0)
                            <button
                                type="button"
                                wire:click="clearPendingCheckout"
                                wire:confirm="{{ __('Clear all checkout items and return to catalog?') }}"
                                class="text-xs shrink-0 rounded-full bg-red-100 px-3 py-1.5 font-medium text-red-700 hover:bg-red-200 transition-colors"
                            >
                                {{ __('Clear checkout') }}
                            </button>
                        @endif
                    </div>

                    <div class="md:hidden space-y-2">
                        @foreach ($pendingLabels as $row)
                            <div wire:key="pending-mobile-{{ $row['marketplace_item_id'] }}" class="border border-gray-400 rounded-lg p-3 space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-16 h-16 rounded-lg shrink-0 overflow-hidden bg-gray-200 flex items-center justify-center ring-1 ring-gray-200">
                                        @if (! empty($row['image_url']))
                                            <img src="{{ $row['image_url'] }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            @php
                                                $name = (string) ($row['name'] ?? '');
                                                $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8')) : '?';
                                            @endphp
                                            <span class="text-base font-bold text-gray-600">{{ $initial }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <p class="font-semibold text-gray-900 leading-snug capitalize">{{ $row['name'] }}</p>
                                        @if (! empty($row['description']))
                                            <p class="text-xs text-gray-500 line-clamp-2 capitalize">{{ $row['description'] }}</p>
                                        @endif
                                        <p class="text-xs text-gray-600 capitalize">
                                            {{ __('Units per purchase') }}: <span class="font-semibold capitalize">{{ $row['per_item_quantity'] ?? 1 }}</span>
                                        </p>
                                    </div>
                                    <button type="button" wire:click="removePendingLine({{ $row['index'] }})" class="shrink-0 text-red-600 hover:text-red-800 p-1" title="{{ __('Remove') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between gap-3 pt-2 border-t border-gray-200">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">{{ __('Qty') }}</span>
                                        <button type="button" wire:click="decrementPendingQty({{ $row['index'] }})" class="text-orange-600 bg-gray-50 hover:bg-gray-100 px-2 rounded-full">−</button>
                                        <span class="text-sm font-semibold tabular-nums w-6 text-center">{{ $row['qty'] }}</span>
                                        <button type="button" wire:click="incrementPendingQty({{ $row['index'] }})" class="text-orange-600 bg-gray-50 hover:bg-gray-100 px-2 rounded-full">+</button>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-orange-500">{{ __('Points') }}</p>
                                        <p class="text-sm font-semibold text-orange-600 tabular-nums whitespace-nowrap">{{ number_format($row['points']) }} {{ __('pts') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="flex justify-between items-center bg-orange-100 rounded-lg p-3">
                            <span class="text-gray-800 font-semibold">{{ __('Total Points') }}</span>
                            <span class="font-semibold text-orange-600 text-lg tabular-nums whitespace-nowrap">{{ number_format($pendingPointsTotal) }} {{ __('pts') }}</span>
                        </div>
                    </div>

                    <table class="hidden md:table w-full table-fixed border-collapse border border-gray-200 rounded-lg p-4">
                        <thead class="bg-gray-200 border border-gray-300">
                            <tr>
                                <th class="text-left p-3 text-sm border-b border-gray-300">{{ __('Product') }}</th>
                                <th class="text-center p-3 text-sm w-36 whitespace-nowrap border-b border-gray-300">{{ __('Quantity') }}</th>
                                <th class="text-right p-3 text-sm w-32 whitespace-nowrap border-b border-gray-300">{{ __('Points') }}</th>
                                <th class="text-right p-3 text-sm w-16 border-b border-gray-300"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingLabels as $row)
                                <tr wire:key="pending-desktop-{{ $row['marketplace_item_id'] }}" class="border-b border-gray-200 py-1">
                                    <td class="text-left p-3 text-sm align-top">
                                        <div class="flex items-start gap-3">
                                            <div class="w-12 h-12 rounded-lg shrink-0 overflow-hidden bg-gray-200 flex items-center justify-center ring-1 ring-gray-200">
                                                @if (! empty($row['image_url']))
                                                    <img src="{{ $row['image_url'] }}" alt="" class="w-full h-full object-cover">
                                                @else
                                                    @php
                                                        $name = (string) ($row['name'] ?? '');
                                                        $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8')) : '?';
                                                    @endphp
                                                    <span class="text-base font-bold text-gray-600">{{ $initial }}</span>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1 space-y-1">
                                                <p class="font-semibold text-gray-900 leading-snug capitalize">{{ $row['name'] }}</p>
                                                @if (! empty($row['description']))
                                                    <p class="text-xs text-gray-500 line-clamp-2 capitalize">{{ $row['description'] }}</p>
                                                @endif
                                                <p class="text-xs text-gray-600 capitalize">
                                                    {{ __('Units per purchase') }}: <span class="font-semibold capitalize">{{ $row['per_item_quantity'] ?? 1 }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center p-3 text-sm whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" wire:click="decrementPendingQty({{ $row['index'] }})" class="text-orange-600 bg-gray-50 hover:bg-gray-100 px-2 rounded-full">−</button>
                                            <span class="tabular-nums w-6 text-center font-semibold">{{ $row['qty'] }}</span>
                                            <button type="button" wire:click="incrementPendingQty({{ $row['index'] }})" class="text-orange-600 bg-gray-50 hover:bg-gray-100 px-2 rounded-full">+</button>
                                        </div>
                                    </td>
                                    <td class="text-right p-3 text-sm whitespace-nowrap tabular-nums text-orange-600 text-lg">{{ number_format($row['points']) }} {{ __('pts') }}</td>
                                    <td class="text-right p-3">
                                        <button type="button" wire:click="removePendingLine({{ $row['index'] }})" class="text-red-600 hover:text-red-800" title="{{ __('Remove') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 bg-orange-50">
                                <td colspan="2" class="text-left p-3 text-xl font-semibold text-orange-600">{{ __('Total Points') }}</td>
                                <td class="text-right p-3 text-xl font-semibold whitespace-nowrap tabular-nums text-orange-600">{{ number_format($pendingPointsTotal) }} {{ __('pts') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:px-0 px-3">
                    <div class="bg-white rounded-xl shadow border border-gray-100 p-5 space-y-4">
                        <h3 class="font-semibold text-gray-900">{{ __('Products Catalog') }}</h3>
                        <div class="grid grid-cols-1 gap-2">
                            <input type="search" wire:model.live.debounce.300ms="catalogSearch" placeholder="{{ __('Search…') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <div class="grid grid-cols-2 gap-2">
                                <select wire:model.live="catalogCategory" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">{{ __('All categories') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <select wire:model.live="catalogLocation" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">{{ __('All locations') }}</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="max-h-[480px] overflow-y-auto space-y-2 pr-1">
                            @forelse ($catalogItems as $item)
                                <div class="flex items-center gap-3 border border-gray-100 rounded-lg p-2">
                                    <div class="w-12 h-12 rounded bg-gray-100 shrink-0 overflow-hidden">
                                        @if ($item->image_url)
                                            <img src="{{ $item->image_url }}" alt="" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $item->per_item_quantity }} x {{ $item->name }}</p>
                                        <p class="text-xs text-orange-600 font-semibold">{{ number_format($item->points_cost) }} {{ __('pts') }}</p>
                                        @if ($item->hasDailyLimit())
                                            <p class="text-xs text-gray-500">{{ __('Daily limit') }}: {{ number_format((int) $item->daily_limit_quantity) }}</p>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="addToBasket({{ $item->id }})"
                                        @if($awaitingMemberPayment) disabled @endif
                                        class="flex items-center shrink-0 p-2 text-sm font-semibold rounded-full bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-50"
                                    >
                                        <svg class="md:w-5 md:h-5 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 py-6 text-center">{{ __('No items match filters.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow border border-gray-100 p-5 space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="font-semibold text-gray-900">{{ __('Products Basket') }}</h3>
                            <button type="button" wire:click="clearBasket" wire:confirm="{{ __('Clear entire basket?') }}" class="@if(empty($basketRows)) hidden @endif text-xs transition-all duration-300 hover:scale-105 cursor-pointer bg-orange-500 text-white px-2 py-1 rounded-full hover:bg-orange-600" @if($awaitingMemberPayment) disabled @endif>{{ __('Clear') }}</button>
                        </div>

                        @if ($basketRows === [])
                            <p class="text-sm text-gray-400 py-8 text-center bg-gray-50">{{ __('Basket is empty.') }}</p>
                        @else
                            <div class="space-y-2 max-h-[360px] overflow-y-auto">
                                @foreach ($basketRows as $row)
                                    @php($line = $row['line'])
                                    @php($item = $row['item'])
                                    <div wire:key="basket-{{ $line['id'] }}" class="grid grid-cols-6 border border-gray-100 rounded-lg p-3 items-center gap-3">
                                        <div class="col-span-4 flex items-start gap-2">
                                            <input
                                                type="checkbox"
                                                class="rounded-none border-gray-300 text-orange-600 focus:ring-0 mt-1"
                                                wire:model.live.boolean="basket.{{ $row['index'] }}.selected"
                                                @if($awaitingMemberPayment) disabled @endif
                                            >
                                            <div class="flex-1 min-w-0">
                                                <p class="text-md font-semibold text-gray-900">{{ $item->per_item_quantity > 1 ? $item->name.'(s)' : $item->name }}</p>
                                                <p class="text-xs text-gray-500">{{ __('Total') }}: {{ number_format($row['line_points']) }} {{ __('points') }}</p>
                                            </div>
                                        </div>
                                        <div class="col-span-1 flex justify-end items-center gap-1">
                                            <button type="button" wire:click="decrementQty('{{ $line['id'] }}')" class="text-orange-600 bg-gray-50 hover:bg-gray-100 px-2 transition-all duration-300 rounded-full cursor-pointer" @if($awaitingMemberPayment) disabled @endif>−</button>
                                            <span class="text-md w-6 text-center">{{ $line['quantity'] }}</span>
                                            <button type="button" wire:click="incrementQty('{{ $line['id'] }}')" class="text-orange-600 bg-gray-50 hover:bg-gray-100 px-2 transition-all duration-300 rounded-full cursor-pointer" @if($awaitingMemberPayment) disabled @endif>+</button>
                                        </div>
                                        <div class="flex flex-wrap gap-2 justify-end">
                                            <button type="button" wire:click="removeLine('{{ $line['id'] }}')" class="text-xs text-red-600 cursor-pointer hover:scale-105 transition-all duration-300" @if($awaitingMemberPayment) disabled @endif>
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t-2 border-gray-400 bg-orange-50 p-2">
                                <span class="text-gray-800">{{ __('Total') }}:</span> 
                                <span class="font-semibold text-orange-600">
                                    {{ number_format($basketTotal) }} {{ __('points') }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-2">
                                <button type="button" wire:click="beginCheckoutSelected" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm font-medium" @if($awaitingMemberPayment) disabled @endif>{{ __('Proceed to Checkout') }}</button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- More than 5 marketplace items: keep member actions below the product list --}}
            @if ($awaitingMemberPayment && count($pendingLabels) > 3)
                <div class="max-w-5xl mx-auto flex md:flex-row flex-col md:gap-2 gap-2 mt-6 md:px-0 px-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="memberQrInput"
                            placeholder="{{ __('Member code') }}"
                            class="w-full lg:p-2 p-3 border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500"
                        >
                        <div wire:loading wire:target="memberQrInput" class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 animate-spin text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    <button type="button" wire:click="confirmPayment" wire:loading.attr="disabled" @if(! empty($dailyLimitWarnings)) disabled @endif class="text-xs lg:p-2 p-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('Confirm Payment') }}
                    </button>
                    <div class="grid grid-cols-2 lg:grid-cols-2 gap-2">
                        <button type="button" wire:click="cancelPayment" class="flex justify-center items-center gap-1 text-xs lg:p-2 p-3 border border-gray-300 rounded-lg bg-gray-200 hover:bg-gray-50">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            {{ __('Back to basket') }}
                        </button>
                        <button type="button" @click="$dispatch('openQrScannerKeepOpen')" class="text-xs lg:p-2 p-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                            {{ __('Scan QR') }}
                        </button>
                    </div>
                </div>
            @endif

            <div class="max-w-5xl mx-auto md:px-4 xl:px-0 px-3 space-y-6">
                @include('livewire.marketplace.partials.favorite-item-cards', [
                    'favoriteItems' => $favoriteItems,
                    'checkoutItemIds' => $checkoutItemIds,
                ])
            </div>
        </div>
    </div>
</div>

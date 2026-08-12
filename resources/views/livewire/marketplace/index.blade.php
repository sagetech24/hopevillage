<div>
    <x-slot name="header">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-between items-center gap-3">
                <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                    {{ __('Marketplace items') }}
                </h2>
                <div class="flex flex-wrap items-center gap-2">
                    @can('marketplace.edit')
                        <a href="{{ route('admin.marketplace.cashier') }}" class="text-sm bg-green-600 hover:bg-green-700 text-white transition-all duration-300 py-2 px-3 rounded-full font-medium hover:text-green-100">
                            {{ __('Cashier Checkout') }}
                        </a>
                    @endcan
                    {{-- @if (auth()->user()?->canAccessAdminMarketplace())
                        <a href="{{ route('admin.marketplace.orders') }}" class="text-sm bg-orange-500 hover:bg-orange-600 text-white transition-all duration-300 py-2 px-3 rounded-full font-medium hover:text-orange-200">
                            {{ __('Confirm Orders') }}
                        </a>
                    @endif --}}
                    @can('marketplace.create')
                        <a href="{{ route('admin.marketplace.create') }}" class="flex items-center gap-1 text-sm bg-orange-600 hover:bg-orange-700 text-white transition-all duration-300 py-2 px-3 rounded-full font-medium hover:text-orange-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span class="">{{ __('Add item') }}</span>
                        </a>
                        <a href="{{ route('admin.marketplace.create') }}" class="md:hidden inline-flex bg-orange-500 hover:bg-orange-600 text-white p-2 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session()->has('message'))
                <div
                    x-data="{ show: @entangle('showMessage').live, timeoutId: null }"
                    x-init="
                        $watch('show', value => {
                            if (value && !timeoutId) { timeoutId = setTimeout(() => { show = false; timeoutId = null; }, 2000); }
                            else if (!value && timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                        });
                        if (show) { timeoutId = setTimeout(() => { show = false; timeoutId = null; }, 3000); }
                    "
                    x-show="show"
                    x-transition
                    class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                    role="alert"
                >
                    <span class="block sm:inline">{{ session('message') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search items…') }}"
                            class="w-full px-4 py-2 border text-gray-800 border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                        >
                    </div>
                    <div>
                        <select wire:model.live="statusFilter" class="w-full px-4 py-2 border text-gray-800 border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500">
                            <option value="all">{{ __('All') }}</option>
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                            <option value="deleted">{{ __('Deleted') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($items as $item)
                    <div class="bg-white rounded-xl shadow-md overflow-hidden flex flex-col h-full">
                        <div class="relative h-80 bg-gray-100 flex items-center justify-center overflow-hidden shrink-0">
                            @if ($item->trashed())
                                <span class="absolute top-2 right-2 shrink-0 text-xs bg-red-200 border border-red-400 text-red-800 px-2 py-0.5 rounded-full">{{ __('Deleted') }}</span>
                            @elseif ($item->is_active && $item->valid_until && $item->valid_until->isFuture())
                                <span class="absolute top-2 right-2 shrink-0 text-xs bg-green-200 border border-green-400 text-green-800 px-2 py-0.5 rounded-full">{{ __('Active') }}</span>
                            @elseif($item->valid_until && $item->valid_until->isPast())
                                <span class="absolute top-2 right-2 shrink-0 text-xs bg-red-200 border border-red-400 text-red-800 px-2 py-0.5 rounded-full">{{ __('Expired') }}</span>
                            @else
                                <span class="absolute top-2 right-2 shrink-0 text-xs bg-gray-200 border border-gray-400 text-gray-800 px-2 py-0.5 rounded-full">{{ __('Inactive') }}</span>
                            @endif
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" class="w-full h-full object-cover">
                            @else
                                <p class="w-full h-full flex flex-col items-center justify-center text-gray-400 text-lg font-medium opacity-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 opacity-50">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    {{ __('No image') }}
                                </p>
                            @endif
                        </div>
                        <div class="p-4 flex flex-col flex-1">
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold text-gray-900 text-xl">{{ $item->name }}</h3>
                                    <p class="text-xs text-gray-500 my-1 line-clamp-2 italic capitalize">{{ $item->description }}</p>
                                </div>
                            </div>
                            <p class="flex items-center flex-wrap">
                                <span class="text-sm text-gray-500">Points: </span>
                                <span class="text-orange-600 text-sm font-bold">{{ number_format($item->points_cost) }} {{ __('pts') }}</span>
                            </p>
                            @if ($item->stock !== null)
                                <p>
                                    <span class="text-sm text-gray-500">{{ __('Remaining Qnty') }}: </span>
                                    <span class="text-orange-600 text-sm font-bold">{{ number_format($item->remainingQuantity()) }}</span>
                                </p>
                            @endif
                            @if ($item->daily_limit_quantity !== null)
                                <p>
                                    <span class="text-sm text-gray-500">{{ __('Daily Limit / User') }}: </span>
                                    <span class="text-orange-600 text-sm font-bold">{{ number_format($item->daily_limit_quantity) }}</span>
                                </p>
                            @endif
                            <p class="text-xs text-gray-500 mt-2 mb-4">
                                @if ($item->category)
                                    <span class="inline-block bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full text-xs capitalize">{{ $item->category?->name }}</span>
                                @else
                                    <span class="inline-block bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs capitalize">{{ __('Uncategorized') }}</span>
                                @endif
                            </p>
                            <div class="mt-auto flex flex-wrap gap-2 border-t border-gray-200 pt-2">
                                @if ($item->trashed())
                                    @can('marketplace.delete')
                                        <button type="button" wire:click="restore({{ $item->id }})" class="text-xs bg-green-600 text-white px-3 py-1.5 rounded-full hover:bg-green-700 hover:text-green-100 transition-all duration-300">{{ __('Restore') }}</button>
                                    @endcan
                                @else
                                    {{-- add a View Inventory button --}}
                                    <a href="{{ route('admin.marketplace.inventory', $item->id) }}" class="flex items-center gap-1 text-xs text-slate-800 hover:text-slate-500 hover:underline transition-all duration-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                                        </svg>
                                        <span>{{ __('Orders') }}</span>
                                    </a>
                                    @can('marketplace.edit')
                                        <span class="text-gray-400">|</span>
                                        <a 
                                            href="{{ route('admin.marketplace.edit', $item->id) }}" 
                                            class="flex items-center gap-1 text-xs text-slate-800 hover:text-slate-500 hover:underline transition-all duration-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.823H3v-3.823L16.862 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                            <span>{{ __('Edit') }}</span>
                                        </a>
                                    @endcan
                                    @can('marketplace.delete')
                                        <span class="text-gray-400">|</span>
                                        <button 
                                            type="button" 
                                            wire:click="delete({{ $item->id }})" 
                                            wire:confirm="{{ __('Archive this item?') }}" 
                                            class="cursor-pointer flex items-center gap-1 text-xs text-red-800 hover:text-red-500 hover:underline transition-all duration-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                            <span>{{ __('Archive') }}</span>
                                        </button>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-600 col-span-full text-center py-12">{{ __('No marketplace items yet.') }}</p>
                @endforelse
            </div>

            @if ($items->total() > 0)
                <div class="mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-sm text-gray-600">
                        {{ __('Showing') }}
                        <span class="font-medium text-gray-900">{{ $items->firstItem() }}</span>
                        {{ __('to') }}
                        <span class="font-medium text-gray-900">{{ $items->lastItem() }}</span>
                        {{ __('of') }}
                        <span class="font-medium text-gray-900">{{ $items->total() }}</span>
                        {{ __('items') }}
                    </p>
                    @if ($items->hasPages())
                        <div>
                            {{ $items->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

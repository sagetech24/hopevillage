<div>
    <x-slot name="header">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-between items-center gap-3">
                <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                    {{ __('Marketplace items') }}
                </h2>
                <div class="flex flex-wrap items-center gap-2">
                    @can('marketplace.edit')
                        <a href="{{ route('admin.marketplace.orders') }}" class="text-sm bg-slate-600 hover:bg-slate-700 text-white transition-all duration-300 py-2 px-3 rounded-full font-medium hover:text-slate-100">
                            {{ __('Go to Inventory') }}
                        </a>
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
                    <div class="flex items-center gap-3">
                        <select wire:model.live="statusFilter" class="w-full px-4 py-2 border text-gray-800 border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500">
                            <option value="all">{{ __('All') }}</option>
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                            <option value="deleted">{{ __('Deleted') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mb-6">
                <div class="inline-flex items-center shrink-0 rounded-full border border-gray-300 p-0.5 bg-white" role="group" aria-label="{{ __('View mode') }}">
                    <button
                        type="button"
                        wire:click="setViewMode('card')"
                        class="inline-flex items-center justify-center p-2 rounded-full transition-all duration-200 {{ $viewMode === 'card' ? 'bg-orange-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 hover:bg-white' }}"
                        title="{{ __('Card view') }}"
                        aria-pressed="{{ $viewMode === 'card' ? 'true' : 'false' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 8.25 20.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        <span class="sr-only">{{ __('Card view') }}</span>
                    </button>
                    <button
                        type="button"
                        wire:click="setViewMode('list')"
                        class="inline-flex items-center justify-center p-2 rounded-full transition-all duration-200 {{ $viewMode === 'list' ? 'bg-orange-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 hover:bg-white' }}"
                        title="{{ __('List view') }}"
                        aria-pressed="{{ $viewMode === 'list' ? 'true' : 'false' }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <span class="sr-only">{{ __('List view') }}</span>
                    </button>
                </div>
            </div>

            @if ($viewMode === 'list')
                <div class="bg-white overflow-hidden shadow-md sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-200/50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Item') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Category') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Points') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Stock') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Daily Limit') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($items as $item)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="h-18 w-18 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                                    @if ($item->image_url)
                                                        <img src="{{ $item->image_url }}" alt="" class="h-full w-full object-cover">
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 text-gray-400">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                        </svg>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $item->name }}</p>
                                                    @if ($item->description)
                                                        <p class="text-xs text-gray-500 truncate italic capitalize w-52">{{ $item->description }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($item->category)
                                                <span class="inline-block bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full text-xs capitalize">{{ $item->category->name }}</span>
                                            @else
                                                <span class="inline-block bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs capitalize">{{ __('Uncategorized') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-orange-600">
                                            {{ number_format($item->points_cost) }} {{ __('pts') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                            @if ($item->stock !== null)
                                                {{ number_format($item->remainingQuantity()) }}
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                            @if ($item->daily_limit_quantity !== null)
                                                {{ number_format($item->daily_limit_quantity) }}
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($item->trashed())
                                                <span class="text-xs bg-red-200 border border-red-400 text-red-800 px-2 py-0.5 rounded-full">{{ __('Deleted') }}</span>
                                            @elseif ($item->is_active && $item->valid_until && $item->valid_until->isFuture())
                                                <span class="text-xs bg-green-200 border border-green-400 text-green-800 px-2 py-0.5 rounded-full">{{ __('Active') }}</span>
                                            @elseif ($item->valid_until && $item->valid_until->isPast())
                                                <span class="text-xs bg-red-200 border border-red-400 text-red-800 px-2 py-0.5 rounded-full">{{ __('Expired') }}</span>
                                            @else
                                                <span class="text-xs bg-gray-200 border border-gray-400 text-gray-800 px-2 py-0.5 rounded-full">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <div
                                                x-data="{
                                                    open: false,
                                                    position: { top: 0, right: 0 },
                                                    setPosition($el) {
                                                        const rect = $el.getBoundingClientRect();
                                                        this.position = {
                                                            top: rect.bottom,
                                                            right: window.innerWidth - rect.right - window.scrollX
                                                        };
                                                    }
                                                }"
                                                class="relative inline-block text-left"
                                                @click.away="open = false"
                                            >
                                                <button
                                                    type="button"
                                                    @click="open = !open; $nextTick(() => { if (open) setPosition($el); })"
                                                    x-ref="button"
                                                    class="inline-flex items-center justify-center text-gray-400 hover:text-gray-600 hover:scale-110 transition-all focus:outline-none focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                                    title="{{ __('Actions') }}"
                                                    aria-label="{{ __('Actions') }}"
                                                    aria-haspopup="true"
                                                    :aria-expanded="open"
                                                >
                                                    <svg class="w-7 h-7 stroke-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                                    </svg>
                                                </button>

                                                <div
                                                    x-show="open"
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="transform opacity-0 scale-95"
                                                    x-transition:enter-end="transform opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="transform opacity-100 scale-100"
                                                    x-transition:leave-end="transform opacity-0 scale-95"
                                                    x-cloak
                                                    :style="`position: fixed; top: ${position.top}px; right: ${position.right}px;`"
                                                    class="w-44 z-[9999] origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                                >
                                                    <div class="py-1" role="menu" aria-orientation="vertical">
                                                        @if ($item->trashed())
                                                            @can('marketplace.delete')
                                                                <button
                                                                    type="button"
                                                                    wire:click="restore({{ $item->id }})"
                                                                    @click="open = false"
                                                                    class="flex w-full items-center gap-3 px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
                                                                    role="menuitem"
                                                                >
                                                                    <svg class="w-3 h-3 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                                                    </svg>
                                                                    <span>{{ __('Restore') }}</span>
                                                                </button>
                                                            @endcan
                                                            <a
                                                                href="{{ route('admin.marketplace.history', $item->id) }}"
                                                                @click="open = false"
                                                                class="flex items-center gap-3 px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 transition-colors"
                                                                role="menuitem"
                                                            >
                                                                <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                                </svg>
                                                                <span>{{ __('History') }}</span>
                                                            </a>
                                                        @else
                                                            <a
                                                                href="{{ route('admin.marketplace.inventory', $item->id) }}"
                                                                @click="open = false"
                                                                class="flex items-center gap-3 px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 transition-colors"
                                                                role="menuitem"
                                                            >
                                                                <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                                                                </svg>
                                                                <span>{{ __('Orders') }}</span>
                                                            </a>
                                                            @can('marketplace.edit')
                                                                <a
                                                                    href="{{ route('admin.marketplace.edit', $item->id) }}"
                                                                    @click="open = false"
                                                                    class="flex items-center gap-3 px-4 py-2 text-xs text-orange-500 hover:bg-gray-100 transition-colors"
                                                                    role="menuitem"
                                                                >
                                                                    <svg class="w-3 h-3 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.823H3v-3.823L16.862 4.487Zm0 0L19.5 7.125" />
                                                                    </svg>
                                                                    <span>{{ __('Edit') }}</span>
                                                                </a>
                                                            @endcan
                                                            @can('marketplace.delete')
                                                                <div class="border-t border-gray-100 my-1"></div>
                                                                <button
                                                                    type="button"
                                                                    wire:click="delete({{ $item->id }})"
                                                                    wire:confirm="{{ __('Archive this item?') }}"
                                                                    @click="open = false"
                                                                    class="flex w-full items-center gap-3 px-4 py-2 text-xs text-red-700 hover:bg-red-50 transition-colors cursor-pointer"
                                                                    role="menuitem"
                                                                >
                                                                    <svg class="w-3 h-3 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                    </svg>
                                                                    <span>{{ __('Archive') }}</span>
                                                                </button>
                                                            @endcan
                                                            <a
                                                                href="{{ route('admin.marketplace.history', $item->id) }}"
                                                                @click="open = false"
                                                                class="flex items-center gap-3 px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 transition-colors"
                                                                role="menuitem"
                                                            >
                                                                <svg class="w-3 h-3 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                                </svg>
                                                                <span>{{ __('History') }}</span>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-12 text-center text-gray-600">{{ __('No marketplace items yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
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
                                        <a href="{{ route('admin.marketplace.history', $item->id) }}" class="flex items-center gap-1 text-xs text-slate-800 hover:text-slate-500 hover:underline transition-all duration-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            <span>{{ __('History') }}</span>
                                        </a>
                                    @else
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
                                                class="flex items-center gap-1 text-xs text-slate-800 hover:text-slate-500 hover:underline transition-all duration-300"
                                            >
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
                                                class="cursor-pointer flex items-center gap-1 text-xs text-red-800 hover:text-red-500 hover:underline transition-all duration-300"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                                <span>{{ __('Archive') }}</span>
                                            </button>
                                        @endcan
                                        <span class="text-gray-400">|</span>
                                        <a href="{{ route('admin.marketplace.history', $item->id) }}" class="flex items-center gap-1 text-xs text-slate-800 hover:text-slate-500 hover:underline transition-all duration-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            <span>{{ __('History') }}</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-600 col-span-full text-center py-12">{{ __('No marketplace items yet.') }}</p>
                    @endforelse
                </div>
            @endif

            @if ($items->hasPages())
                <div class="mt-8">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

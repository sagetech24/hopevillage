<x-slot name="header">
    @livewire('member.points-header')
</x-slot>

<div class="max-w-md mx-auto min-h-screen pb-20 px-4 py-6 space-y-6">
    <div class="space-y-2">
        <label class="relative block">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
            </svg>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search marketplace items…') }}"
                class="w-full pl-10 py-3 border border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
            />
        </label>
        <select wire:model.live="categoryFilter" class="w-full capitalize px-3 py-2 border border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm">
            <option value="">{{ __('All categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex justify-between items-center gap-2 flex-wrap">
        <h2 class="text-xl font-bold text-gray-900">{{ __('Shop') }}</h2>
        <a href="{{ route('member.marketplace.my-orders') }}" class="flex gap-1 items-center border border-orange-600 rounded-lg px-2 py-1 text-orange-600 transition-all duration-300 text-sm font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 9 19.5l8.25-6.75m0 0L21 8.25M12.75 8.25 12 2.25c0-.966-.784-1.75-1.75-1.75H8.75C7.784 0.5 7 1.284 7 2.25v1.5m8.25 0 0 3m0 0 0 3m0-3 0 3m0-3-3.75-3.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ __('My Orders') }}
        </a>
    </div>

    <div class="grid grid-cols-2 gap-2">
        @forelse ($items as $item)
            <div class="bg-white rounded-xl shadow border border-gray-300 overflow-hidden flex flex-col sm:flex-row">
                <div class="relative sm:w-36 h-52 sm:h-auto shrink-0 bg-orange-400">
                    @if ($item->image_url)
                        <img src="{{ $item->image_url }}" alt="" class="w-full h-full object-cover bg-orange-200">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">{{ __('No image') }}</div>
                    @endif
                    <p class="text-white font-bold mt-2 absolute top-0 right-0 text-xs shadow bg-orange-600 p-1">{{ number_format($item->points_cost) }} {{ __('pts') }}</p>
                </div>
                <div class="p-4 flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900">{{ $item->name }}</h3>
                        @if ($item->description)
                            <p class="text-xs text-gray-600 mt-1 line-clamp-2 capitalize">{{ $item->description }}</p>
                        @endif
                        @if ($item->category)
                            <span class="inline-block bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full text-xs capitalize">{{ $item->category?->name }}</span>
                        @else
                            <span class="inline-block bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs capitalize">{{ __('Uncategorized') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-gray-600 py-12 col-span-full">{{ __('No items available right now.') }}</p>
        @endforelse
    </div>
</div>

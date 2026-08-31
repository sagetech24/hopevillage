@php
    $requiredPermission = $itemId ? 'marketplace.edit' : 'marketplace.create';
@endphp

@if (Illuminate\Support\Facades\Gate::allows($requiredPermission))
    <x-slot name="header">
        <div class="max-w-5xl mx-auto px-4 md:px-0 lg:px-0 flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800">
                {{ $itemId ? __('Edit Marketplace Item') : __('New Marketplace Item') }}
            </h2>
            <a href="{{ route('admin.marketplace.index') }}" class="text-orange-500 font-medium hover:text-orange-600 text-sm">{{ __('← Back') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto px-4 md:px-0 lg:px-0">
            <form wire:submit="save" class="bg-white shadow-md rounded-lg p-6 space-y-6">
                <div class="grid md:grid-cols-4 grid-cols-1 md:gap-4 gap-y-4">
                    <div class="col-span-1">
                        <span class="block text-sm font-medium text-gray-700">{{ __('Update Image') }}</span>
                        <label for="itemImage" class="mt-2 block cursor-pointer">
                            <div class="relative aspect-square w-full overflow-hidden rounded-lg border-2 border-dashed border-gray-300 bg-gray-50">
                                @if ($itemImage)
                                    <img src="{{ $itemImage->temporaryUrl() }}" alt="" class="h-full w-full object-cover">
                                @elseif ($existingItemImage)
                                    <img src="{{ $existingItemImage }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full flex-col items-center justify-center gap-2 p-4 text-center">
                                        <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="text-sm text-gray-500">{{ __('Click to upload image') }}</p>
                                    </div>
                                @endif
                                <div wire:loading wire:target="itemImage" class="absolute inset-0 flex items-center justify-center bg-white/80 text-sm text-gray-600">
                                    {{ __('Uploading…') }}
                                </div>
                            </div>
                        </label>
                        <input type="file" id="itemImage" wire:model="itemImage" accept="image/*" class="hidden">
                        @if ($itemImage || $existingItemImage)
                            <button type="button" wire:click="removeItemImage" class="mt-2 text-sm text-red-600 hover:underline">{{ __('Remove image') }}</button>
                        @endif
                        @error('itemImage') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-3 flex flex-col gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Name') }} *</label>
                            <input type="text" wire:model="name" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
                            <textarea wire:model="description" rows="4" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"></textarea>
                            @error('description') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
        
                        <div class="flex flex-col gap-4">
                            <fieldset class="border border-gray-300 rounded-md p-4">
                                <legend class="text-sm font-medium text-gray-700">{{ __('Item Costing and Settings') }}</legend>
                                <ul class="text-xs text-gray-500 list-disc list-inside ml-3">
                                    <li class="mb-1 -ml-2">{{ __('The stock is the total quantity of the item in stock.') }}</li>
                                    <li class="mb-1 -ml-2">{{ __('Enable the checkbox to allow the item to have unlimited stock.') }}</li>
                                    <li class="mb-1 -ml-2">{{ __('Cost per item is auto-computed as points per item × cost per point.') }}</li>
                                    <li class="mb-1 -ml-2">{{ __('Points per item is the points value of the item to be redeemed.') }}</li>
                                    {{-- <li class="mb-1 -ml-2">{{ __('Auto-compute points cost based on item cost & stock quantity. Uncheck to enter points manually.') }}</li> --}}
                                </ul>
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label class="block text-sm font-medium text-gray-700">{{ __('Stock') }}</label>
                                        <input type="number" min="0" wire:model.live="stockInput" placeholder="{{ __('Maximum Quantity') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="block text-sm font-medium text-gray-700">{{ __('Points per Item') }} 
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            wire:model.live="points_cost"
                                            wire:blur="normalizeEmptyPointsCost"
                                            @if ($compute_points_cost) readonly @endif
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 {{ $compute_points_cost ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                        >
                                        @error('points_cost') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="block text-sm font-medium text-gray-700">{{ __('Cost per Point') }}</label>
                                        <div class="relative">
                                            <input type="number" min="0" step="0.01" placeholder="0.00" wire:model.live="amount_cost" class="pl-12 w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            <div class="absolute left-1 top-0 bottom-0 flex items-center justify-center">
                                                <span class="text-sm text-gray-500">`
                                                    {{ __('SGD') }}
                                                </span>
                                            </div>
                                        </div>
                                        @error('amount_cost') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="block text-sm font-medium text-gray-700">{{ __('Cost per Item') }}</label>
                                        <div class="relative">
                                            <input
                                                type="text"
                                                readonly
                                                value="{{ number_format($this->computedCostPerItem(), 2, '.', '') }}"
                                                class="pl-12 w-full rounded-md border-gray-300 bg-gray-100 cursor-not-allowed shadow-sm"
                                            >
                                            <div class="absolute left-3 top-1 bottom-0 flex items-center justify-center">
                                                <span class="text-sm text-gray-500">
                                                    {{ __('SGD') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2 mt-4">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model.live="unlimited_stock" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                        <span class="text-sm font-medium text-gray-700 whitespace-nowrap">{{ __('Unlimited quantity') }}</span>
                                    </label>
                                    @error('stockInput') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                    
                                    {{-- <label class="flex items-center gap-2 {{ $unlimited_stock ? 'opacity-50' : '' }}">
                                        <input
                                            type="checkbox"
                                            wire:model.live="compute_points_cost"
                                            @disabled($unlimited_stock)
                                            class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                                        >
                                        <span class="text-sm font-medium text-gray-700 whitespace-nowrap">{{ __('Auto-compute points cost based on item cost & stock quantity.') }}</span>
                                    </label> --}}
                                    {{-- @if ($compute_points_cost)
                                        <p class="text-xs text-gray-500">{{ __('Points = item cost × stock quantity, rounded to a whole number. Uncheck to enter points manually.') }}</p>
                                    @endif --}}
                                    {{-- @error('compute_points_cost') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror --}}
                                </div>
                                <div class="mt-2 flex flex-col gap-2">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model.live="no_daily_limit" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                        <span class="text-sm font-medium text-gray-700">{{ __('No daily limit') }}</span>
                                    </label>
                                    @if (! $no_daily_limit)
                                        <input type="number" min="1" wire:model="dailyLimitInput" placeholder="{{ __('Max quantity per member per day') }}" class="mt-2 w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                        <p class="text-xs text-gray-500 mt-1">{{ __('A member who reaches this quantity must wait until tomorrow to redeem this item again.') }}</p>
                                        @error('dailyLimitInput') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                    @endif
                                </div>
                            </fieldset>
                        </div>
                        <fieldset class="border border-gray-300 rounded-md p-4">
                            <legend class="text-sm font-medium text-gray-700">{{ __('Availability Settings') }}</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Valid from') }}</label>
                                    <input type="datetime-local" wire:model="valid_from" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    @error('valid_from') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Valid until') }}</label>
                                    <input type="datetime-local" wire:model="valid_until" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    @error('valid_until') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </fieldset>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                {{ __('Category') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="marketplace_category_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">{{ __('Select category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('marketplace_category_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                            <input type="text" wire:model="new_category_name" placeholder="{{ __('Or add new category') }}" class="mt-2 w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                            @error('new_category_name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
        
                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="text-sm font-medium text-gray-700">{{ __('Active (visible to members)') }}</span>
                            </label>
                        </div>

                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model.live="available_in_all_locations" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="text-sm font-medium text-gray-700">{{ __('Available in all locations') }}</span>
                            </label>
                        </div>
                        @if (! $available_in_all_locations)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Available locations') }} *</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach ($locations as $location)
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" wire:model="selectedLocations" value="{{ $location->id }}" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                            <span>{{ $location->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('selectedLocations') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
        
                        <div class="flex justify-end gap-3">
                            <a href="{{ route('admin.marketplace.index') }}" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">{{ __('Cancel') }}</a>
                            <button type="submit" class="px-4 py-2 rounded-md bg-orange-500 text-white font-medium hover:bg-orange-600 focus:ring-2 focus:ring-orange-500">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </div>
                    
                </div>
            </form>

            <x-confirmation-modal wire:model.live="confirmingZeroPoints">
                <x-slot name="title">
                    {{ __('Confirm 0 points') }}
                </x-slot>

                <x-slot name="content">
                    {{ $itemId
                        ? __('You are setting the points cost to 0. Members will be able to redeem this item without spending points. Are you sure you want to save these changes?')
                        : __('You are setting the points cost to 0. Members will be able to redeem this item without spending points. Are you sure you want to save this item?')
                    }}
                </x-slot>

                <x-slot name="footer">
                    <x-secondary-button wire:click="$set('confirmingZeroPoints', false)" wire:loading.attr="disabled">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <button
                        type="button"
                        wire:click="confirmZeroPointsAndSave"
                        wire:loading.attr="disabled"
                        class="ms-3 px-4 py-2 rounded-md bg-orange-500 text-white font-medium hover:bg-orange-600 focus:ring-2 focus:ring-orange-500 disabled:opacity-50"
                    >
                        {{ __('Confirm') }}
                    </button>
                </x-slot>
            </x-confirmation-modal>
        </div>
    </div>
@else
    @php abort(403, 'Unauthorized.'); @endphp
@endif

@props(['favoriteItems', 'checkoutItemIds' => []])

@if ($favoriteItems->isNotEmpty())
    <div class="mt-8 pt-6 border-t border-gray-200">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('Favorite Items') }}</h3>
            <p class="text-sm text-gray-600">{{ __('Quick access to items you starred on the marketplace list.') }}</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($favoriteItems as $item)
                @php($inCheckout = in_array($item->id, $checkoutItemIds, true))
                <div wire:key="cashier-favorite-{{ $item->id }}" class="relative min-h-[300px] rounded-xl overflow-hidden border border-gray-200 shadow-sm">
                    {{-- Background image --}}
                    <div class="absolute inset-0 bg-gradient-to-b from-gray-900/50 via-gray-600/50 to-transparent">
                        @if ($item->image_url)
                            <img src="{{ $item->image_url }}" alt="" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-12 opacity-40">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Dark fade overlay (always visible) --}}
                    <div class="absolute inset-0 bg-gray-900/75"></div>

                    {{-- Action buttons — centered on card --}}
                    <div class="absolute inset-0 z-30 flex items-start justify-center p-3">
                        <div class="flex w-full max-w-[180px] flex-col gap-2 mt-8">
                            <button
                                type="button"
                                wire:click="addFavoriteToBasket({{ $item->id }})"
                                @disabled($inCheckout)
                                class="flex w-full lg:items-center items-start cursor-pointer hover:scale-105 duration-200 transition-all justify-center lg:gap-1 gap-0 rounded-lg bg-orange-500 p-2.5 lg:text-sm text-xs font-semibold text-white shadow-md hover:bg-orange-600 disabled:hover:scale-100 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $inCheckout ? __('Already Added') : __('Add to basket') }}
                            </button>
                            <button
                                type="button"
                                wire:click="replaceBasketWithItem({{ $item->id }})"
                                @if (count($checkoutItemIds) > 0 && ! ($inCheckout && count($checkoutItemIds) === 1))
                                    wire:confirm="{{ __('Clear the checkout and add only this item?') }}"
                                @endif
                                class="flex w-full lg:items-center items-start cursor-pointer hover:scale-105 duration-200 transition-all justify-center lg:gap-1 gap-0 rounded-lg bg-white p-2.5 lg:text-sm text-xs font-semibold text-gray-900 shadow-md hover:bg-gray-100"
                            >
                                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                {{ __('Clear basket & add') }}
                            </button>
                        </div>
                    </div>

                    {{-- Item details --}}
                    <div class="relative z-20 flex min-h-[280px] flex-col justify-between p-3 pointer-events-none">
                        <div class="flex items-start justify-between gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full bg-black/40 px-2 py-0.5 text-xs font-medium text-amber-300 backdrop-blur-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-3.5">
                                    <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" />
                                </svg>
                                {{ __('Favorite') }}
                            </span>
                            @if ($inCheckout)
                                <span class="rounded-full bg-green-500/90 px-2 py-0.5 text-xs font-medium text-white">{{ __('Added') }}</span>
                            @endif
                        </div>

                        <div class="space-y-1 text-white">
                            <h4 class="font-semibold capitalize line-clamp-2 drop-shadow-sm">{{ $item->name }}</h4>
                            <p class="text-sm">
                                <span class="text-white/80">{{ __('Points') }}:</span>
                                <span class="font-bold text-orange-300">{{ number_format($item->points_cost) }} {{ __('pts') }}</span>
                            </p>
                            @if ($item->stock !== null)
                                <p class="text-xs text-white/75">
                                    {{ __('Remaining Qnty') }}: <span class="font-semibold text-white">{{ number_format($item->remainingQuantity()) }}</span>
                                </p>
                            @endif
                            @if ($item->category)
                                <p class="pt-1">
                                    <span class="inline-block rounded-full bg-white/15 px-2 py-0.5 text-xs capitalize text-white backdrop-blur-sm">{{ $item->category->name }}</span>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

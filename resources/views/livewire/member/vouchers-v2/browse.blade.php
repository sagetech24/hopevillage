<div
    wire:poll.5s
    class="space-y-10"
>
    <!-- Available Merchant Vouchers (carousel) -->
    <div>
        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-800 mb-1">Available Vouchers</h2>
            <p class="text-xs text-gray-600">Claim these vouchers from our partner merchants</p>
        </div>

        <div
            class="flex flex-nowrap gap-4 min-h-[200px] items-start overflow-x-auto overflow-y-hidden pb-4 scrollbar-hide snap-x snap-mandatory"
            x-data="{
                startX: null,
                onTouchStart(e) { this.startX = e.touches?.[0]?.clientX ?? null },
                onTouchEnd(e) {
                    if (this.startX === null) return;
                    const endX = e.changedTouches?.[0]?.clientX ?? null;
                    if (endX === null) return;
                    this.startX = null;
                },
            }"
            @touchstart.passive="onTouchStart($event)"
            @touchend.passive="onTouchEnd($event)"
        >
            @if($this->claimableVouchers->isNotEmpty() || $this->claimableAdminVouchers->isNotEmpty())
                @foreach($this->claimableVouchers as $voucher)
                    <div class="shrink-0 w-48 snap-center">
                        <div class="h-full border border-orange-200 rounded-2xl bg-white overflow-hidden opacity-90 hover:opacity-100 transition-all duration-300 hover:shadow-lg">
                            @if($voucher->image_url)
                                <div class="relative h-20 overflow-hidden">
                                    <img src="{{ $voucher->image_url }}" alt="{{ $voucher->name }}" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-linear-to-t from-orange-900/40 to-transparent"></div>
                                    <div class="absolute top-2 right-2">
                                        <span class="px-2 py-1 bg-green-500 text-white text-[11px] font-semibold rounded-full">Available</span>
                                    </div>
                                </div>
                            @else
                                <div class="relative h-20 bg-linear-to-br from-orange-400 via-orange-300 to-orange-200 flex items-center justify-center">
                                    <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-orange-600">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M16 2H0V6C1.10457 6 2 6.89543 2 8C2 9.10457 1.10457 10 0 10V14H16V10C14.8954 10 14 9.10457 14 8C14 6.89543 14.8954 6 16 6V2ZM8 10C9.10457 10 10 9.10457 10 8C10 6.89543 9.10457 6 8 6C6.89543 6 6 6.89543 6 8C6 9.10457 6.89543 10 8 10Z" fill="currentColor"></path>
                                    </svg>
                                    <div class="absolute top-2 right-2">
                                        <span class="px-2 py-1 bg-green-500 text-white text-[11px] font-semibold rounded-full">Available</span>
                                    </div>
                                </div>
                            @endif
                            <div class="p-3">
                                <h3 class="font-bold text-md text-gray-800 mb-1 leading-tight">{{ $voucher->name }}</h3>
                                <p class="text-xs text-gray-600 mb-2">{{ $voucher->merchant->name ?? 'Merchant' }}</p>
                                <div class="flex items-center justify-between text-xs text-orange-600 font-semibold mb-2">
                                    @if($voucher->discount_type === 'percentage')
                                        {{ $voucher->discount_value }}% OFF
                                    @elseif($voucher->discount_type === 'item')
                                        Free Item
                                    @else
                                        ${{ number_format($voucher->discount_value, 2) }} OFF
                                    @endif
                                </div>
                                <button
                                    type="button"
                                    wire:click="claim({{ $voucher->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="claim({{ $voucher->id }})"
                                    class="w-full px-3 py-2 bg-linear-to-r from-orange-500 to-orange-600 text-white text-xs font-semibold rounded-full hover:from-orange-600 hover:to-orange-700 transition-all duration-200 flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span wire:loading.remove wire:target="claim({{ $voucher->id }})">Claim</span>
                                    <span wire:loading wire:target="claim({{ $voucher->id }})" class="flex items-center gap-1">
                                        <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Claiming...
                                    </span>
                                </button>
                            </div>
                            <div class="text-xs text-gray-500 mt-2 py-2 border-t border-gray-200 px-3">
                                Valid Until: {{ $voucher->valid_until?->format('d M Y') ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                @endforeach

                @foreach($this->claimableAdminVouchers as $adminVoucher)
                    <div class="shrink-0 w-48 snap-center">
                        <div class="h-full border border-blue-200 rounded-2xl bg-white overflow-hidden opacity-90 hover:opacity-100 transition-all duration-300 hover:shadow-lg">
                            @if($adminVoucher->image_url)
                                <div class="relative h-20 overflow-hidden">
                                    <img src="{{ $adminVoucher->image_url }}" alt="{{ $adminVoucher->name }}" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-linear-to-t from-blue-900/40 to-transparent"></div>
                                    <div class="absolute top-2 right-2">
                                        <span class="px-2 py-1 bg-green-500 text-white text-[11px] font-semibold rounded-full">Available</span>
                                    </div>
                                </div>
                            @else
                                <div class="relative h-20 bg-linear-to-br from-blue-400 via-blue-300 to-blue-200 flex items-center justify-center">
                                    <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-blue-600/70">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M16 2H0V6C1.10457 6 2 6.89543 2 8C2 9.10457 1.10457 10 0 10V14H16V10C14.8954 10 14 9.10457 14 8C14 6.89543 14.8954 6 16 6V2ZM8 10C9.10457 10 10 9.10457 10 8C10 6.89543 9.10457 6 8 6C6.89543 6 6 6.89543 6 8C6 9.10457 6.89543 10 8 10Z" fill="currentColor"></path>
                                    </svg>
                                    <div class="absolute top-2 right-2">
                                        <span class="px-2 py-1 bg-green-500 text-white text-[11px] font-semibold rounded-full">Available</span>
                                    </div>
                                </div>
                            @endif
                            <div class="p-3 flex flex-col min-h-[120px]">
                                <h3 class="font-bold text-md text-gray-800 mb-1 leading-tight">{{ $adminVoucher->name }}</h3>
                                <p class="text-xs text-gray-600 mb-1 leading-tight">{{ $adminVoucher->description }}</p>
                                @if($adminVoucher->merchants->isNotEmpty())
                                    <p class="text-xs text-gray-500 mb-2">Redeemable at {{ $adminVoucher->merchants->pluck('name')->join(', ') }}</p>
                                @endif
                                @php
                                    $currentUser = auth()->user();
                                    $hasEnoughPoints = $currentUser && $currentUser->total_points >= $adminVoucher->points_cost;
                                @endphp
                                @if($currentUser)
                                    <button
                                        type="button"
                                        wire:click="claimAdminVoucher({{ $adminVoucher->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="claimAdminVoucher({{ $adminVoucher->id }})"
                                        @class([
                                            'w-full shrink-0 min-h-[40px] cursor-pointer text-xs rounded-full transition-all duration-200 flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed',
                                            'bg-gradient-to-r from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700' => $hasEnoughPoints,
                                            'bg-gray-300 text-gray-500 cursor-not-allowed' => !$hasEnoughPoints,
                                        ])
                                        @if(!$hasEnoughPoints) disabled title="Insufficient points. You need {{ number_format($adminVoucher->points_cost) }} points." @endif
                                    >
                                        @if($hasEnoughPoints)
                                            <span class="flex items-center gap-1" wire:loading.remove wire:target="claimAdminVoucher({{ $adminVoucher->id }})">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3 stroke-white shrink-0">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                                </svg>
                                                Exchange {{ number_format($adminVoucher->points_cost) }} point(s)
                                            </span>
                                            <span wire:loading wire:target="claimAdminVoucher({{ $adminVoucher->id }})" class="flex items-center gap-1">
                                                <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Exchanging...
                                            </span>
                                        @else
                                            Insufficient Points
                                        @endif
                                    </button>
                                @else
                                    <button type="button" disabled class="w-full shrink-0 min-h-[40px] px-3 py-2.5 bg-gray-300 text-gray-500 text-xs font-semibold rounded-xl cursor-not-allowed mt-auto">
                                        Login to Claim
                                    </button>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 py-2 border-t border-gray-200 px-3">
                                Valid Until: {{ $adminVoucher->valid_until?->format('d M Y') ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="w-full border-2 border-dashed border-gray-300 rounded-2xl bg-gray-50/50 p-8 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h7zm-7 4a2 2 0 100 4h7a2 2 0 100-4M7 21h10a2 2 0 002-2V9a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-gray-500 font-medium">No claimable vouchers available</p>
                    <p class="text-sm text-gray-400 mt-1">Check back later for new voucher releases</p>
                </div>
            @endif
        </div>
    </div>
</div>



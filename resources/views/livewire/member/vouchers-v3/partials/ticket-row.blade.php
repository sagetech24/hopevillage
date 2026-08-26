@props([
    'item',
    'tab',
    'userPoints' => 0,
])

@php
    $isAdmin = $item->type === 'admin';
    $accent = $isAdmin ? 'teal' : 'orange';
    $leftBg = $isAdmin ? 'bg-teal-500' : 'bg-orange-500';
    $percentColor = $isAdmin ? 'text-teal-500' : 'text-orange-500';
    $btnBorder = $isAdmin ? 'border-teal-500 text-teal-600' : 'border-orange-500 text-orange-600';
    $merchantLabel = $item->merchant_name ?: 'All Sellers';
    $hasEnoughPoints = ! $isAdmin || $userPoints >= (int) ($item->points_cost ?? 0);
    $cannotAfford = $isAdmin && $tab === 'active' && ! $hasEnoughPoints;
    $claimedOn = ! empty($item->claimed_at ?? null)
        ? \Carbon\Carbon::parse($item->claimed_at)->format('d/m/Y g:i A')
        : 'N/A';
    $redeemedOn = ! empty($item->redeemed_at ?? null)
        ? \Carbon\Carbon::parse($item->redeemed_at)->format('d/m/Y g:i A')
        : 'N/A';
@endphp

<div
    class="bg-white rounded-r-lg border border-gray-400 overflow-hidden w-full"
    style="-webkit-mask-image: radial-gradient(circle 5px at left center, transparent 5px, #000 5.5px); -webkit-mask-size: 100% 16px; -webkit-mask-repeat: repeat-y; mask-image: radial-gradient(circle 5px at left center, transparent 5px, #000 5.5px); mask-size: 100% 16px; mask-repeat: repeat-y;{{ $cannotAfford ? ' opacity: 0.75; filter: grayscale(100%);' : '' }}"
>
    <div class="flex min-h-32">
        <div class="w-[100px] min-w-[100px] {{ $leftBg }} text-white pl-3.5 pr-2 py-3 flex flex-col items-center justify-center text-center">
            <div class="relative h-11 w-11">
                <span class="absolute inset-0 flex items-center justify-center text-xl font-bold leading-none text-white">%</span>
            </div>
            <p class="text-[0.75rem] leading-tight font-semibold font-sans px-0.5">{{ $merchantLabel }}</p>
        </div>

        <div class="flex p-4 flex-col items-start justify-between gap-3 w-full">
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900 leading-tight">{{ $item->name }}</p>

                {{-- Admin Voucher --}}
                @if($isAdmin)
                    @if($tab === 'active')
                        <p class="text-sm text-gray-700 mt-1 font-semibold">Points Cost: {{ number_format($item->points_cost ?? 0) }}</p>
                    @elseif($tab === 'claimed')
                        <p class="text-gray-600 mt-1 text-[0.7rem] tracking-normal">Claimed On {{ $claimedOn }}</p>
                    @elseif($tab === 'redeemed')
                        @if(!empty($item->redeemed_at_merchant))
                            <p class="text-sm text-gray-600 mt-1">Redeemed at {{ $item->redeemed_at_merchant }}</p>
                        @endif
                    @endif
                    @if($tab !== 'redeemed' && !empty($item->valid_until))
                        <p class="text-xs text-gray-500 mt-0.5">
                            <span class="text-gray-500 font-normal text-xs">Valid Until:</span>
                            <span class="text-gray-700 font-normal text-xs">{{ \Carbon\Carbon::parse($item->valid_until)->format('d/m/Y g:i A') }}</span>
                        </p>
                    @endif
                {{-- Merchant Voucher --}}
                @else
                    @if($tab === 'active')
                        @if($item->discount_type === 'percentage')
                            <p class="text-xs text-gray-700 mt-1">{{ rtrim(rtrim((string) $item->discount_value, '0'), '.') }}% off</p>
                        @else
                            <p class="text-xs text-gray-700 mt-1">${{ number_format((float) $item->discount_value, 2) }} off</p>
                        @endif
                        @if(!is_null($item->min_purchase))
                            <p class="text-gray-600 mt-1 text-[0.7rem]">Min. Spend ${{ number_format((float) $item->min_purchase, 0) }}</p>
                        @endif
                    @elseif($tab === 'claimed')
                        <p class="text-gray-600 mt-1 text-[0.7rem] tracking-normal">Claimed On {{ $claimedOn }}</p>
                        {{-- <p class="text-[0.7rem] text-gray-500 mt-1">Tap Use Now to show QR code</p> --}}
                    @elseif($tab === 'redeemed')
                        @if(!empty($item->redeemed_at_merchant))
                            <p class="text-sm text-gray-600 mt-1">Redeemed at {{ $item->redeemed_at_merchant }}</p>
                        @endif
                    @endif
                @endif
            </div>

            <div class="flex items-center justify-between w-full pr-2 mt-auto">
                @if($tab === 'redeemed')
                    <p class="text-gray-500 text-[0.7rem]">Redeemed on {{ $redeemedOn }}</p>
                @endif
                <div class="flex items-center gap-2 shrink-0">
                    @if($isAdmin)
                        @if($tab === 'active')
                            <button
                                type="button"
                                wire:click="openClaimConfirm({{ $item->id }}, 'admin')"
                                wire:loading.attr="disabled"
                                wire:target="openClaimConfirm({{ $item->id }}, 'admin')"
                                @class([
                                    'py-1 px-3 rounded-full border text-xs font-semibold transition-colors',
                                    $btnBorder => $hasEnoughPoints,
                                    'border-gray-300 text-gray-400 cursor-not-allowed' => !$hasEnoughPoints,
                                ])
                                @if(!$hasEnoughPoints) disabled @endif
                            >
                                @if($hasEnoughPoints)
                                    <span wire:loading.remove wire:target="openClaimConfirm({{ $item->id }}, 'admin')">Claim Now</span>
                                    <span wire:loading wire:target="openClaimConfirm({{ $item->id }}, 'admin')">...</span>
                                @else
                                    Not enough points
                                @endif
                            </button>
                        @elseif($tab === 'claimed')
                            <button
                                type="button"
                                wire:click="showClaimedQr('{{ $item->voucher_code }}', '{{ $item->type }}')"
                                class="py-1 px-3 rounded-full text-xs font-semibold bg-teal-600 text-white"
                            >
                                Use Now
                            </button>
                        @endif
                    @else
                        @if($tab === 'active')
                            <button
                                type="button"
                                wire:click="openClaimConfirm({{ $item->id }}, 'merchant')"
                                wire:loading.attr="disabled"
                                wire:target="openClaimConfirm({{ $item->id }}, 'merchant')"
                                class="py-1 px-3 rounded-full border {{ $btnBorder }} text-xs font-semibold transition-colors"
                            >
                                <span wire:loading.remove wire:target="openClaimConfirm({{ $item->id }}, 'merchant')">Claim Now</span>
                                <span wire:loading wire:target="openClaimConfirm({{ $item->id }}, 'merchant')">...</span>
                            </button>
                        @elseif($tab === 'claimed')
                            <button
                                type="button"
                                wire:click="showClaimedQr('{{ $item->voucher_code }}', '{{ $item->type }}')"
                                class="py-1 px-3 rounded-full border {{ $btnBorder }} text-xs font-semibold bg-orange-400 text-white"
                            >
                                Use Now
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

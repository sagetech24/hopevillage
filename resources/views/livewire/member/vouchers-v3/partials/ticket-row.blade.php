@props([
    'item',
    'tab',
    'userPoints' => 0,
])

@php
    $isAdmin = $item->type === 'admin';
    $btnBorder = $isAdmin ? 'border-teal-500 text-teal-600' : 'border-orange-500 text-orange-600';
    $merchantLabel = $item->merchant_name ?: 'All Sellers';
    $hasEnoughPoints = ! $isAdmin || $userPoints >= (int) ($item->points_cost ?? 0);
    $cannotAfford = $isAdmin && $tab === 'active' && ! $hasEnoughPoints;
    $isRedeemed = $tab === 'redeemed';
    $claimedOn = ! empty($item->claimed_at ?? null)
        ? \Carbon\Carbon::parse($item->claimed_at)->format('d/m/Y g:i A')
        : 'N/A';
    $redeemedOn = ! empty($item->redeemed_at ?? null)
        ? \Carbon\Carbon::parse($item->redeemed_at)->format('d/m/Y g:i A')
        : 'N/A';
@endphp

<x-voucher.ticket
    :type="$isAdmin ? 'admin' : 'merchant'"
    :merchant-label="$merchantLabel"
    :dimmed="$cannotAfford || $isRedeemed"
>
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
            @elseif($item->discount_type === 'item')
                <p class="text-xs text-gray-700 mt-1">Free Item</p>
            @else
                <p class="text-xs text-gray-700 mt-1">${{ number_format((float) $item->discount_value, 2) }} off</p>
            @endif
            @if(!is_null($item->min_purchase))
                <p class="text-gray-600 mt-1 text-[0.7rem]">Min. Spend ${{ number_format((float) $item->min_purchase, 0) }}</p>
            @endif
        @elseif($tab === 'claimed')
            <p class="text-gray-600 mt-1 text-[0.7rem] tracking-normal">Claimed On {{ $claimedOn }}</p>
        @elseif($tab === 'redeemed')
            @if(!empty($item->redeemed_at_merchant))
                <p class="text-sm text-gray-600 mt-1">Redeemed at {{ $item->redeemed_at_merchant }}</p>
            @endif
        @endif
    @endif

    <x-slot:meta>
        @if($tab === 'redeemed')
            <p class="text-gray-500 text-[0.7rem]">Redeemed on {{ $redeemedOn }}</p>
        @endif
    </x-slot:meta>

    <x-slot:actions>
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
                @if($this->canUseAdminTestRedeem())
                    <button
                        type="button"
                        wire:click="adminTestRedeem({{ $item->id }}, '{{ $item->type }}')"
                        wire:confirm="Mark this claimed voucher as redeemed? Admin/dev testing only."
                        wire:loading.attr="disabled"
                        wire:target="adminTestRedeem({{ $item->id }}, '{{ $item->type }}')"
                        class="py-1 px-3 rounded-full border border-dashed border-gray-400 text-xs font-semibold text-gray-600 whitespace-nowrap"
                        title="Admin/dev testing only — hidden in production"
                    >
                        <span wire:loading.remove wire:target="adminTestRedeem({{ $item->id }}, '{{ $item->type }}')">Redeem</span>
                        <span wire:loading wire:target="adminTestRedeem({{ $item->id }}, '{{ $item->type }}')">...</span>
                    </button>
                @endif
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
                @if($this->canUseAdminTestRedeem())
                    <button
                        type="button"
                        wire:click="adminTestRedeem({{ $item->id }}, '{{ $item->type }}')"
                        wire:confirm="Mark this claimed voucher as redeemed? Admin/dev testing only."
                        wire:loading.attr="disabled"
                        wire:target="adminTestRedeem({{ $item->id }}, '{{ $item->type }}')"
                        class="py-1 px-3 rounded-full border border-dashed border-gray-400 text-xs font-semibold text-gray-600 whitespace-nowrap"
                        title="Admin/dev testing only — hidden in production"
                    >
                        <span wire:loading.remove wire:target="adminTestRedeem({{ $item->id }}, '{{ $item->type }}')">Redeem</span>
                        <span wire:loading wire:target="adminTestRedeem({{ $item->id }}, '{{ $item->type }}')">...</span>
                    </button>
                @endif
            @endif
        @endif
    </x-slot:actions>
</x-voucher.ticket>

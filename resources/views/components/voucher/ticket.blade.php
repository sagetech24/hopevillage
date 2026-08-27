@props([
    'type' => 'merchant', // merchant | admin
    'merchantLabel' => 'All Sellers',
    'dimmed' => false,
])

@php
    $isAdmin = $type === 'admin';
    $leftBg = $isAdmin ? 'bg-teal-500' : 'bg-orange-500';
    $merchantLabel = filled($merchantLabel) ? $merchantLabel : 'All Sellers';
    $hasMeta = isset($meta) && trim((string) $meta) !== '';
    $hasActions = isset($actions) && trim((string) $actions) !== '';
    $hasFooter = isset($footer) && trim((string) $footer) !== '';
    $ticketStyle = '-webkit-mask-image: radial-gradient(circle 5px at left center, transparent 5px, #000 5.5px); -webkit-mask-size: 100% 16px; -webkit-mask-repeat: repeat-y; mask-image: radial-gradient(circle 5px at left center, transparent 5px, #000 5.5px); mask-size: 100% 16px; mask-repeat: repeat-y;';
    if ($dimmed) {
        $ticketStyle .= ' opacity: 0.75; filter: grayscale(100%);';
    }
@endphp

{{-- Canonical voucher ticket shell. Member vouchers and merchant/admin lists share this design. --}}
<div
    {{ $attributes->merge(['class' => 'bg-white rounded-r-lg border border-gray-400 overflow-hidden w-full']) }}
    style="{{ $ticketStyle }}"
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
                {{ $slot }}
            </div>

            @if($hasMeta || $hasActions)
                <div class="flex items-center justify-between w-full pr-2 mt-auto">
                    @if($hasMeta)
                        {{ $meta }}
                    @endif
                    @if($hasActions)
                        <div @class(['flex items-center gap-2 shrink-0', 'ml-auto' => ! $hasMeta])>
                            {{ $actions }}
                        </div>
                    @endif
                </div>
            @endif

            @if($hasFooter)
                <div class="w-full">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>

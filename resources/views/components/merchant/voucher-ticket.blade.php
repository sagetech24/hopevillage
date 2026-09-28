@props([
    'voucher',
    'type' => 'merchant', // merchant | admin
    'merchantLabel' => null,
    'statusLabel' => null,
    'statusClass' => null,
])

@php
    $isAdmin = $type === 'admin';
    $merchantLabel = $merchantLabel
        ?? ($isAdmin ? 'Admin Voucher' : optional($voucher->merchant)->name);
    $merchantLabel = filled($merchantLabel) ? $merchantLabel : 'All Sellers';

    $isValidVoucher = method_exists($voucher, 'isValid') ? (bool) $voucher->isValid() : false;
    $computedStatusLabel = $statusLabel ?? ((($voucher->is_active ?? false) && $isValidVoucher) ? 'Active' : 'Inactive');

    $statusOutline = match (mb_strtolower((string) $computedStatusLabel)) {
        'active' => 'border-green-500 text-green-600',
        'expired', 'full' => 'border-red-500 text-red-600',
        'pending approval' => 'border-yellow-500 text-yellow-700',
        'inactive' => 'border-gray-400 text-gray-600',
        default => 'border-gray-300 text-gray-500',
    };
    if ($isAdmin) {
        if (! is_null($voucher->points_cost ?? null)) {
            $valueText = 'Points Cost: '.number_format((int) $voucher->points_cost);
        } else {
            $valueText = 'Admin reward voucher';
        }
    } elseif (($voucher->discount_type ?? null) === 'percentage') {
        $valueText = rtrim(rtrim((string) ($voucher->discount_value ?? 0), '0'), '.').'% off';
    } elseif (($voucher->discount_type ?? null) === 'item') {
        $valueText = 'Free Item';
    } else {
        $valueText = '$'.number_format((float) ($voucher->discount_value ?? 0), 2).' off';
    }

    $secondaryText = null;
    if (! $isAdmin && ! is_null($voucher->min_purchase ?? null)) {
        $secondaryText = 'Min. Spend $'.number_format((float) $voucher->min_purchase, 0);
    }

    $expiryText = ! empty($voucher->valid_until)
        ? \Carbon\Carbon::parse($voucher->valid_until)->format('d/m/Y g:i A')
        : null;

    $hasMeta = isset($meta) && trim((string) $meta) !== '';
    $hasActions = isset($actions) && trim((string) $actions) !== '';
    $hasFooter = isset($footer) && trim((string) $footer) !== '';
@endphp

<x-voucher.ticket :type="$type" :merchant-label="$merchantLabel" {{ $attributes }}>
    <p class="text-sm font-bold text-gray-900 leading-tight">{{ $voucher->name }}</p>

    @if($isAdmin)
        <p class="text-sm text-gray-700 mt-1 font-semibold">{{ $valueText }}</p>
    @else
        <p class="text-xs text-gray-700 mt-1">{{ $valueText }}</p>
    @endif

    @if($secondaryText)
        <p class="text-gray-600 mt-1 text-[0.7rem]">{{ $secondaryText }}</p>
    @endif

    @if($expiryText)
        <p class="text-xs text-gray-500 mt-0.5">
            <span class="text-gray-500 font-normal text-xs">Valid Until:</span>
            <span class="text-gray-700 font-normal text-xs">{{ $expiryText }}</span>
        </p>
    @endif

    <x-slot:meta>
        @if($hasMeta)
            {{ $meta }}
        @endif
    </x-slot:meta>

    <x-slot:actions>
        <span class="py-1 px-3 rounded-full border text-xs font-semibold {{ $statusOutline }}">
            {{ $computedStatusLabel }}
        </span>
        @if($hasActions)
            {{ $actions }}
        @endif
    </x-slot:actions>

    <x-slot:footer>
        @if($hasFooter)
            {{ $footer }}
        @endif
    </x-slot:footer>
</x-voucher.ticket>
